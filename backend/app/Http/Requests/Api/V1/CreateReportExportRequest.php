<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class CreateReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('reports.export')) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'reportCode' => ['required', 'string', 'max:120'],
            'format' => ['required', 'string', 'in:pdf,csv,xlsx'],
            'branchId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'filters' => ['sometimes', 'array'],
            'timezone' => ['sometimes', 'string', 'max:64'],
        ];
    }
}
