<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    public function rules(): array
    {
        return [
            'parentCategoryId' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'code' => ['sometimes', 'string', 'max:64'],
            'name' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer'],
        ];
    }
}
