<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Models\InventoryBalance;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InventoryBalanceResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryBalanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('inventory.read')) {
            throw new AuthorizationException;
        }

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view inventory balances.']]);
        }

        $branchId = (int) $request->query('branchId');

        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = InventoryBalance::query()->with('product')->where('branch_id', $branchId);

        if ($request->filled('productId')) {
            $query->where('product_id', (int) $request->query('productId'));
        }

        if ($request->filled('categoryId')) {
            $categoryId = (int) $request->query('categoryId');
            $query->whereHas('product', fn ($inner) => $inner->where('category_id', $categoryId));
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->whereHas('product', fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        if ($request->query('availability') === 'out_of_stock') {
            // "low_stock" is intentionally not supported yet: it requires a
            // reorder point, which belongs to the reorder-policy domain
            // (not yet built) and must not be guessed here.
            $query->where('available_quantity', '<=', 0);
        } elseif ($request->query('availability') === 'in_stock') {
            $query->where('available_quantity', '>', 0);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => InventoryBalanceResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
