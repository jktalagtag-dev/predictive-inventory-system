<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('branch'));
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:32', Rule::unique('branches', 'code')->ignore($branchId)],
            'name' => ['sometimes', 'string', 'max:160'],
            'addressLine1' => ['nullable', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'postalCode' => ['nullable', 'string', 'max:24'],
            'countryCode' => ['sometimes', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:48'],
            'isActive' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer'],
        ];
    }
}
