<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class MarkOrderedPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('purchase_orders.order')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'orderedAt' => ['required', 'date'],
            'supplierReference' => ['nullable', 'string', 'max:120'],
            'version' => ['required', 'integer'],
        ];
    }
}
