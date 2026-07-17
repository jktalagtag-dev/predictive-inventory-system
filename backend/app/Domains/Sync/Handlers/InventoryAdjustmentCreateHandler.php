<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Services\InventoryAdjustmentException;
use App\Domains\Inventory\Services\InventoryAdjustmentService;
use App\Domains\Sync\Contracts\OfflineOperationHandler;
use App\Domains\Sync\Contracts\OfflineOperationResult;
use App\Domains\Sync\Services\SyncOperationConflictException;
use App\Domains\Sync\Services\SyncOperationRejectedException;
use App\Http\Requests\Api\V1\StoreInventoryAdjustmentRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Replays a queued inventory-adjustment draft through the exact same
 * InventoryAdjustmentService::createDraft() the online endpoint calls, so
 * offline and online submissions are validated identically — this
 * handler only adds the payload-shape check a FormRequest would
 * otherwise provide, since there is no HTTP request to bind one to.
 */
class InventoryAdjustmentCreateHandler implements OfflineOperationHandler
{
    public function __construct(private readonly InventoryAdjustmentService $adjustmentService)
    {
    }

    public function handle(array $payload, int $branchId, User $actor, string $correlationId): OfflineOperationResult
    {
        $validated = $this->validatePayload($payload, $branchId);

        try {
            $adjustment = $this->adjustmentService->createDraft([
                'branch_id' => $branchId,
                'reason_code' => $validated['reasonCode'],
                'reason_note' => $validated['reasonNote'] ?? null,
                'effective_at' => $validated['effectiveAt'],
                'lines' => array_map(fn ($line) => [
                    'product_id' => $line['productId'],
                    'quantity_delta' => (string) $line['quantityDelta'],
                    'unit_cost' => isset($line['unitCost']) ? (string) $line['unitCost'] : null,
                    'notes' => $line['notes'] ?? null,
                ], $validated['lines']),
            ], $actor, $correlationId);
        } catch (InventoryAdjustmentException $exception) {
            // Negative stock means the client's offline snapshot of
            // on-hand quantity is no longer accurate — that is a conflict
            // between local and server truth, not a plain validation
            // refusal, so the user gets a resolver rather than a dead end.
            if ($exception->errorCode() === 'NEGATIVE_STOCK') {
                throw new SyncOperationConflictException(
                    'STALE_STOCK_SNAPSHOT',
                    $exception->getMessage(),
                    ['reason' => $exception->getMessage()],
                );
            }

            throw new SyncOperationRejectedException($exception->errorCode(), $exception->getMessage());
        }

        return new OfflineOperationResult(
            resourceType: 'inventory_adjustment',
            resourceId: $adjustment->id,
            resourceSnapshot: [
                'id' => (string) $adjustment->id,
                'adjustmentNumber' => $adjustment->adjustment_number,
                'status' => $adjustment->status,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validatePayload(array $payload, int $branchId): array
    {
        $validator = Validator::make($payload, [
            'reasonCode' => ['required', 'string', Rule::in(StoreInventoryAdjustmentRequest::REASON_CODES)],
            'reasonNote' => ['nullable', 'string', 'max:1000'],
            'effectiveAt' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.productId' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.quantityDelta' => ['required', 'numeric', 'not_in:0'],
            'lines.*.unitCost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            throw new SyncOperationRejectedException('INVALID_OFFLINE_PAYLOAD', $validator->errors()->first());
        }

        return $validator->validated();
    }
}
