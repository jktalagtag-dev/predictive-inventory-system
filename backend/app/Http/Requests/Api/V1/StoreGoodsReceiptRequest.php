<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('goods_receipts.create')) {
            throw new AuthorizationException;
        }

        if (! $this->user()->canAccessBranch((int) $this->input('branchId'))) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'branchId' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'purchaseOrderId' => ['required', 'integer', Rule::exists('purchase_orders', 'id')],
            'supplierDeliveryNumber' => ['nullable', 'string', 'max:120'],
            'receivedAt' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchaseOrderLineId' => ['required', 'integer', Rule::exists('purchase_order_lines', 'id')],
            'lines.*.receivedQuantity' => ['required', 'numeric', 'min:0'],
            'lines.*.acceptedQuantity' => ['required', 'numeric', 'min:0'],
            'lines.*.rejectedQuantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unitCost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.lotNumber' => ['nullable', 'string', 'max:120'],
            'lines.*.serialNumber' => ['nullable', 'string', 'max:120'],
            'lines.*.expiryDate' => ['nullable', 'date'],
            'lines.*.rejectionReason' => ['nullable', 'string', 'max:500'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lineIds = collect($this->input('lines', []))->pluck('purchaseOrderLineId');
            if ($lineIds->count() !== $lineIds->unique()->count()) {
                $validator->errors()->add('lines', 'Each purchase order line may appear only once per receipt.');
            }
        });
    }
}
