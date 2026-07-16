<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Procurement\Services\PurchaseOrderException;
use App\Domains\Procurement\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelPurchaseOrderRequest;
use App\Http\Requests\Api\V1\ClosePurchaseOrderRequest;
use App\Http\Requests\Api\V1\DecidePurchaseOrderRequest;
use App\Http\Requests\Api\V1\MarkOrderedPurchaseOrderRequest;
use App\Http\Requests\Api\V1\StorePurchaseOrderRequest;
use App\Http\Requests\Api\V1\SubmitPurchaseOrderRequest;
use App\Http\Requests\Api\V1\UpdatePurchaseOrderRequest;
use App\Http\Resources\Api\V1\PurchaseOrderResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $purchaseOrderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('purchase_orders.read')) {
            throw new AuthorizationException;
        }

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view purchase orders.']]);
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = PurchaseOrder::query()->with('supplier')->where('branch_id', $branchId);

        if ($request->filled('supplierId')) {
            $query->where('supplier_id', (int) $request->query('supplierId'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where('po_number', 'like', "%{$search}%");
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => PurchaseOrderResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $po = $this->purchaseOrderService->createDraft([
                'branch_id' => $validated['branchId'],
                'supplier_id' => $validated['supplierId'],
                'currency_code' => $validated['currencyCode'],
                'expected_receipt_at' => $validated['expectedReceiptAt'] ?? null,
                'supplier_reference' => $validated['supplierReference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'lines' => array_map(fn ($line) => [
                    'product_id' => $line['productId'],
                    'unit_id' => $line['unitId'],
                    'ordered_quantity' => (string) $line['orderedQuantity'],
                    'unit_cost' => (string) $line['unitCost'],
                    'tax_rate' => isset($line['taxRate']) ? (string) $line['taxRate'] : '0',
                    'discount_amount' => isset($line['discountAmount']) ? (string) $line['discountAmount'] : '0',
                    'expected_receipt_at' => $line['expectedReceiptAt'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ], $validated['lines']),
            ], $request->user());
        } catch (PurchaseOrderException $exception) {
            return $this->exceptionResponse($exception);
        }

        return (new PurchaseOrderResource($po->load('supplier')))->response()->setStatusCode(201);
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        if (! $request->user()->hasPermission('purchase_orders.read') || ! $request->user()->canAccessBranch($purchaseOrder->branch_id)) {
            throw new AuthorizationException;
        }

        return new PurchaseOrderResource($purchaseOrder->load(['supplier', 'lines', 'approvals']));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        if (! $request->user()->canAccessBranch($purchaseOrder->branch_id)) {
            throw new AuthorizationException;
        }

        $validated = $request->validated();

        if ((int) $validated['version'] !== $purchaseOrder->row_version) {
            return $this->conflictResponse('This purchase order was changed by someone else. Reload and try again.');
        }

        try {
            $updated = $this->purchaseOrderService->updateDraft($purchaseOrder, [
                'currency_code' => $validated['currencyCode'] ?? null,
                'expected_receipt_at' => $validated['expectedReceiptAt'] ?? null,
                'supplier_reference' => $validated['supplierReference'] ?? null,
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : null,
                ...(array_key_exists('lines', $validated) ? ['lines' => array_map(fn ($line) => [
                    'product_id' => $line['productId'],
                    'unit_id' => $line['unitId'],
                    'ordered_quantity' => (string) $line['orderedQuantity'],
                    'unit_cost' => (string) $line['unitCost'],
                    'tax_rate' => isset($line['taxRate']) ? (string) $line['taxRate'] : '0',
                    'discount_amount' => isset($line['discountAmount']) ? (string) $line['discountAmount'] : '0',
                    'expected_receipt_at' => $line['expectedReceiptAt'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ], $validated['lines'])] : []),
            ], $request->user());
        } catch (PurchaseOrderException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new PurchaseOrderResource($updated->load('supplier'));
    }

    public function submit(SubmitPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        if (! $request->user()->canAccessBranch($purchaseOrder->branch_id)) {
            throw new AuthorizationException;
        }

        $validated = $request->validated();
        if ((int) $validated['version'] !== $purchaseOrder->row_version) {
            return $this->conflictResponse('This purchase order was changed by someone else. Reload and try again.');
        }

        try {
            $submitted = $this->purchaseOrderService->submit($purchaseOrder, $request->user());
        } catch (PurchaseOrderException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new PurchaseOrderResource($submitted->load('supplier'));
    }

    public function decide(DecidePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        if (! $request->user()->canAccessBranch($purchaseOrder->branch_id)) {
            throw new AuthorizationException;
        }

        $validated = $request->validated();
        if ((int) $validated['version'] !== $purchaseOrder->row_version) {
            return $this->conflictResponse('This purchase order was changed by someone else. Reload and try again.');
        }

        try {
            $decided = $this->purchaseOrderService->decide($purchaseOrder, $validated['decision'], $validated['reason'] ?? null, $request->user());
        } catch (PurchaseOrderException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new PurchaseOrderResource($decided->load('supplier'));
    }

    public function markOrdered(MarkOrderedPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        if (! $request->user()->canAccessBranch($purchaseOrder->branch_id)) {
            throw new AuthorizationException;
        }

        $validated = $request->validated();
        if ((int) $validated['version'] !== $purchaseOrder->row_version) {
            return $this->conflictResponse('This purchase order was changed by someone else. Reload and try again.');
        }

        try {
            $ordered = $this->purchaseOrderService->markOrdered($purchaseOrder, new \DateTimeImmutable($validated['orderedAt']), $validated['supplierReference'] ?? null, $request->user());
        } catch (PurchaseOrderException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new PurchaseOrderResource($ordered->load('supplier'));
    }

    public function cancel(CancelPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        if (! $request->user()->canAccessBranch($purchaseOrder->branch_id)) {
            throw new AuthorizationException;
        }

        $validated = $request->validated();
        if ((int) $validated['version'] !== $purchaseOrder->row_version) {
            return $this->conflictResponse('This purchase order was changed by someone else. Reload and try again.');
        }

        try {
            $cancelled = $this->purchaseOrderService->cancel($purchaseOrder, $validated['reason'], $request->user());
        } catch (PurchaseOrderException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new PurchaseOrderResource($cancelled->load('supplier'));
    }

    public function close(ClosePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        if (! $request->user()->canAccessBranch($purchaseOrder->branch_id)) {
            throw new AuthorizationException;
        }

        $validated = $request->validated();
        if ((int) $validated['version'] !== $purchaseOrder->row_version) {
            return $this->conflictResponse('This purchase order was changed by someone else. Reload and try again.');
        }

        try {
            $closed = $this->purchaseOrderService->close($purchaseOrder, $validated['reason'] ?? null, $request->user());
        } catch (PurchaseOrderException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new PurchaseOrderResource($closed->load('supplier'));
    }

    private function exceptionResponse(PurchaseOrderException $exception): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $exception->errorCode(), 'message' => $exception->getMessage(), 'requestId' => (string) Str::uuid()],
        ], $exception->status());
    }

    private function conflictResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => ['code' => 'VERSION_CONFLICT', 'message' => $message, 'requestId' => (string) Str::uuid()],
        ], 409);
    }
}
