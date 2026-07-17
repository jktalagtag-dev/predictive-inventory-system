<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeRestockingAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('restocking.acknowledge')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'assignedToUserId' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer'],
        ];
    }
}
