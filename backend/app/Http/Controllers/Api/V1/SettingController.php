<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Governance\Models\Setting;
use App\Domains\Governance\Services\SettingException;
use App\Domains\Governance\Services\SettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpsertSettingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);

        $branchId = $request->filled('branchId') ? (int) $request->query('branchId') : null;

        if ($branchId !== null && ! $request->user()->canAccessBranch($branchId)) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'You are not authorized for that branch.', 'requestId' => $this->correlationId($request)],
            ], 403);
        }

        $settings = $this->settingsService->list($branchId, $request->query('prefix'), $request->user());

        return response()->json([
            'data' => $settings,
            'meta' => ['requestId' => $this->correlationId($request)],
        ]);
    }

    public function show(Request $request, string $settingKey): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);

        $branchId = $request->filled('branchId') ? (int) $request->query('branchId') : null;

        if ($branchId !== null && ! $request->user()->canAccessBranch($branchId)) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'You are not authorized for that branch.', 'requestId' => $this->correlationId($request)],
            ], 403);
        }

        try {
            $setting = $this->settingsService->get($settingKey, $branchId, $request->user());
        } catch (SettingException $exception) {
            return $this->exceptionResponse($exception, $request);
        }

        return response()->json(['data' => $setting, 'meta' => ['requestId' => $this->correlationId($request)]]);
    }

    public function update(UpsertSettingRequest $request, string $settingKey): JsonResponse
    {
        $validated = $request->validated();
        $branchId = array_key_exists('branchId', $validated) ? $validated['branchId'] : null;

        if ($branchId !== null && ! $request->user()->canAccessBranch($branchId)) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'You are not authorized for that branch.', 'requestId' => $this->correlationId($request)],
            ], 403);
        }

        try {
            $setting = $this->settingsService->upsert(
                $settingKey,
                $branchId,
                $validated['valueType'],
                $validated['value'],
                $validated['version'],
                $request->user(),
                $this->correlationId($request),
            );
        } catch (SettingException $exception) {
            return $this->exceptionResponse($exception, $request);
        }

        return response()->json(['data' => $setting, 'meta' => ['requestId' => $this->correlationId($request)]]);
    }

    private function exceptionResponse(SettingException $exception, Request $request): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $exception->errorCode(), 'message' => $exception->getMessage(), 'requestId' => $this->correlationId($request)],
        ], $exception->status());
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }
}
