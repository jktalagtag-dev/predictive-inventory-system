<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class RecordManualPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('forecasting.override')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'manualQuantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
            'expiresAt' => ['required', 'date', 'after:now'],
        ];
    }
}
