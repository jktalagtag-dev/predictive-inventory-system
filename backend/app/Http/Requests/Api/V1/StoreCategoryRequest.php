<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Catalog\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    public function rules(): array
    {
        return [
            'parentCategoryId' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
