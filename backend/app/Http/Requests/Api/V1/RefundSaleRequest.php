<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Sales\Models\SalePayment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('sales.refund')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'version' => ['required', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.productId' => ['required', 'integer', Rule::exists('products', 'id')],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
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
                $validator->errors()->add('lines', 'Each product may appear only once per refund.');
            }
        });
    }
}
