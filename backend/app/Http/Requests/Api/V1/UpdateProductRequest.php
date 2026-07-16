<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        return [
            'categoryId' => ['sometimes', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'stockUnitId' => ['sometimes', 'integer', Rule::exists('units_of_measure', 'id')->where('is_active', true)],
            'sku' => ['sometimes', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'productType' => ['sometimes', 'string', Rule::in(Product::TYPES)],
            'isActive' => ['sometimes', 'boolean'],
            'isLotTracked' => ['sometimes', 'boolean'],
            'isSerialTracked' => ['sometimes', 'boolean'],
            'isExpiryTracked' => ['sometimes', 'boolean'],
            'defaultTaxRate' => ['sometimes', 'numeric', 'min:0'],
            'version' => ['required', 'integer'],
        ];
    }
}
