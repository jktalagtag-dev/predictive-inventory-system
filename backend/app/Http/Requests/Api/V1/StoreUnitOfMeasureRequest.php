<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitOfMeasureRequest extends FormRequest
{
    public const DIMENSIONS = ['count', 'volume', 'mass', 'length'];

    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('units.manage')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:24', Rule::unique('units_of_measure', 'code')],
            'name' => ['required', 'string', 'max:80', Rule::unique('units_of_measure', 'name')],
            'symbol' => ['required', 'string', 'max:16'],
            'dimension' => ['required', 'string', Rule::in(self::DIMENSIONS)],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
