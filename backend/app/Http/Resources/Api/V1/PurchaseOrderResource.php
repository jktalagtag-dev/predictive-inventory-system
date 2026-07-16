<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Procurement\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PurchaseOrder $resource
 */
class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $po = $this->resource;

        return [
            'id' => (string) $po->id,
            'branchId' => (string) $po->branch_id,
            'supplier' => $po->relationLoaded('supplier') && $po->supplier ? [
                'id' => (string) $po->supplier->id,
                'code' => $po->supplier->code,
                'legalName' => $po->supplier->legal_name,
            ] : null,
            'poNumber' => $po->po_number,
            'status' => $po->status,
            'currencyCode' => $po->currency_code,
            'orderedAt' => optional($po->ordered_at)->toIso8601String(),
            'expectedReceiptAt' => optional($po->expected_receipt_at)->toIso8601String(),
            'submittedAt' => optional($po->submitted_at)->toIso8601String(),
            'approvedAt' => optional($po->approved_at)->toIso8601String(),
            'cancelledAt' => optional($po->cancelled_at)->toIso8601String(),
            'subtotalAmount' => (string) $po->subtotal_amount,
            'taxAmount' => (string) $po->tax_amount,
            'discountAmount' => (string) $po->discount_amount,
            'totalAmount' => (string) $po->total_amount,
            'supplierReference' => $po->supplier_reference,
            'notes' => $po->notes,
            'lines' => $po->relationLoaded('lines') ? $po->lines->map(fn ($line) => [
                'id' => (string) $line->id,
                'lineNumber' => $line->line_number,
                'productId' => (string) $line->product_id,
                'productSku' => $line->product_sku_snapshot,
                'productName' => $line->product_name_snapshot,
                'unitId' => (string) $line->unit_id,
                'orderedQuantity' => (string) $line->ordered_quantity,
                'receivedQuantity' => (string) $line->received_quantity,
                'unitCost' => (string) $line->unit_cost,
                'taxRate' => (string) $line->tax_rate,
                'discountAmount' => (string) $line->discount_amount,
                'netAmount' => (string) $line->net_amount,
                'taxAmount' => (string) $line->tax_amount,
                'totalAmount' => (string) $line->total_amount,
                'notes' => $line->notes,
            ])->values() : [],
            'approvals' => $po->relationLoaded('approvals') ? $po->approvals->map(fn ($approval) => [
                'id' => (string) $approval->id,
                'approvalStage' => $approval->approval_stage,
                'decision' => $approval->decision,
                'decisionByUserId' => (string) $approval->decision_by_user_id,
                'decisionAt' => $approval->decision_at?->toIso8601String(),
                'reason' => $approval->reason,
            ])->values() : [],
            'version' => $po->row_version,
        ];
    }
}
