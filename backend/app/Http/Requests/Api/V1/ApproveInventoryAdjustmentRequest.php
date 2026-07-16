<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ApproveInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('inventory.adjustments.approve')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
