<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ReverseGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('goods_receipts.reverse')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'version' => ['required', 'integer'],
        ];
    }
}
