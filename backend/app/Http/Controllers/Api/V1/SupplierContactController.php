<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Procurement\Models\Supplier;
use App\Domains\Procurement\Models\SupplierContact;
use App\Domains\Procurement\Services\SupplierService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSupplierContactRequest;
use App\Http\Requests\Api\V1\UpdateSupplierContactRequest;
use App\Http\Resources\Api\V1\SupplierContactResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SupplierContactController extends Controller
{
    public function __construct(private readonly SupplierService $supplierService)
    {
    }

    public function store(StoreSupplierContactRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $validated = $request->validated();

        $contact = $this->supplierService->createContact($supplier, [
            'full_name' => $validated['fullName'],
            'job_title' => $validated['jobTitle'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_primary' => $validated['isPrimary'] ?? false,
            'is_active' => $validated['isActive'] ?? true,
        ], $request->user());

        return (new SupplierContactResource($contact))->response()->setStatusCode(201);
    }

    public function update(UpdateSupplierContactRequest $request, Supplier $supplier, SupplierContact $contact): SupplierContactResource|JsonResponse
    {
        $this->authorize('update', $supplier);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $contact->row_version) {
            return response()->json([
                'error' => ['code' => 'VERSION_CONFLICT', 'message' => 'This contact was changed by someone else. Reload and try again.', 'requestId' => (string) Str::uuid()],
            ], 409);
        }

        $fieldMap = ['fullName' => 'full_name', 'jobTitle' => 'job_title', 'email' => 'email', 'phone' => 'phone', 'isPrimary' => 'is_primary', 'isActive' => 'is_active'];
        $attributes = [];
        foreach ($fieldMap as $requestField => $column) {
            if (array_key_exists($requestField, $validated)) {
                $attributes[$column] = $validated[$requestField];
            }
        }

        $updated = $this->supplierService->updateContact($contact, $attributes, $request->user());

        return new SupplierContactResource($updated);
    }
}
