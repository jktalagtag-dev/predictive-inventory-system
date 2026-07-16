<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Procurement\Models\Supplier;
use App\Domains\Procurement\Services\SupplierException;
use App\Domains\Procurement\Services\SupplierService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSupplierRequest;
use App\Http\Requests\Api\V1\UpdateSupplierRequest;
use App\Http\Resources\Api\V1\SupplierResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $supplierService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('suppliers.read')) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = Supplier::query();

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('legal_name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        $paginator = $query->orderBy('legal_name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => SupplierResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $supplier = $this->supplierService->create([
                'code' => $validated['code'],
                'legal_name' => $validated['legalName'],
                'tax_identifier' => $validated['taxIdentifier'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address_line_1' => $validated['addressLine1'] ?? null,
                'address_line_2' => $validated['addressLine2'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'postal_code' => $validated['postalCode'] ?? null,
                'country_code' => $validated['countryCode'],
                'default_currency_code' => $validated['defaultCurrencyCode'],
                'is_active' => $validated['isActive'] ?? true,
            ], $request->user());
        } catch (SupplierException $exception) {
            return $this->exceptionResponse($exception);
        }

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    public function show(Request $request, Supplier $supplier): SupplierResource
    {
        if (! $request->user()->hasPermission('suppliers.read')) {
            throw new AuthorizationException;
        }

        return new SupplierResource($supplier->load('contacts'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource|JsonResponse
    {
        $validated = $request->validated();

        if ((int) $validated['version'] !== $supplier->row_version) {
            return $this->conflictResponse('This supplier was changed by someone else. Reload and try again.');
        }

        $fieldMap = [
            'code' => 'code', 'legalName' => 'legal_name', 'taxIdentifier' => 'tax_identifier',
            'email' => 'email', 'phone' => 'phone', 'addressLine1' => 'address_line_1',
            'addressLine2' => 'address_line_2', 'city' => 'city', 'province' => 'province',
            'postalCode' => 'postal_code', 'countryCode' => 'country_code',
            'defaultCurrencyCode' => 'default_currency_code', 'isActive' => 'is_active',
        ];

        $attributes = [];
        foreach ($fieldMap as $requestField => $column) {
            if (array_key_exists($requestField, $validated)) {
                $attributes[$column] = $validated[$requestField];
            }
        }

        try {
            $updated = $this->supplierService->update($supplier, $attributes, $request->user());
        } catch (SupplierException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new SupplierResource($updated);
    }

    private function exceptionResponse(SupplierException $exception): JsonResponse
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
