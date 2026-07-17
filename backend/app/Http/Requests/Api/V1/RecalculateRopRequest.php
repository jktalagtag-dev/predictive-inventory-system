<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecalculateRopRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('planning.rop.calculate')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'forecastRunId' => ['nullable', 'integer', Rule::exists('forecast_runs', 'id')],
        ];
    }
}
