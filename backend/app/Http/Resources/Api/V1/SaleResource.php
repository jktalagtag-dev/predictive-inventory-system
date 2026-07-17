<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Sales\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Sale $resource
 */
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sale = $this->resource;

        return [
            'id' => (string) $sale->id,
            'branchId' => (string) $sale->branch_id,
            'saleNumber' => $sale->sale_number,
            'status' => $sale->status,
            'currencyCode' => $sale->currency_code,
            'soldAt' => optional($sale->sold_at)->toIso8601String(),
            'completedAt' => optional($sale->completed_at)->toIso8601String(),
            'voidedAt' => optional($sale->voided_at)->toIso8601String(),
            'refundedAt' => optional($sale->refunded_at)->toIso8601String(),
            'reversesSaleId' => $sale->reverses_sale_id ? (string) $sale->reverses_sale_id : null,
            'subtotalAmount' => (string) $sale->subtotal_amount,
            'discountAmount' => (string) $sale->discount_amount,
            'taxAmount' => (string) $sale->tax_amount,
            'totalAmount' => (string) $sale->total_amount,
            'cashierUserId' => (string) $sale->cashier_user_id,
            'cashierName' => $sale->relationLoaded('cashier') && $sale->cashier ? $sale->cashier->display_name : null,
            'approvedByUserId' => $sale->approved_by_user_id ? (string) $sale->approved_by_user_id : null,
            'notes' => $sale->notes,
            'lineCount' => $sale->lines_count ?? ($sale->relationLoaded('lines') ? $sale->lines->count() : null),
            'lines' => $sale->relationLoaded('lines') ? $sale->lines->map(fn ($line) => [
                'id' => (string) $line->id,
                'lineNumber' => $line->line_number,
                'productId' => (string) $line->product_id,
                'productSku' => $line->product_sku_snapshot,
                'productName' => $line->product_name_snapshot,
                'unitId' => (string) $line->unit_id,
                'quantity' => (string) $line->quantity,
                'stockQuantityDelta' => (string) $line->stock_quantity_delta,
                'unitPrice' => (string) $line->unit_price,
                'discountAmount' => (string) $line->discount_amount,
                'taxRate' => (string) $line->tax_rate,
                'taxAmount' => (string) $line->tax_amount,
                'lineTotalAmount' => (string) $line->line_total_amount,
                'overrideReason' => $line->override_reason,
            ])->values() : [],
            'payments' => $sale->relationLoaded('payments') ? $sale->payments->map(fn ($payment) => [
                'id' => (string) $payment->id,
                'paymentMethod' => $payment->payment_method,
                'amount' => (string) $payment->amount,
                'currencyCode' => $payment->currency_code,
                'externalReference' => $payment->external_reference,
                'receivedAt' => optional($payment->received_at)->toIso8601String(),
            ])->values() : [],
            'version' => $sale->row_version,
        ];
    }
}
