<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUnitOfMeasureRequest;
use App\Http\Requests\Api\V1\UpdateUnitOfMeasureRequest;
use App\Http\Resources\Api\V1\UnitOfMeasureResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnitOfMeasureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('units.read')) {
            throw new AuthorizationException;
        }

        $query = UnitOfMeasure::query();

        if ($request->filled('dimension')) {
            $query->where('dimension', $request->query('dimension'));
        }

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => UnitOfMeasureResource::collection($query->orderBy('name')->get()),
            'meta' => ['requestId' => (string) Str::uuid()],
        ]);
    }

    public function store(StoreUnitOfMeasureRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $unit = UnitOfMeasure::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'dimension' => $validated['dimension'],
            'is_active' => $validated['isActive'] ?? true,
            'row_version' => 1,
        ]);

        return (new UnitOfMeasureResource($unit->refresh()))->response()->setStatusCode(201);
    }

    public function update(UpdateUnitOfMeasureRequest $request, UnitOfMeasure $unit): UnitOfMeasureResource|JsonResponse
    {
        $validated = $request->validated();

        if ((int) $validated['version'] !== $unit->row_version) {
            return response()->json([
                'error' => ['code' => 'VERSION_CONFLICT', 'message' => 'This unit was changed by someone else. Reload and try again.', 'requestId' => (string) Str::uuid()],
            ], 409);
        }

        $attributes = array_filter([
            'code' => $validated['code'] ?? null,
            'name' => $validated['name'] ?? null,
            'symbol' => $validated['symbol'] ?? null,
            'dimension' => $validated['dimension'] ?? null,
            'is_active' => $validated['isActive'] ?? null,
        ], fn ($value) => $value !== null);

        $unit->fill($attributes);
        $unit->row_version = $unit->row_version + 1;
        $unit->save();

        return new UnitOfMeasureResource($unit);
    }
}
