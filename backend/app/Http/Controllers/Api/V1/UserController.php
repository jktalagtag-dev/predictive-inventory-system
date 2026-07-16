<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\UserAccessException;
use App\Domains\Identity\Services\UserAccessService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(private readonly UserAccessService $userAccessService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = User::query()->with(['roles', 'branches']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('display_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        if ($request->filled('role')) {
            $roleCode = (string) $request->query('role');
            $query->whereHas('roles', fn ($inner) => $inner->where('code', $roleCode));
        }

        if ($request->filled('branchId')) {
            $branchId = (int) $request->query('branchId');
            $query->whereHas('branches', fn ($inner) => $inner->where('branches.id', $branchId));
        }

        $paginator = $query->orderBy('display_name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => UserResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->userAccessService->createUser(
            attributes: [
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'display_name' => trim($validated['firstName'].' '.$validated['lastName']),
                'email' => mb_strtolower($validated['email']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['isActive'] ?? true,
                'password_hash' => Hash::make(Str::password(16)),
            ],
            roleIds: $validated['roleIds'],
            branchIds: $validated['branchIds'],
            defaultBranchId: $validated['defaultBranchId'],
            actor: $request->user(),
        );

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load(['roles', 'branches']));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource|JsonResponse
    {
        $validated = $request->validated();

        if ((int) $validated['version'] !== $user->row_version) {
            return response()->json([
                'error' => [
                    'code' => 'VERSION_CONFLICT',
                    'message' => 'This user was changed by someone else. Reload and try again.',
                    'requestId' => (string) Str::uuid(),
                ],
            ], 409);
        }

        $attributes = array_filter([
            'first_name' => $validated['firstName'] ?? null,
            'last_name' => $validated['lastName'] ?? null,
            'email' => isset($validated['email']) ? mb_strtolower($validated['email']) : null,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : null,
            'is_active' => $validated['isActive'] ?? null,
        ], fn ($value) => $value !== null);

        if (isset($validated['firstName']) || isset($validated['lastName'])) {
            $attributes['display_name'] = trim(($validated['firstName'] ?? $user->first_name).' '.($validated['lastName'] ?? $user->last_name));
        }

        try {
            $updated = $this->userAccessService->updateUser(
                user: $user,
                attributes: $attributes,
                roleIds: $validated['roleIds'] ?? null,
                branchIds: $validated['branchIds'] ?? null,
                defaultBranchId: $validated['defaultBranchId'] ?? null,
                actor: $request->user(),
            );
        } catch (UserAccessException $exception) {
            return response()->json([
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => $exception->getMessage(),
                    'requestId' => (string) Str::uuid(),
                ],
            ], 422);
        }

        return new UserResource($updated);
    }
}
