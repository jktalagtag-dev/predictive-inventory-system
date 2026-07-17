<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Inventory\Models\GoodsReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property GoodsReceipt $resource
 */
class GoodsReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $receipt = $this->resource;

        return [
            'id' => (string) $receipt->id,
            'purchaseOrderId' => $receipt->purchase_order_id ? (string) $receipt->purchase_order_id : null,
            'branchId' => (string) $receipt->branch_id,
            'receiptNumber' => $receipt->receipt_number,
            'status' => $receipt->status,
            'supplierDeliveryNumber' => $receipt->supplier_delivery_number,
            'receivedAt' => optional($receipt->received_at)->toIso8601String(),
            'postedAt' => optional($receipt->posted_at)->toIso8601String(),
            'reversedAt' => optional($receipt->reversed_at)->toIso8601String(),
            'reversalReceiptId' => $receipt->reversal_receipt_id ? (string) $receipt->reversal_receipt_id : null,
            'notes' => $receipt->notes,
            'lineCount' => $receipt->lines_count ?? ($receipt->relationLoaded('lines') ? $receipt->lines->count() : null),
            'lines' => $receipt->relationLoaded('lines') ? $receipt->lines->map(fn ($line) => [
                'id' => (string) $line->id,
                'lineNumber' => $line->line_number,
                'purchaseOrderLineId' => $line->purchase_order_line_id ? (string) $line->purchase_order_line_id : null,
                'productId' => (string) $line->product_id,
                'productSku' => $line->product_sku_snapshot,
                'productName' => $line->product_name_snapshot,
                'unitId' => (string) $line->unit_id,
                'receivedQuantity' => (string) $line->received_quantity,
                'acceptedQuantity' => (string) $line->accepted_quantity,
                'rejectedQuantity' => (string) $line->rejected_quantity,
                'unitCost' => (string) $line->unit_cost,
                'lotNumber' => $line->lot_number,
                'serialNumber' => $line->serial_number,
                'expiryDate' => optional($line->expiry_date)->toDateString(),
                'rejectionReason' => $line->rejection_reason,
                'notes' => $line->notes,
            ])->values() : [],
            'version' => $receipt->row_version,
        ];
    }
}
