<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Planning\Models\ReorderPolicy;
use App\Domains\Planning\Services\EoqService;
use App\Domains\Planning\Services\PlanningException;
use App\Domains\Planning\Services\RopService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CalculateEoqRequest;
use App\Http\Requests\Api\V1\RecalculateRopRequest;
use App\Http\Requests\Api\V1\StoreReorderPolicyRequest;
use App\Http\Requests\Api\V1\UpdateReorderPolicyRequest;
use App\Http\Resources\Api\V1\EoqCalculationResource;
use App\Http\Resources\Api\V1\ReorderPolicyResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReorderPolicyController extends Controller
{
    public function __construct(
        private readonly RopService $ropService,
        private readonly EoqService $eoqService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReorderPolicy::class);

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view reorder policies.']]);
        }

        $branchId = (int) $request->query('branchId');
        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = ReorderPolicy::query()->with(['product', 'preferredSupplier'])->where('branch_id', $branchId);

        if ($request->filled('productId')) {
            $query->where('product_id', (int) $request->query('productId'));
        }
        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => ReorderPolicyResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreReorderPolicyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (ReorderPolicy::query()->where('branch_id', $validated['branchId'])->where('product_id', $validated['productId'])->exists()) {
            return response()->json([
                'error' => ['code' => 'DUPLICATE_REORDER_POLICY', 'message' => 'A reorder policy already exists for this product at this branch.', 'requestId' => (string) Str::uuid()],
            ], 409);
        }

        $policy = ReorderPolicy::query()->create([
            'branch_id' => $validated['branchId'],
            'product_id' => $validated['productId'],
            'preferred_supplier_id' => $validated['preferredSupplierId'] ?? null,
            'safety_stock_quantity' => $validated['safetyStockQuantity'],
            'safety_stock_basis' => $validated['safetyStockBasis'],
            'lead_time_days_override' => $validated['leadTimeDaysOverride'] ?? null,
            'lead_time_basis' => $validated['leadTimeBasis'],
            'is_active' => $validated['isActive'] ?? true,
            'row_version' => 1,
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);

        return (new ReorderPolicyResource($policy->load(['product', 'preferredSupplier'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, ReorderPolicy $reorderPolicy): ReorderPolicyResource
    {
        $this->authorize('view', $reorderPolicy);

        return new ReorderPolicyResource($reorderPolicy->load(['product', 'preferredSupplier']));
    }

    public function update(UpdateReorderPolicyRequest $request, ReorderPolicy $reorderPolicy): ReorderPolicyResource|JsonResponse
    {
        $this->authorize('update', $reorderPolicy);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $reorderPolicy->row_version) {
            return $this->conflictResponse('This reorder policy was changed by someone else. Reload and try again.');
        }

        $fieldMap = [
            'preferredSupplierId' => 'preferred_supplier_id',
            'safetyStockQuantity' => 'safety_stock_quantity',
            'safetyStockBasis' => 'safety_stock_basis',
            'leadTimeDaysOverride' => 'lead_time_days_override',
            'leadTimeBasis' => 'lead_time_basis',
            'isActive' => 'is_active',
        ];

        foreach ($fieldMap as $requestField => $column) {
            if (array_key_exists($requestField, $validated)) {
                $reorderPolicy->{$column} = $validated[$requestField];
            }
        }

        $reorderPolicy->updated_by_user_id = $request->user()->id;
        $reorderPolicy->row_version = $reorderPolicy->row_version + 1;
        $reorderPolicy->save();

        return new ReorderPolicyResource($reorderPolicy->load(['product', 'preferredSupplier']));
    }

    public function recalculateRop(RecalculateRopRequest $request, ReorderPolicy $reorderPolicy): ReorderPolicyResource|JsonResponse
    {
        $this->authorize('recalculate', $reorderPolicy);

        $validated = $request->validated();

        try {
            $updated = $this->ropService->recalculate($reorderPolicy, $validated['forecastRunId'] ?? null, $request->user());
        } catch (PlanningException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new ReorderPolicyResource($updated->load(['product', 'preferredSupplier']));
    }

    public function calculateEoq(CalculateEoqRequest $request, ReorderPolicy $reorderPolicy): JsonResponse
    {
        $this->authorize('calculateEoq', $reorderPolicy);

        $validated = $request->validated();

        $calculation = $this->eoqService->calculate($reorderPolicy, [
            'annual_demand_quantity' => (string) $validated['annualDemandQuantity'],
            'ordering_cost' => (string) $validated['orderingCost'],
            'annual_holding_cost_per_unit' => (string) $validated['annualHoldingCostPerUnit'],
            'currency_code' => $validated['currencyCode'],
        ], $request->user());

        return (new EoqCalculationResource($calculation))->response()->setStatusCode(201);
    }

    public function listEoq(Request $request, ReorderPolicy $reorderPolicy): JsonResponse
    {
        $this->authorize('viewEoq', $reorderPolicy);

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $paginator = $reorderPolicy->eoqCalculations()->orderByDesc('calculated_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => EoqCalculationResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
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

    private function conflictResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => ['code' => 'VERSION_CONFLICT', 'message' => $message, 'requestId' => (string) Str::uuid()],
        ], 409);
    }
}
