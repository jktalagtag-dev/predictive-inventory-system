<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InventoryMovementResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('inventory.movements.read')) {
            throw new AuthorizationException;
        }

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view movement history.']]);
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        if ($request->filled('from') && $request->filled('to') && $request->query('from') > $request->query('to')) {
            throw ValidationException::withMessages(['from' => ['The date range is invalid.']])->status(422);
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = InventoryMovement::query()->with(['product', 'actor'])->where('branch_id', $branchId);

        if ($request->filled('productId')) {
            $query->where('product_id', (int) $request->query('productId'));
        }

        if ($request->filled('movementType')) {
            $query->where('movement_type', $request->query('movementType'));
        }

        if ($request->filled('referenceType')) {
            $query->where('reference_type', $request->query('referenceType'));
        }

        if ($request->filled('referenceId')) {
            $query->where('reference_id', (int) $request->query('referenceId'));
        }

        if ($request->filled('actorUserId')) {
            $query->where('actor_user_id', (int) $request->query('actorUserId'));
        }

        if ($request->filled('from')) {
            $query->where('effective_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('effective_at', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('effective_at')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => InventoryMovementResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
