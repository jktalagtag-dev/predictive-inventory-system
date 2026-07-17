<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class CalculateEoqRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('planning.eoq.calculate')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'annualDemandQuantity' => ['required', 'numeric', 'min:0'],
            'orderingCost' => ['required', 'numeric', 'min:0'],
            'annualHoldingCostPerUnit' => ['required', 'numeric', 'gt:0'],
            'currencyCode' => ['required', 'string', 'size:3'],
        ];
    }
}
