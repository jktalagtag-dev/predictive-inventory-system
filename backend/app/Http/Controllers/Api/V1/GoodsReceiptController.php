<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Models\GoodsReceipt;
use App\Domains\Inventory\Services\GoodsReceiptException;
use App\Domains\Inventory\Services\GoodsReceiptService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PostGoodsReceiptRequest;
use App\Http\Requests\Api\V1\ReverseGoodsReceiptRequest;
use App\Http\Requests\Api\V1\StoreGoodsReceiptRequest;
use App\Http\Requests\Api\V1\UpdateGoodsReceiptRequest;
use App\Http\Resources\Api\V1\GoodsReceiptResource;
use App\Support\Services\IdempotencyConflictException;
use App\Support\Services\IdempotencyGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private readonly GoodsReceiptService $goodsReceiptService,
        private readonly IdempotencyGuard $idempotencyGuard,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view goods receipts.']]);
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = GoodsReceipt::query()->withCount('lines')->where('branch_id', $branchId);

        if ($request->filled('purchaseOrderId')) {
            $query->where('purchase_order_id', (int) $request->query('purchaseOrderId'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('supplierDeliveryNumber')) {
            $query->where('supplier_delivery_number', $request->query('supplierDeliveryNumber'));
        }

        if ($request->filled('from')) {
            $query->where('received_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('received_at', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('received_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => GoodsReceiptResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->authorize('create', [GoodsReceipt::class, (int) $validated['branchId']]);

        try {
            $receipt = $this->goodsReceiptService->createDraft([
                'branch_id' => $validated['branchId'],
                'purchase_order_id' => $validated['purchaseOrderId'],
                'supplier_delivery_number' => $validated['supplierDeliveryNumber'] ?? null,
                'received_at' => $validated['receivedAt'],
                'notes' => $validated['notes'] ?? null,
                'lines' => array_map(fn ($line) => [
                    'purchase_order_line_id' => $line['purchaseOrderLineId'],
                    'received_quantity' => (string) $line['receivedQuantity'],
                    'accepted_quantity' => (string) $line['acceptedQuantity'],
                    'rejected_quantity' => (string) $line['rejectedQuantity'],
                    'unit_cost' => isset($line['unitCost']) ? (string) $line['unitCost'] : null,
                    'lot_number' => $line['lotNumber'] ?? null,
                    'serial_number' => $line['serialNumber'] ?? null,
                    'expiry_date' => $line['expiryDate'] ?? null,
                    'rejection_reason' => $line['rejectionReason'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ], $validated['lines']),
            ], $request->user(), $this->correlationId($request));
        } catch (GoodsReceiptException $exception) {
            return $this->exceptionResponse($exception);
        }

        return (new GoodsReceiptResource($receipt))->response()->setStatusCode(201);
    }

    public function show(Request $request, GoodsReceipt $goodsReceipt): GoodsReceiptResource
    {
        $this->authorize('view', $goodsReceipt);

        return new GoodsReceiptResource($goodsReceipt->load('lines'));
    }

    public function update(UpdateGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): GoodsReceiptResource|JsonResponse
    {
        $this->authorize('update', $goodsReceipt);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $goodsReceipt->row_version) {
            return $this->conflictResponse('This goods receipt was changed by someone else. Reload and try again.');
        }

        try {
            $updated = $this->goodsReceiptService->updateDraft($goodsReceipt, [
                'supplier_delivery_number' => array_key_exists('supplierDeliveryNumber', $validated) ? $validated['supplierDeliveryNumber'] : null,
                'received_at' => $validated['receivedAt'] ?? null,
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : null,
                ...(array_key_exists('lines', $validated) ? ['lines' => array_map(fn ($line) => [
                    'purchase_order_line_id' => $line['purchaseOrderLineId'],
                    'received_quantity' => (string) $line['receivedQuantity'],
                    'accepted_quantity' => (string) $line['acceptedQuantity'],
                    'rejected_quantity' => (string) $line['rejectedQuantity'],
                    'unit_cost' => isset($line['unitCost']) ? (string) $line['unitCost'] : null,
                    'lot_number' => $line['lotNumber'] ?? null,
                    'serial_number' => $line['serialNumber'] ?? null,
                    'expiry_date' => $line['expiryDate'] ?? null,
                    'rejection_reason' => $line['rejectionReason'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ], $validated['lines'])] : []),
            ], $request->user(), $this->correlationId($request));
        } catch (GoodsReceiptException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new GoodsReceiptResource($updated);
    }

    public function post(PostGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('post', $goodsReceipt);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to post a goods receipt.']]);
        }

        $validated = $request->validated();
        $correlationId = $this->correlationId($request);

        try {
            $replay = $this->idempotencyGuard->begin(
                $request->user(),
                'goods_receipts.post',
                $idempotencyKey,
                ['goodsReceiptId' => $goodsReceipt->id, 'version' => $validated['version']],
                $correlationId,
            );
        } catch (IdempotencyConflictException $exception) {
            return $this->idempotencyExceptionResponse($exception);
        }

        if ($replay !== null) {
            return response()->json($replay);
        }

        if ((int) $validated['version'] !== $goodsReceipt->row_version) {
            return $this->conflictResponse('This goods receipt was changed by someone else. Reload and try again.');
        }

        try {
            $posted = $this->goodsReceiptService->post($goodsReceipt, $request->user(), $correlationId);
        } catch (GoodsReceiptException $exception) {
            return $this->exceptionResponse($exception);
        }

        $responseBody = ['data' => (new GoodsReceiptResource($posted))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'goods_receipts.post', $idempotencyKey, 200, $responseBody, 'goods_receipt', $posted->id);

        return response()->json($responseBody);
    }

    public function reverse(ReverseGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('reverse', $goodsReceipt);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to reverse a goods receipt.']]);
        }

        $validated = $request->validated();
        $correlationId = $this->correlationId($request);

        try {
            $replay = $this->idempotencyGuard->begin(
                $request->user(),
                'goods_receipts.reverse',
                $idempotencyKey,
                ['goodsReceiptId' => $goodsReceipt->id, 'version' => $validated['version'], 'reason' => $validated['reason']],
                $correlationId,
            );
        } catch (IdempotencyConflictException $exception) {
            return $this->idempotencyExceptionResponse($exception);
        }

        if ($replay !== null) {
            return response()->json($replay);
        }

        if ((int) $validated['version'] !== $goodsReceipt->row_version) {
            return $this->conflictResponse('This goods receipt was changed by someone else. Reload and try again.');
        }

        try {
            $reversed = $this->goodsReceiptService->reverse($goodsReceipt, $validated['reason'], $request->user(), $correlationId);
        } catch (GoodsReceiptException $exception) {
            return $this->exceptionResponse($exception);
        }

        $responseBody = ['data' => (new GoodsReceiptResource($reversed))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'goods_receipts.reverse', $idempotencyKey, 200, $responseBody, 'goods_receipt', $reversed->id);

        return response()->json($responseBody);
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }

    private function exceptionResponse(GoodsReceiptException $exception): JsonResponse
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
