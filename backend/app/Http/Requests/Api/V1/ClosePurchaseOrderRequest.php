<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ClosePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('purchase_orders.close')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer'],
        ];
    }
}
