<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ShowDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('dashboard.read')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'branchId' => ['required', 'integer', 'exists:branches,id'],
            // Order and range-length are validated in DashboardService so
            // both cases return the spec's INVALID_DATE_RANGE error code
            // instead of the generic FormRequest validation envelope.
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'timezone' => ['sometimes', 'timezone'],
        ];
    }
}
