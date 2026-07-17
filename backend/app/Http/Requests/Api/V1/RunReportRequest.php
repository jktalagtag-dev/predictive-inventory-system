<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Generic filter validation for the interactive report endpoint. Which of
 * these are actually required is determined per-report by ReportCatalog
 * and enforced in ReportRunner — this only bounds the accepted shape and
 * type of each documented filter (REST_API_SPECIFICATION.md section
 * 13.2, "Only documented filters accepted").
 */
class RunReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branchId' => ['sometimes', 'integer', 'min:1'],
            'categoryId' => ['sometimes', 'integer', 'min:1'],
            'isActive' => ['sometimes', 'boolean'],
            'dateFrom' => ['sometimes', 'date'],
            'dateTo' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'max:32'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
