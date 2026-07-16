<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('purchase_orders.submit')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer']];
    }
}
