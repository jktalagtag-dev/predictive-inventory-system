<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Planning\Models\ForecastRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ForecastRun $resource
 */
class ForecastRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $run = $this->resource;

        return [
            'id' => (string) $run->id,
            'branchId' => $run->branch_id ? (string) $run->branch_id : null,
            'modelCode' => $run->model_code,
            'modelVersion' => $run->model_version,
            'periodGrain' => $run->period_grain,
            'windowPeriods' => $run->window_periods,
            'historyStartDate' => $run->history_start_date?->toDateString(),
            'historyEndDate' => $run->history_end_date?->toDateString(),
            'dataCutoffAt' => optional($run->data_cutoff_at)->toIso8601String(),
            'status' => $run->status,
            'failureCode' => $run->failure_code,
            'itemCount' => $run->items_count ?? ($run->relationLoaded('items') ? $run->items->count() : null),
            'items' => $run->relationLoaded('items') ? $run->items->map(fn ($item) => [
                'productId' => (string) $item->product_id,
                'productSku' => $item->product_sku_snapshot,
                'productName' => $item->product_name_snapshot,
                'historyPeriodCount' => $item->history_period_count,
                'demandTotal' => (string) $item->demand_total,
                'forecastQuantity' => $item->forecast_quantity !== null ? (string) $item->forecast_quantity : null,
                'coldStartStatus' => $item->cold_start_status,
                'manualQuantity' => $item->manual_quantity !== null ? (string) $item->manual_quantity : null,
                'manualReason' => $item->manual_reason,
                'manualExpiresAt' => optional($item->manual_expires_at)->toIso8601String(),
            ])->values() : [],
            'createdAt' => optional($run->created_at)->toIso8601String(),
        ];
    }
}
