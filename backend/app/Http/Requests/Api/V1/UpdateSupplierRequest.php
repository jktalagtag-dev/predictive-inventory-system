<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
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
            'code' => ['sometimes', 'string', 'max:64'],
            'legalName' => ['sometimes', 'string', 'max:255'],
            'taxIdentifier' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'string', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:48'],
            'addressLine1' => ['nullable', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'postalCode' => ['nullable', 'string', 'max:24'],
            'countryCode' => ['sometimes', 'string', 'size:2'],
            'defaultCurrencyCode' => ['sometimes', 'string', 'size:3'],
            'isActive' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer'],
        ];
    }
}
