<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Models\InventoryAdjustment;
use App\Domains\Inventory\Services\InventoryAdjustmentException;
use App\Domains\Inventory\Services\InventoryAdjustmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApproveInventoryAdjustmentRequest;
use App\Http\Requests\Api\V1\PostInventoryAdjustmentRequest;
use App\Http\Requests\Api\V1\ReverseInventoryAdjustmentRequest;
use App\Http\Requests\Api\V1\StoreInventoryAdjustmentRequest;
use App\Http\Requests\Api\V1\UpdateInventoryAdjustmentRequest;
use App\Http\Resources\Api\V1\InventoryAdjustmentResource;
use App\Support\Services\IdempotencyConflictException;
use App\Support\Services\IdempotencyGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentController extends Controller
{
    public function __construct(
        private readonly InventoryAdjustmentService $adjustmentService,
        private readonly IdempotencyGuard $idempotencyGuard,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryAdjustment::class);

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view adjustments.']]);
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = InventoryAdjustment::query()->where('branch_id', $branchId);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('reasonCode')) {
            $query->where('reason_code', $request->query('reasonCode'));
        }

        if ($request->filled('from')) {
            $query->where('effective_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('effective_at', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('effective_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => InventoryAdjustmentResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->authorize('create', [InventoryAdjustment::class, (int) $validated['branchId']]);

        try {
            $adjustment = $this->adjustmentService->createDraft([
                'branch_id' => $validated['branchId'],
                'reason_code' => $validated['reasonCode'],
                'reason_note' => $validated['reasonNote'] ?? null,
                'effective_at' => $validated['effectiveAt'],
                'lines' => array_map(fn ($line) => [
                    'product_id' => $line['productId'],
                    'quantity_delta' => (string) $line['quantityDelta'],
                    'unit_cost' => isset($line['unitCost']) ? (string) $line['unitCost'] : null,
                    'notes' => $line['notes'] ?? null,
                ], $validated['lines']),
            ], $request->user(), $this->correlationId($request));
        } catch (InventoryAdjustmentException $exception) {
            return $this->exceptionResponse($exception);
        }

        return (new InventoryAdjustmentResource($adjustment))->response()->setStatusCode(201);
    }

    public function show(Request $request, InventoryAdjustment $adjustment): InventoryAdjustmentResource
    {
        $this->authorize('view', $adjustment);

        return new InventoryAdjustmentResource($adjustment->load('lines'));
    }

    public function update(UpdateInventoryAdjustmentRequest $request, InventoryAdjustment $adjustment): InventoryAdjustmentResource|JsonResponse
    {
        $this->authorize('update', $adjustment);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $adjustment->row_version) {
            return $this->conflictResponse('This adjustment was changed by someone else. Reload and try again.');
        }

        try {
            $updated = $this->adjustmentService->updateDraft($adjustment, [
                'reason_code' => $validated['reasonCode'] ?? null,
                'reason_note' => array_key_exists('reasonNote', $validated) ? $validated['reasonNote'] : null,
                'effective_at' => $validated['effectiveAt'] ?? null,
                ...(array_key_exists('lines', $validated) ? ['lines' => array_map(fn ($line) => [
                    'product_id' => $line['productId'],
                    'quantity_delta' => (string) $line['quantityDelta'],
                    'unit_cost' => isset($line['unitCost']) ? (string) $line['unitCost'] : null,
                    'notes' => $line['notes'] ?? null,
                ], $validated['lines'])] : []),
            ], $request->user(), $this->correlationId($request));
        } catch (InventoryAdjustmentException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new InventoryAdjustmentResource($updated);
    }

    public function approve(ApproveInventoryAdjustmentRequest $request, InventoryAdjustment $adjustment): InventoryAdjustmentResource|JsonResponse
    {
        $this->authorize('approve', $adjustment);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $adjustment->row_version) {
            return $this->conflictResponse('This adjustment was changed by someone else. Reload and try again.');
        }

        try {
            $approved = $this->adjustmentService->approve($adjustment, $request->user(), $this->correlationId($request));
        } catch (InventoryAdjustmentException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new InventoryAdjustmentResource($approved);
    }

    public function post(PostInventoryAdjustmentRequest $request, InventoryAdjustment $adjustment): JsonResponse
    {
        $this->authorize('post', $adjustment);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to post an adjustment.']]);
        }

        $validated = $request->validated();
        $correlationId = $request->attributes->get('correlation_id') ?? (string) Str::uuid();

        try {
            $replay = $this->idempotencyGuard->begin(
                $request->user(),
                'inventory.adjustments.post',
                $idempotencyKey,
                ['adjustmentId' => $adjustment->id, 'version' => $validated['version']],
                $correlationId,
            );
        } catch (IdempotencyConflictException $exception) {
            return $this->idempotencyExceptionResponse($exception);
        }

        if ($replay !== null) {
            return response()->json($replay);
        }

        if ((int) $validated['version'] !== $adjustment->row_version) {
            return $this->conflictResponse('This adjustment was changed by someone else. Reload and try again.');
        }

        try {
            $posted = $this->adjustmentService->post($adjustment, $request->user(), $correlationId);
        } catch (InventoryAdjustmentException $exception) {
            return $this->exceptionResponse($exception);
        }

        $responseBody = ['data' => (new InventoryAdjustmentResource($posted))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'inventory.adjustments.post', $idempotencyKey, 200, $responseBody, 'inventory_adjustment', $posted->id);

        return response()->json($responseBody);
    }

    public function reverse(ReverseInventoryAdjustmentRequest $request, InventoryAdjustment $adjustment): JsonResponse
    {
        $this->authorize('reverse', $adjustment);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to reverse an adjustment.']]);
        }

        $validated = $request->validated();
        $correlationId = $request->attributes->get('correlation_id') ?? (string) Str::uuid();

        try {
            $replay = $this->idempotencyGuard->begin(
                $request->user(),
                'inventory.adjustments.reverse',
                $idempotencyKey,
                ['adjustmentId' => $adjustment->id, 'version' => $validated['version'], 'reason' => $validated['reason']],
                $correlationId,
            );
        } catch (IdempotencyConflictException $exception) {
            return $this->idempotencyExceptionResponse($exception);
        }

        if ($replay !== null) {
            return response()->json($replay);
        }

        if ((int) $validated['version'] !== $adjustment->row_version) {
            return $this->conflictResponse('This adjustment was changed by someone else. Reload and try again.');
        }

        try {
            $reversed = $this->adjustmentService->reverse($adjustment, $validated['reason'], $request->user(), $correlationId);
        } catch (InventoryAdjustmentException $exception) {
            return $this->exceptionResponse($exception);
        }

        $responseBody = ['data' => (new InventoryAdjustmentResource($reversed))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'inventory.adjustments.reverse', $idempotencyKey, 200, $responseBody, 'inventory_adjustment', $reversed->id);

        return response()->json($responseBody);
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }

    private function exceptionResponse(InventoryAdjustmentException $exception): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
                'requestId' => (string) Str::uuid(),
            ],
        ], $exception->status());
    }

    private function idempotencyExceptionResponse(IdempotencyConflictException $exception): JsonResponse
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
