<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('purchase_orders.create')) {
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
            'supplierId' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'currencyCode' => ['required', 'string', 'size:3'],
            'expectedReceiptAt' => ['nullable', 'date'],
            'supplierReference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.productId' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.unitId' => ['required', 'integer', Rule::exists('units_of_measure', 'id')->where('is_active', true)],
            'lines.*.orderedQuantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unitCost' => ['required', 'numeric', 'min:0'],
            'lines.*.taxRate' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discountAmount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.expectedReceiptAt' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productIds = collect($this->input('lines', []))->pluck('productId');
            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('lines', 'Each product may appear only once per purchase order.');
            }
        });
    }
}
