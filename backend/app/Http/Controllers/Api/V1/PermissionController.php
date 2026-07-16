<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Identity\Models\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PermissionResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('permissions.read')) {
            throw new AuthorizationException;
        }

        $permissions = Permission::query()->orderBy('module')->orderBy('name')->get();

        return response()->json([
            'data' => PermissionResource::collection($permissions),
            'meta' => ['requestId' => (string) Str::uuid()],
        ]);
    }
}
