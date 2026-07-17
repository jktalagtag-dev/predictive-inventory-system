<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Governance\Services\AuditLogger;
use App\Domains\Governance\Services\SettingsService;
use App\Domains\Identity\Models\User;
use App\Domains\Reporting\Models\ReportExport;
use App\Domains\Reporting\Support\ReportCatalog;
use App\Domains\Reporting\Support\ReportTableExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Generates and stores report export files. Runs synchronously within the
 * request rather than dispatching a queued job: this deployment does not
 * currently run a queue worker (docker-compose only runs `php artisan
 * serve`), and the report set is small enough that interactive generation
 * stays within a reasonable request budget. The report_exports row still
 * models the full queued/running/completed/failed/expired lifecycle from
 * DATABASE_DESIGN.md section 10.4 so a real queue can be introduced later
 * without a data-model change — see CLAUDE.md section 54's async guidance,
 * which this documents as a deliberate, reviewable trade-off rather than a
 * silent shortcut.
 */
class ReportExportService
{
    private const DISK = 'local';

    public function __construct(
        private readonly ReportRunner $reportRunner,
        private readonly SettingsService $settingsService,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(string $reportCode, string $format, array $filters, ?int $branchId, User $actor, string $correlationId): ReportExport
    {
        $definition = ReportCatalog::find($reportCode);

        if ($definition === null) {
            throw new ReportException('UNKNOWN_REPORT', 404, 'This report is not in the catalog.');
        }

        if (! in_array($format, $definition->formats, true) || ! in_array($format, ReportExport::FORMATS, true)) {
            throw new ReportException('INVALID_EXPORT_FORMAT', 422, "This report does not support the {$format} format.");
        }

        // Exports always contain the full filtered result set regardless
        // of any interactive page/perPage the caller passed in filters —
        // a downloaded file must not silently reflect only one page.
        $reportResult = $this->reportRunner->run($reportCode, [...$filters, 'page' => 1, 'perPage' => 100000], $actor);

        // Row creation, file generation, and the completion update are
        // deliberately not wrapped in one long-lived transaction: PDF/XLSX
        // generation is CPU/disk-bound work, and holding a DB transaction
        // open across it would needlessly extend row locks (CLAUDE.md
        // section 62). Each state transition below is its own atomic
        // single-row write instead.
        $export = ReportExport::query()->create([
            'requested_by_user_id' => $actor->id,
            'branch_id' => $branchId,
            'report_code' => $reportCode,
            'format' => $format,
            'status' => 'running',
            'filters_snapshot' => $filters,
            'data_cutoff_at' => $reportResult['meta']['dataCutoffAt'],
            'requested_at' => CarbonImmutable::now(),
        ]);

        try {
            [$storageKey, $fileName, $contentType, $sizeBytes] = $this->generateFile($export, $definition->title, $reportResult);

            $retentionDays = (int) $this->settingsService->resolveValue('reports.export_retention_days', null);

            $export->update([
                'status' => 'completed',
                'storage_key' => $storageKey,
                'file_name' => $fileName,
                'content_type' => $contentType,
                'file_size_bytes' => $sizeBytes,
                'expires_at' => CarbonImmutable::now()->addDays($retentionDays > 0 ? $retentionDays : 30),
                'completed_at' => CarbonImmutable::now(),
            ]);
        } catch (\Throwable $exception) {
            $export->update(['status' => 'failed', 'failure_code' => 'EXPORT_GENERATION_FAILED']);
            report($exception);

            throw new ReportException('EXPORT_GENERATION_FAILED', 500, 'The export could not be generated.');
        }

        $this->auditLogger->record(
            actor: $actor,
            action: 'report_export.requested',
            entityType: 'report_export',
            entityId: $export->id,
            branchId: $branchId,
            correlationId: $correlationId,
            after: ['reportCode' => $reportCode, 'format' => $format, 'status' => $export->status],
        );

        return $export->refresh();
    }

    /**
     * @return array{string, string, string, int} storageKey, fileName, contentType, sizeBytes
     */
    private function generateFile(ReportExport $export, string $title, array $reportResult): array
    {
        $baseName = Str::slug($export->report_code).'-'.$export->id.'-'.now()->format('Ymd-His');
        $directory = 'report-exports';

        return match ($export->format) {
            'pdf' => $this->generatePdf($directory, $baseName, $title, $reportResult),
            'csv' => $this->generateSpreadsheet($directory, $baseName, $reportResult, ExcelFormat::CSV, 'csv', 'text/csv'),
            'xlsx' => $this->generateSpreadsheet($directory, $baseName, $reportResult, ExcelFormat::XLSX, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            default => throw new ReportException('INVALID_EXPORT_FORMAT', 422, 'Unsupported export format.'),
        };
    }

    private function generatePdf(string $directory, string $baseName, string $title, array $reportResult): array
    {
        $pdf = Pdf::loadView('reports.export-pdf', [
            'title' => $title,
            'columns' => $reportResult['columns'],
            'rows' => $reportResult['rows'],
            'meta' => $reportResult['meta'],
        ]);

        $contents = $pdf->output();
        $storageKey = "{$directory}/{$baseName}.pdf";
        Storage::disk(self::DISK)->put($storageKey, $contents);

        return [$storageKey, "{$baseName}.pdf", 'application/pdf', strlen($contents)];
    }

    private function generateSpreadsheet(string $directory, string $baseName, array $reportResult, string $excelFormat, string $extension, string $contentType): array
    {
        $storageKey = "{$directory}/{$baseName}.{$extension}";
        $export = new ReportTableExport($reportResult['columns'], $reportResult['rows']);

        Excel::store($export, $storageKey, self::DISK, $excelFormat);

        $sizeBytes = Storage::disk(self::DISK)->size($storageKey);

        return [$storageKey, "{$baseName}.{$extension}", $contentType, $sizeBytes];
    }

    public function readFile(ReportExport $export): string
    {
        if ($export->status !== 'completed' || $export->storage_key === null) {
            throw new ReportException('EXPORT_NOT_READY', 409, 'This export is not ready for download.');
        }

        if ($export->isExpired()) {
            throw new ReportException('EXPORT_EXPIRED', 410, 'This export has expired. Request a new one.');
        }

        return Storage::disk(self::DISK)->get($export->storage_key);
    }
}
