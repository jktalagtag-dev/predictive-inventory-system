<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'stockUnitId' => ['required', 'integer', Rule::exists('units_of_measure', 'id')->where('is_active', true)],
            'sku' => ['required', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'productType' => ['required', 'string', Rule::in(Product::TYPES)],
            'isActive' => ['sometimes', 'boolean'],
            'isLotTracked' => ['sometimes', 'boolean'],
            'isSerialTracked' => ['sometimes', 'boolean'],
            'isExpiryTracked' => ['sometimes', 'boolean'],
            'defaultTaxRate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
