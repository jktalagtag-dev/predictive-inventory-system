<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'firstName' => ['sometimes', 'string', 'max:100'],
            'lastName' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'string', 'email', 'max:254', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:48'],
            'roleIds' => ['sometimes', 'array', 'min:1'],
            'roleIds.*' => ['integer', Rule::exists('roles', 'id')],
            'branchIds' => ['sometimes', 'array', 'min:1'],
            'branchIds.*' => ['integer', Rule::exists('branches', 'id')],
            'defaultBranchId' => ['sometimes', 'integer', Rule::in($this->input('branchIds', []))],
            'isActive' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer'],
        ];
    }
}
