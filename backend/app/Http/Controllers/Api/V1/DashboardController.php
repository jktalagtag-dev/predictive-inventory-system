<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Dashboard\Services\DashboardException;
use App\Domains\Dashboard\Services\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ShowDashboardRequest;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function show(ShowDashboardRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $correlationId = $request->attributes->get('correlation_id') ?? (string) Str::uuid();

        $branchId = (int) $validated['branchId'];

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $timezone = $validated['timezone'] ?? config('app.timezone');

        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to'], $timezone) : CarbonImmutable::now($timezone);
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from'], $timezone) : $to->subDays(29);

        $generatedAt = CarbonImmutable::now();

        try {
            $data = $this->dashboardService->build($branchId, $from, $to, $timezone);
        } catch (DashboardException $exception) {
            return $this->errorResponse($exception->errorCode(), $exception->getMessage(), $exception->status(), $correlationId);
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'requestId' => $correlationId,
                'branchId' => (string) $branchId,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'timezone' => $timezone,
                'currency' => 'PHP',
                'generatedAt' => $generatedAt->toIso8601String(),
                'freshness' => 'live',
            ],
        ]);
    }

    private function errorResponse(string $code, string $message, int $status, string $correlationId): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message, 'requestId' => $correlationId],
        ], $status);
    }
}
