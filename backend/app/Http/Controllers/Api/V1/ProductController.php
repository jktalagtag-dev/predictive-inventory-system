<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Services\ProductException;
use App\Domains\Catalog\Services\ProductService;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = Product::query()->with(['category', 'stockUnit']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categoryId')) {
            $query->where('category_id', (int) $request->query('categoryId'));
        }

        if ($request->filled('productType')) {
            $query->where('product_type', $request->query('productType'));
        }

        if ($request->filled('barcode')) {
            $query->where('barcode', $request->query('barcode'));
        }

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        $paginator = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        $this->attachStockSnapshots($request, $paginator->items());

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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $product = $this->productService->create([
                'category_id' => $validated['categoryId'],
                'stock_unit_id' => $validated['stockUnitId'],
                'sku' => $validated['sku'],
                'barcode' => $validated['barcode'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'product_type' => $validated['productType'],
                'is_active' => $validated['isActive'] ?? true,
                'is_lot_tracked' => $validated['isLotTracked'] ?? false,
                'is_serial_tracked' => $validated['isSerialTracked'] ?? false,
                'is_expiry_tracked' => $validated['isExpiryTracked'] ?? false,
                'default_tax_rate' => $validated['defaultTaxRate'],
            ], $request->user());
        } catch (ProductException $exception) {
            return $this->exceptionResponse($exception);
        }

        return (new ProductResource($product->load(['category', 'stockUnit'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $this->authorize('view', $product);

        $product->load(['category', 'stockUnit']);
        $this->attachStockSnapshots($request, [$product]);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource|JsonResponse
    {
        $validated = $request->validated();

        if ((int) $validated['version'] !== $product->row_version) {
            return $this->conflictResponse('This product was changed by someone else. Reload and try again.');
        }

        $fieldMap = [
            'categoryId' => 'category_id',
            'stockUnitId' => 'stock_unit_id',
            'sku' => 'sku',
            'barcode' => 'barcode',
            'name' => 'name',
            'description' => 'description',
            'productType' => 'product_type',
            'isActive' => 'is_active',
            'isLotTracked' => 'is_lot_tracked',
            'isSerialTracked' => 'is_serial_tracked',
            'isExpiryTracked' => 'is_expiry_tracked',
            'defaultTaxRate' => 'default_tax_rate',
        ];

        $attributes = [];
        foreach ($fieldMap as $requestField => $column) {
            if (array_key_exists($requestField, $validated)) {
                $attributes[$column] = $validated[$requestField];
            }
        }

        try {
            $updated = $this->productService->update($product, $attributes, $request->user());
        } catch (ProductException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new ProductResource($updated->load(['category', 'stockUnit']));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $version = (int) $request->query('version', -1);
        if ($version !== $product->row_version) {
            return $this->conflictResponse('This product was changed by someone else. Reload and try again.');
        }

        $this->productService->archive($product, $request->user());

        return response()->json(['data' => ['archived' => true]]);
    }

    /**
     * Embeds a branch-scoped stock snapshot on each product when the caller
     * asked for one, has inventory visibility, and is authorized for that
     * branch. Silently omitted otherwise — never fabricated.
     *
     * @param  array<int, Product>  $products
     */
    private function attachStockSnapshots(Request $request, array $products): void
    {
        if (! $request->filled('branchId') || ! $request->user()->hasPermission('inventory.read')) {
            return;
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId) || $products === []) {
            return;
        }

        $productIds = array_map(fn (Product $product) => $product->id, $products);
        $balances = InventoryBalance::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        foreach ($products as $product) {
            $product->stock_snapshot = $balances->get($product->id);
        }
    }

    private function exceptionResponse(ProductException $exception): JsonResponse
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
