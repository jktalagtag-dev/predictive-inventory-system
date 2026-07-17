<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Sync\Models\SyncOperation;
use App\Domains\Sync\Services\SyncCoordinatorService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncOperationsRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    public function __construct(private readonly SyncCoordinatorService $syncCoordinator)
    {
    }

    public function store(SyncOperationsRequest $request): JsonResponse
    {
        $results = $this->syncCoordinator->processBatch(
            $request->validated()['operations'],
            $request->user(),
            $this->correlationId($request),
        );

        return response()->json([
            'data' => ['results' => $results],
            'meta' => ['requestId' => $this->correlationId($request)],
        ]);
    }

    public function show(Request $request, string $clientOperationId): JsonResponse
    {
        if (! $request->user()->hasPermission('sync.use')) {
            throw new AuthorizationException;
        }

        $operation = SyncOperation::query()->where('client_operation_id', $clientOperationId)->first();

        if ($operation === null) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'This sync operation is not known to the server.', 'requestId' => $this->correlationId($request)],
            ], 404);
        }

        $isOriginator = $operation->actor_user_id === $request->user()->id;
        $isSupportRole = $request->user()->hasRole('owner') || $request->user()->hasRole('manager');

        if (! $isOriginator && ! $isSupportRole) {
            throw new AuthorizationException;
        }

        return response()->json([
            'data' => [
                'clientOperationId' => $operation->client_operation_id,
                'operationType' => $operation->operation_type,
                'status' => $operation->status,
                'resolvedAt' => optional($operation->resolved_at)->toIso8601String(),
                'errorCode' => $operation->error_code,
                'serverResource' => $operation->server_resource_type !== null
                    ? ['type' => $operation->server_resource_type, 'id' => (string) $operation->server_resource_id]
                    : null,
                'conflictPayload' => ($isOriginator || $isSupportRole) ? $operation->conflict_payload : null,
            ],
            'meta' => ['requestId' => $this->correlationId($request)],
        ]);
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }
}
