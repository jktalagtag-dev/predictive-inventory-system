<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('inventory.adjustments.update')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'reasonCode' => ['sometimes', 'string', Rule::in(StoreInventoryAdjustmentRequest::REASON_CODES)],
            'reasonNote' => ['nullable', 'string', 'max:1000'],
            'effectiveAt' => ['sometimes', 'date'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.productId' => ['required_with:lines', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.quantityDelta' => ['required_with:lines', 'numeric', 'not_in:0'],
            'lines.*.unitCost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer'],
        ];
    }
}
