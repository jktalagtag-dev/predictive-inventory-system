<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Planning\Models\ForecastRun;
use App\Domains\Planning\Services\PlanningException;
use App\Domains\Planning\Services\SmaForecastService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateForecastRunRequest;
use App\Http\Requests\Api\V1\RecordManualPlanRequest;
use App\Http\Resources\Api\V1\ForecastRunResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForecastRunController extends Controller
{
    public function __construct(private readonly SmaForecastService $forecastService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ForecastRun::class);

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view forecast runs.']]);
        }

        $branchId = (int) $request->query('branchId');
        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = ForecastRun::query()->withCount('items')->where('branch_id', $branchId);

        if ($request->filled('modelCode')) {
            $query->where('model_code', $request->query('modelCode'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('from')) {
            $query->where('history_start_date', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->where('history_end_date', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => ForecastRunResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(CreateForecastRunRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $run = $this->forecastService->createRun([
                'branch_id' => $validated['branchId'],
                'period_grain' => $validated['periodGrain'],
                'window_periods' => $validated['windowPeriods'],
                'history_start_date' => $validated['historyStartDate'],
                'history_end_date' => $validated['historyEndDate'],
                'product_ids' => $validated['productIds'] ?? null,
            ], $request->user());
        } catch (PlanningException $exception) {
            return $this->exceptionResponse($exception);
        }

        return (new ForecastRunResource($run))->response()->setStatusCode(201);
    }

    public function show(Request $request, ForecastRun $forecastRun): ForecastRunResource
    {
        $this->authorize('view', $forecastRun);

        return new ForecastRunResource($forecastRun->load('items'));
    }

    public function showItem(Request $request, ForecastRun $forecastRun, int $productId): JsonResponse
    {
        $this->authorize('view', $forecastRun);

        $item = $forecastRun->items()->where('product_id', $productId)->first();

        if (! $item) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'This product does not belong to this forecast run.', 'requestId' => (string) Str::uuid()],
            ], 404);
        }

        return response()->json(['data' => [
            'productId' => (string) $item->product_id,
            'productSku' => $item->product_sku_snapshot,
            'productName' => $item->product_name_snapshot,
            'historyPeriodCount' => $item->history_period_count,
            'demandTotal' => (string) $item->demand_total,
            'forecastQuantity' => $item->forecast_quantity !== null ? (string) $item->forecast_quantity : null,
            'coldStartStatus' => $item->cold_start_status,
            'manualQuantity' => $item->manual_quantity !== null ? (string) $item->manual_quantity : null,
            'manualReason' => $item->manual_reason,
            'manualExpiresAt' => optional($item->manual_expires_at)->toIso8601String(),
            'inputSnapshot' => $item->input_snapshot,
            'dataCutoffAt' => optional($forecastRun->data_cutoff_at)->toIso8601String(),
            'modelVersion' => $forecastRun->model_version,
        ]]);
    }

    public function manualPlan(RecordManualPlanRequest $request, ForecastRun $forecastRun, int $productId): JsonResponse
    {
        $this->authorize('override', $forecastRun);

        $item = $forecastRun->items()->where('product_id', $productId)->first();
        if (! $item) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'This product does not belong to this forecast run.', 'requestId' => (string) Str::uuid()],
            ], 404);
        }

        $validated = $request->validated();

        try {
            $updated = $this->forecastService->recordManualPlan(
                $item,
                (string) $validated['manualQuantity'],
                $validated['reason'],
                $validated['expiresAt'],
            );
        } catch (PlanningException $exception) {
            return $this->exceptionResponse($exception);
        }

        return response()->json(['data' => [
            'productId' => (string) $updated->product_id,
            'manualQuantity' => (string) $updated->manual_quantity,
            'manualReason' => $updated->manual_reason,
            'manualExpiresAt' => optional($updated->manual_expires_at)->toIso8601String(),
            'coldStartStatus' => $updated->cold_start_status,
        ]]);
    }

    private function exceptionResponse(PlanningException $exception): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
                'requestId' => (string) Str::uuid(),
            ],
        ], $exception->status());
    }
}
