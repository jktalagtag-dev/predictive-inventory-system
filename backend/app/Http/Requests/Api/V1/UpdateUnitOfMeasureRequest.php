<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('units.manage')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:24', Rule::unique('units_of_measure', 'code')->ignore($unitId)],
            'name' => ['sometimes', 'string', 'max:80', Rule::unique('units_of_measure', 'name')->ignore($unitId)],
            'symbol' => ['sometimes', 'string', 'max:16'],
            'dimension' => ['sometimes', 'string', Rule::in(StoreUnitOfMeasureRequest::DIMENSIONS)],
            'isActive' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer'],
        ];
    }
}
