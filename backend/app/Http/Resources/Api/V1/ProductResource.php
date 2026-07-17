<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Product $resource
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            'id' => (string) $product->id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->relationLoaded('category') && $product->category ? [
                'id' => (string) $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'stockUnit' => $product->relationLoaded('stockUnit') && $product->stockUnit ? [
                'id' => (string) $product->stockUnit->id,
                'code' => $product->stockUnit->code,
                'symbol' => $product->stockUnit->symbol,
            ] : null,
            'productType' => $product->product_type,
            'isActive' => (bool) $product->is_active,
            'isLotTracked' => (bool) $product->is_lot_tracked,
            'isSerialTracked' => (bool) $product->is_serial_tracked,
            'isExpiryTracked' => (bool) $product->is_expiry_tracked,
            'defaultTaxRate' => (string) $product->default_tax_rate,
            'sellingPrice' => (string) $product->selling_price,
            'defaultLeadTimeDays' => $product->default_lead_time_days !== null ? (string) $product->default_lead_time_days : null,
            // Only present when the caller requested a branch-scoped stock
            // snapshot (see ProductController::attachStockSnapshots) and is
            // authorized to see it; otherwise null, never fabricated.
            'stock' => $this->stockSnapshot($product),
            'updatedAt' => $product->updated_at?->toIso8601String(),
            'version' => $product->row_version,
        ];
    }

    private function stockSnapshot(Product $product): ?array
    {
        if (! array_key_exists('stock_snapshot', $product->getAttributes())) {
            return null;
        }

        $balance = $product->getAttribute('stock_snapshot');

        if ($balance === null) {
            return ['onHandQuantity' => '0.0000', 'availableQuantity' => '0.0000', 'incomingQuantity' => '0.0000', 'lastMovementAt' => null];
        }

        return [
            'onHandQuantity' => (string) $balance->on_hand_quantity,
            'availableQuantity' => (string) $balance->available_quantity,
            'incomingQuantity' => (string) $balance->incoming_quantity,
            'lastMovementAt' => optional($balance->last_movement_at)->toIso8601String(),
        ];
    }
}
