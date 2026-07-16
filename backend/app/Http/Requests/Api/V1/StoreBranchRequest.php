<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Domains\Identity\Models\Branch::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('branches', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'addressLine1' => ['nullable', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'postalCode' => ['nullable', 'string', 'max:24'],
            'countryCode' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:48'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
