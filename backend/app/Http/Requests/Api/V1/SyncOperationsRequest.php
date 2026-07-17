<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncOperationsRequest extends FormRequest
{
    public const MAX_BATCH_SIZE = 50;

    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('sync.use')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:'.self::MAX_BATCH_SIZE],
            'operations.*.clientOperationId' => ['required', 'uuid'],
            'operations.*.operationType' => ['required', 'string', 'max:80'],
            'operations.*.branchId' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'operations.*.payloadVersion' => ['required', 'integer', 'min:1'],
            'operations.*.idempotencyKey' => ['required', 'string', 'max:128'],
            'operations.*.dependencyOperationId' => ['nullable', 'uuid'],
            'operations.*.payload' => ['required', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = collect($this->input('operations', []))->pluck('clientOperationId');
            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('operations', 'Each clientOperationId must be unique within a batch.');
            }
        });
    }
}
