<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Identity\Models\Branch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBranchRequest;
use App\Http\Requests\Api\V1\UpdateBranchRequest;
use App\Http\Resources\Api\V1\BranchResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        $query = Branch::query();

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => BranchResource::collection($query->orderBy('name')->get()),
            'meta' => ['requestId' => (string) Str::uuid()],
        ]);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $branch = Branch::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'address_line_1' => $validated['addressLine1'] ?? null,
            'address_line_2' => $validated['addressLine2'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postalCode'] ?? null,
            'country_code' => $validated['countryCode'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
            'row_version' => 1,
        ]);

        return (new BranchResource($branch->refresh()))->response()->setStatusCode(201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): BranchResource|JsonResponse
    {
        $validated = $request->validated();

        if ((int) $validated['version'] !== $branch->row_version) {
            return response()->json([
                'error' => [
                    'code' => 'VERSION_CONFLICT',
                    'message' => 'This branch was changed by someone else. Reload and try again.',
                    'requestId' => (string) Str::uuid(),
                ],
            ], 409);
        }

        $attributes = array_filter([
            'code' => $validated['code'] ?? null,
            'name' => $validated['name'] ?? null,
            'address_line_1' => $validated['addressLine1'] ?? null,
            'address_line_2' => $validated['addressLine2'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postalCode'] ?? null,
            'country_code' => $validated['countryCode'] ?? null,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : null,
            'is_active' => $validated['isActive'] ?? null,
        ], fn ($value) => $value !== null);

        $branch->fill($attributes);
        $branch->row_version = $branch->row_version + 1;
        $branch->save();

        return new BranchResource($branch);
    }
}
