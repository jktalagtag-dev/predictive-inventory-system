<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Planning\Models\RestockingAlert;
use App\Domains\Planning\Services\PlanningException;
use App\Domains\Planning\Services\RestockingAlertService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AcknowledgeRestockingAlertRequest;
use App\Http\Requests\Api\V1\ResolveRestockingAlertRequest;
use App\Http\Resources\Api\V1\RestockingAlertResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RestockingAlertController extends Controller
{
    public function __construct(private readonly RestockingAlertService $alertService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RestockingAlert::class);

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to view restocking alerts.']]);
        }

        $branchId = (int) $request->query('branchId');
        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = RestockingAlert::query()
            ->with(['reorderPolicy.product'])
            ->whereHas('reorderPolicy', fn ($inner) => $inner->where('branch_id', $branchId));

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }
        if ($request->filled('assignedToUserId')) {
            $query->where('assigned_to_user_id', (int) $request->query('assignedToUserId'));
        }

        $paginator = $query
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('last_evaluated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => RestockingAlertResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, RestockingAlert $restockingAlert): RestockingAlertResource
    {
        $this->authorize('view', $restockingAlert);

        return new RestockingAlertResource($restockingAlert->load(['reorderPolicy.product', 'events']));
    }

    public function evaluate(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('restocking.evaluate')) {
            throw new AuthorizationException;
        }

        if (! $request->filled('branchId')) {
            throw ValidationException::withMessages(['branchId' => ['A branch is required to evaluate restocking alerts.']]);
        }

        $branchId = (int) $request->input('branchId');
        if (! $request->user()->canAccessBranch($branchId)) {
            throw new AuthorizationException;
        }

        $alerts = $this->alertService->evaluateAll($branchId);

        return response()->json(['data' => ['evaluatedActiveAlertCount' => $alerts->count()]]);
    }

    public function acknowledge(AcknowledgeRestockingAlertRequest $request, RestockingAlert $restockingAlert): JsonResponse
    {
        $this->authorize('acknowledge', $restockingAlert);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $restockingAlert->row_version) {
            return $this->conflictResponse('This alert was changed by someone else. Reload and try again.');
        }

        try {
            $updated = $this->alertService->acknowledge($restockingAlert, $validated['assignedToUserId'] ?? null, $validated['note'] ?? null, $request->user());
        } catch (PlanningException $exception) {
            return $this->exceptionResponse($exception);
        }

        return response()->json(['data' => (new RestockingAlertResource($updated->load(['reorderPolicy.product', 'events'])))->response()->getData(true)['data']]);
    }

    public function resolve(ResolveRestockingAlertRequest $request, RestockingAlert $restockingAlert): JsonResponse
    {
        $this->authorize('resolve', $restockingAlert);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $restockingAlert->row_version) {
            return $this->conflictResponse('This alert was changed by someone else. Reload and try again.');
        }

        try {
            $updated = $this->alertService->resolve($restockingAlert, $validated['reason'], $request->user());
        } catch (PlanningException $exception) {
            return $this->exceptionResponse($exception);
        }

        return response()->json(['data' => (new RestockingAlertResource($updated->load(['reorderPolicy.product', 'events'])))->response()->getData(true)['data']]);
    }

    public function dismiss(ResolveRestockingAlertRequest $request, RestockingAlert $restockingAlert): JsonResponse
    {
        $this->authorize('resolve', $restockingAlert);

        $validated = $request->validated();

        if ((int) $validated['version'] !== $restockingAlert->row_version) {
            return $this->conflictResponse('This alert was changed by someone else. Reload and try again.');
        }

        try {
            $updated = $this->alertService->dismiss($restockingAlert, $validated['reason'], $request->user());
        } catch (PlanningException $exception) {
            return $this->exceptionResponse($exception);
        }

        return response()->json(['data' => (new RestockingAlertResource($updated->load(['reorderPolicy.product', 'events'])))->response()->getData(true)['data']]);
    }

    private function exceptionResponse(PlanningException $exception): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
                'requestId' => (string) Str::uuid(),
            ],
        ], $exception->status());
    }

    private function conflictResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => ['code' => 'VERSION_CONFLICT', 'message' => $message, 'requestId' => (string) Str::uuid()],
        ], 409);
    }
}
