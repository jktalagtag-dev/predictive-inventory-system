<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Reporting\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ReportExport $resource
 */
class ReportExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $export = $this->resource;

        return [
            'id' => (string) $export->id,
            'reportCode' => $export->report_code,
            'format' => $export->format,
            'status' => $export->status,
            'branchId' => $export->branch_id ? (string) $export->branch_id : null,
            'filtersSnapshot' => $export->filters_snapshot,
            'dataCutoffAt' => optional($export->data_cutoff_at)->toIso8601String(),
            'fileName' => $export->file_name,
            'fileSizeBytes' => $export->file_size_bytes,
            'requestedAt' => optional($export->requested_at)->toIso8601String(),
            'completedAt' => optional($export->completed_at)->toIso8601String(),
            'expiresAt' => optional($export->expires_at)->toIso8601String(),
            'failureCode' => $export->failure_code,
            'downloadLink' => $export->status === 'completed' ? "/api/v1/report-exports/{$export->id}/download" : null,
        ];
    }
}
