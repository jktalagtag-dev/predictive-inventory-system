<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class UpsertSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('settings.manage')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'branchId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'valueType' => ['required', 'string', 'in:string,integer,decimal,boolean,json,date'],
            'value' => ['required'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }
}
