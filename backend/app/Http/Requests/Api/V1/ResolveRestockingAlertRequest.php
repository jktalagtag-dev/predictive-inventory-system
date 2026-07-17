<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ResolveRestockingAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('restocking.resolve')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'version' => ['required', 'integer'],
        ];
    }
}
