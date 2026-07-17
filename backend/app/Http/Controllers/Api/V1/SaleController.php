<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Catalog\Models\Product;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Services\SaleException;
use App\Domains\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FinalizeSaleRequest;
use App\Http\Requests\Api\V1\RefundSaleRequest;
use App\Http\Requests\Api\V1\VoidSaleRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\SaleResource;
use App\Support\Services\IdempotencyConflictException;
use App\Support\Services\IdempotencyGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly IdempotencyGuard $idempotencyGuard,
    ) {
    }

    /**
     * GET /pos/products — sale-eligible product lookup for checkout
     * (REST_API_SPECIFICATION.md section 11.1).
     */
    public function posProducts(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('pos.use')) {
            throw new AuthorizationException;
        }

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['MISSING_BRANCH_SCOPE: A branch is required to look up sale-eligible products.']]);
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = Product::query()->with(['category', 'stockUnit'])->where('is_active', true);

        if ($barcode = trim((string) $request->query('barcode', ''))) {
            $query->where('barcode', $barcode);
        } elseif ($search = trim((string) $request->query('query', ''))) {
            if (mb_strlen($search) > 120) {
                throw ValidationException::withMessages(['query' => ['Search text is too long.']]);
            }
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        $productIds = array_map(fn (Product $product) => $product->id, $paginator->items());
        $balances = InventoryBalance::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        foreach ($paginator->items() as $product) {
            $product->stock_snapshot = $balances->get($product->id);
        }

        return response()->json([
            'data' => ProductResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view sales.']]);
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = Sale::query()->withCount('lines')->with('cashier')->where('branch_id', $branchId);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('cashierUserId')) {
            $query->where('cashier_user_id', (int) $request->query('cashierUserId'));
        }

        if ($request->filled('saleNumber')) {
            $query->where('sale_number', $request->query('saleNumber'));
        }

        if ($request->filled('from')) {
            $query->where('sold_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('sold_at', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('sold_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => SaleResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, Sale $sale): SaleResource
    {
        $this->authorize('view', $sale);

        return new SaleResource($sale->load(['lines', 'payments', 'cashier', 'reversesSale']));
    }

    public function store(FinalizeSaleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to finalize a sale.']]);
        }

        $correlationId = $this->correlationId($request);

        try {
            $replay = $this->idempotencyGuard->begin($request->user(), 'sales.finalize', $idempotencyKey, $validated, $correlationId);
        } catch (IdempotencyConflictException $exception) {
            return $this->idempotencyExceptionResponse($exception);
        }

        if ($replay !== null) {
            // The original successful response was 201 Created; a replay
            // must report the same outcome, not the response()->json()
            // default of 200.
            return response()->json($replay, 201);
        }

        try {
            $sale = $this->saleService->finalize([
                'branch_id' => $validated['branchId'],
                'sold_at' => $validated['soldAt'],
                'currency_code' => $validated['currencyCode'],
                'notes' => $validated['notes'] ?? null,
                'approved_by_user_id' => $validated['approvedByUserId'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'lines' => array_map(fn ($line) => [
                    'product_id' => $line['productId'],
                    'unit_id' => $line['productUnitId'],
                    'quantity' => $line['quantity'],
                    'requested_unit_price' => $line['unitPrice'] ?? null,
                    'discount_amount' => $line['discountAmount'] ?? null,
                    'override_reason' => $line['overrideReason'] ?? null,
                ], $validated['lines']),
                'payments' => array_map(fn ($payment) => [
                    'payment_method' => $payment['paymentMethod'],
                    'amount' => $payment['amount'],
                    'external_reference' => $payment['externalReference'] ?? null,
                ], $validated['payments']),
            ], $request->user(), $correlationId);
        } catch (SaleException $exception) {
            return $this->exceptionResponse($exception);
        }

        $responseBody = ['data' => (new SaleResource($sale))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'sales.finalize', $idempotencyKey, 201, $responseBody, 'sale', $sale->id);

        return response()->json($responseBody, 201);
    }

    public function void(VoidSaleRequest $request, Sale $sale): JsonResponse
    {
        $this->authorize('void', $sale);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to void a sale.']]);
        }

        $validated = $request->validated();
        $correlationId = $this->correlationId($request);

        try {
            $replay = $this->idempotencyGuard->begin(
                $request->user(), 'sales.void', $idempotencyKey,
                ['saleId' => $sale->id, 'version' => $validated['version'], 'reason' => $validated['reason']],
                $correlationId,
            );
        } catch (IdempotencyConflictException $exception) {
            return $this->idempotencyExceptionResponse($exception);
        }

        if ($replay !== null) {
            return response()->json($replay);
        }

        if ((int) $validated['version'] !== $sale->row_version) {
            return $this->conflictResponse('This sale was changed by someone else. Reload and try again.');
        }

        try {
            $voided = $this->saleService->void($sale, $validated['reason'], $request->user(), $correlationId);
        } catch (SaleException $exception) {
            return $this->exceptionResponse($exception);
        }

        $responseBody = ['data' => (new SaleResource($voided))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'sales.void', $idempotencyKey, 200, $responseBody, 'sale', $voided->id);

        return response()->json($responseBody);
    }

    public function refund(RefundSaleRequest $request, Sale $sale): JsonResponse
    {
        $this->authorize('refund', $sale);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            throw ValidationException::withMessages(['idempotencyKey' => ['An Idempotency-Key header is required to refund a sale.']]);
        }

        $validated = $request->validated();
        $correlationId = $this->correlationId($request);

        try {
            $replay = $this->idempotencyGuard->begin($request->user(), 'sales.refund', $idempotencyKey, $validated + ['saleId' => $sale->id], $correlationId);
        } catch (IdempotencyConflictException $exception) {
            return $this->idempotencyExceptionResponse($exception);
        }

        if ($replay !== null) {
            // The original successful response was 201 Created; a replay
            // must report the same outcome, not the response()->json()
            // default of 200.
            return response()->json($replay, 201);
        }

        if ((int) $validated['version'] !== $sale->row_version) {
            return $this->conflictResponse('This sale was changed by someone else. Reload and try again.');
        }

        try {
            $refundSale = $this->saleService->refund($sale, [
                'reason' => $validated['reason'],
                'idempotency_key' => $idempotencyKey,
                'lines' => array_map(fn ($line) => ['product_id' => $line['productId'], 'quantity' => $line['quantity']], $validated['lines']),
                'payments' => array_map(fn ($payment) => [
                    'payment_method' => $payment['paymentMethod'],
                    'amount' => $payment['amount'],
                    'external_reference' => $payment['externalReference'] ?? null,
                ], $validated['payments']),
            ], $request->user(), $correlationId);
        } catch (SaleException $exception) {
            return $this->exceptionResponse($exception);
        }

        $responseBody = ['data' => (new SaleResource($refundSale->load('reversesSale')))->response()->getData(true)['data']];
        $this->idempotencyGuard->complete($request->user(), 'sales.refund', $idempotencyKey, 201, $responseBody, 'sale', $refundSale->id);

        return response()->json($responseBody, 201);
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }

    private function exceptionResponse(SaleException $exception): JsonResponse
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
