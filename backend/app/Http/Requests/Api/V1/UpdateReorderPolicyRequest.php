<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Planning\Models\ReorderPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReorderPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('planning.rop.manage')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'preferredSupplierId' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'safetyStockQuantity' => ['sometimes', 'numeric', 'min:0'],
            'safetyStockBasis' => ['sometimes', 'string', Rule::in(ReorderPolicy::SAFETY_STOCK_BASES)],
            'leadTimeDaysOverride' => ['nullable', 'numeric', 'min:0'],
            'leadTimeBasis' => ['sometimes', 'string', Rule::in(ReorderPolicy::LEAD_TIME_BASES)],
            'isActive' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer'],
        ];
    }
}
