<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Sales\Models\SalePayment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizeSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('pos.finalize')) {
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
            'soldAt' => ['required', 'date'],
            'currencyCode' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'approvedByUserId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.productId' => ['required', 'integer', Rule::exists('products', 'id')],
            'lines.*.productUnitId' => ['required', 'integer', Rule::exists('units_of_measure', 'id')],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unitPrice' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discountAmount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.overrideReason' => ['nullable', 'string', 'max:500'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.paymentMethod' => ['required', 'string', Rule::in(SalePayment::METHODS)],
            'payments.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'payments.*.externalReference' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productIds = collect($this->input('lines', []))->pluck('productId');
            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('lines', 'Each product may appear only once per sale.');
            }
        });
    }
}
