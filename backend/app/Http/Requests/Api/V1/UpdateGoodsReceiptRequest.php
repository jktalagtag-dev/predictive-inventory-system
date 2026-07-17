<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('goods_receipts.update')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'supplierDeliveryNumber' => ['nullable', 'string', 'max:120'],
            'receivedAt' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.purchaseOrderLineId' => ['required_with:lines', 'integer', Rule::exists('purchase_order_lines', 'id')],
            'lines.*.receivedQuantity' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.acceptedQuantity' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.rejectedQuantity' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.unitCost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.lotNumber' => ['nullable', 'string', 'max:120'],
            'lines.*.serialNumber' => ['nullable', 'string', 'max:120'],
            'lines.*.expiryDate' => ['nullable', 'date'],
            'lines.*.rejectionReason' => ['nullable', 'string', 'max:500'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer'],
        ];
    }
}
