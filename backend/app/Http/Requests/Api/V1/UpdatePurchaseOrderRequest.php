<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('purchase_orders.update')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'currencyCode' => ['sometimes', 'string', 'size:3'],
            'expectedReceiptAt' => ['nullable', 'date'],
            'supplierReference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.productId' => ['required_with:lines', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.unitId' => ['required_with:lines', 'integer', Rule::exists('units_of_measure', 'id')->where('is_active', true)],
            'lines.*.orderedQuantity' => ['required_with:lines', 'numeric', 'gt:0'],
            'lines.*.unitCost' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.taxRate' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discountAmount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.expectedReceiptAt' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer'],
        ];
    }
}
