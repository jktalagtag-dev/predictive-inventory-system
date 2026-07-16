<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('suppliers.update')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'max:160'],
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:48'],
            'isPrimary' => ['sometimes', 'boolean'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('email') && ! $this->filled('phone')) {
                $validator->errors()->add('email', 'At least one contact channel (email or phone) is required.');
            }
        });
    }
}
