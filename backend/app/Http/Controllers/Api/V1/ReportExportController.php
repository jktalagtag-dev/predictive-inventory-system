<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Governance\Services\AuditLogger;
use App\Domains\Reporting\Models\ReportExport;
use App\Domains\Reporting\Services\ReportException;
use App\Domains\Reporting\Services\ReportExportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateReportExportRequest;
use App\Http\Resources\Api\V1\ReportExportResource;
use App\Support\Services\IdempotencyConflictException;
use App\Support\Services\IdempotencyGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly ReportExportService $reportExportService,
        private readonly IdempotencyGuard $idempotencyGuard,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function store(CreateReportExportRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to request a report export.']]);
        }

        $validated = $request->validated();
        $correlationId = $this->correlationId($request);

        try {
            $replay = $this->idempotencyGuard->begin(
                $request->user(),
                'report_exports.create',
                $idempotencyKey,
                $validated,
                $correlationId,
            );
        } catch (IdempotencyConflictException $exception) {
            return response()->json([
                'error' => ['code' => $exception->errorCode(), 'message' => $exception->getMessage(), 'requestId' => $correlationId],
            ], $exception->status());
        }

        if ($replay !== null) {
            return response()->json($replay, 201);
        }

        try {
            $export = $this->reportExportService->create(
                $validated['reportCode'],
                $validated['format'],
                $validated['filters'] ?? [],
                $validated['branchId'] ?? null,
                $request->user(),
                $correlationId,
            );
        } catch (ReportException $exception) {
            return response()->json([
                'error' => ['code' => $exception->errorCode(), 'message' => $exception->getMessage(), 'requestId' => $correlationId],
            ], $exception->status());
        }

        $responseBody = ['data' => (new ReportExportResource($export))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'report_exports.create', $idempotencyKey, 201, $responseBody, 'report_export', $export->id);

        return response()->json($responseBody, 201);
    }

    public function show(Request $request, ReportExport $reportExport): JsonResponse
    {
        $this->authorize('view', $reportExport);

        return (new ReportExportResource($reportExport))->response();
    }

    public function download(Request $request, ReportExport $reportExport): Response|JsonResponse
    {
        $this->authorize('download', $reportExport);

        try {
            $contents = $this->reportExportService->readFile($reportExport);
        } catch (ReportException $exception) {
            return response()->json([
                'error' => ['code' => $exception->errorCode(), 'message' => $exception->getMessage(), 'requestId' => $this->correlationId($request)],
            ], $exception->status());
        }

        $this->auditLogger->record(
            actor: $request->user(),
            action: 'report_export.downloaded',
            entityType: 'report_export',
            entityId: $reportExport->id,
            branchId: $reportExport->branch_id,
            correlationId: $this->correlationId($request),
            ipAddress: $request->ip(),
        );

        return response($contents, 200, [
            'Content-Type' => $reportExport->content_type ?? 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$reportExport->file_name.'"',
        ]);
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }
}
