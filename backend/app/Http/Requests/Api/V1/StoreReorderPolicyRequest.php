<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Planning\Models\ReorderPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReorderPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('planning.rop.manage')) {
            throw new AuthorizationException;
        }

        if (! $this->user()->canAccessBranch((int) $this->input('branchId'))) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'branchId' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'productId' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'preferredSupplierId' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'safetyStockQuantity' => ['required', 'numeric', 'min:0'],
            'safetyStockBasis' => ['required', 'string', Rule::in(ReorderPolicy::SAFETY_STOCK_BASES)],
            'leadTimeDaysOverride' => ['nullable', 'numeric', 'min:0'],
            'leadTimeBasis' => ['required', 'string', Rule::in(ReorderPolicy::LEAD_TIME_BASES)],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
