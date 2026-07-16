<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Domains\Identity\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:48'],
            'roleIds' => ['required', 'array', 'min:1'],
            'roleIds.*' => ['integer', Rule::exists('roles', 'id')],
            'branchIds' => ['required', 'array', 'min:1'],
            'branchIds.*' => ['integer', Rule::exists('branches', 'id')],
            'defaultBranchId' => ['required', 'integer', Rule::in($this->input('branchIds', []))],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
