<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierContactRequest extends FormRequest
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
            'fullName' => ['sometimes', 'string', 'max:160'],
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:48'],
            'isPrimary' => ['sometimes', 'boolean'],
            'isActive' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer'],
        ];
    }
}
