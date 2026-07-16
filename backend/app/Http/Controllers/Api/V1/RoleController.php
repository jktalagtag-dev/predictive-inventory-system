<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Identity\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RoleResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('roles.read')) {
            throw new AuthorizationException;
        }

        $roles = Role::query()->with('permissions')->orderBy('name')->get();

        return response()->json([
            'data' => RoleResource::collection($roles),
            'meta' => ['requestId' => (string) Str::uuid()],
        ]);
    }
}
