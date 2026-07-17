<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Planning\Models\ForecastRun;
use App\Domains\Planning\Services\SmaForecastService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateForecastRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasPermission('forecasting.run')) {
            throw new AuthorizationException;
        }

        if (! $this->user()->canAccessBranch((int) $this->input('branchId'))) {
            throw new AuthorizationException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'branchId' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'modelCode' => ['required', 'string', Rule::in(['sma'])],
            'periodGrain' => ['required', 'string', Rule::in(ForecastRun::PERIOD_GRAINS)],
            'windowPeriods' => ['required', 'integer', 'min:'.SmaForecastService::MIN_WINDOW_PERIODS, 'max:'.SmaForecastService::MAX_WINDOW_PERIODS],
            'historyStartDate' => ['required', 'date'],
            'historyEndDate' => ['required', 'date', 'after_or_equal:historyStartDate'],
            'productIds' => ['nullable', 'array'],
            'productIds.*' => ['integer', Rule::exists('products', 'id')->where('is_active', true)],
        ];
    }
}
