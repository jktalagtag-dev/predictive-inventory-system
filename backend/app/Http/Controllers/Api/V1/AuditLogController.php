<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Governance\Models\AuditLog;
use App\Domains\Governance\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = AuditLog::query();

        if ($request->filled('branchId')) {
            $query->where('branch_id', (int) $request->query('branchId'));
        }

        if ($request->filled('actorUserId')) {
            $query->where('actor_user_id', (int) $request->query('actorUserId'));
        }

        if ($request->filled('eventType')) {
            $query->where('action', $request->query('eventType'));
        }

        if ($request->filled('entityType')) {
            $query->where('entity_type', $request->query('entityType'));
        }

        if ($request->filled('entityId')) {
            $query->where('entity_id', (int) $request->query('entityId'));
        }

        if ($request->filled('correlationId')) {
            $query->where('correlation_id', $request->query('correlationId'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        $correlationId = $this->correlationId($request);

        // Access to audit records is itself audited (CLAUDE.md section 55).
        $this->auditLogger->record(
            $request->user(),
            'audit_log.searched',
            'audit_log',
            null,
            $request->filled('branchId') ? (int) $request->query('branchId') : null,
            $correlationId,
            null,
            null,
            $request->ip(),
        );

        return response()->json([
            'data' => AuditLogResource::collection($paginator->items()),
            'meta' => [
                'requestId' => $correlationId,
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);

        $correlationId = $this->correlationId($request);

        $this->auditLogger->record(
            $request->user(),
            'audit_log.viewed',
            'audit_log',
            $auditLog->id,
            $auditLog->branch_id,
            $correlationId,
            null,
            null,
            $request->ip(),
        );

        return (new AuditLogResource($auditLog))->response();
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }
}
