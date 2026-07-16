<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public const REASON_CODES = ['damage', 'count_correction', 'theft', 'expiry', 'other'];

    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('inventory.adjustments.create')) {
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
            'reasonCode' => ['required', 'string', Rule::in(self::REASON_CODES)],
            'reasonNote' => ['nullable', 'string', 'max:1000'],
            'effectiveAt' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.productId' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.quantityDelta' => ['required', 'numeric', 'not_in:0'],
            'lines.*.unitCost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productIds = collect($this->input('lines', []))->pluck('productId');
            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('lines', 'Each product may appear only once per adjustment.');
            }
        });
    }
}
