<?php

namespace App\Domains\Sync\Support;

use App\Domains\Sync\Handlers\InventoryAdjustmentCreateHandler;

/**
 * The approved offline workflow list (DEVELOPMENT_ROADMAP.md M9,
 * "Approved offline workflow enablement only after each workflow has
 * documented server validation and conflict behavior"). An operation
 * type absent from this registry is refused with
 * UNSUPPORTED_OFFLINE_OPERATION regardless of what the client sends.
 *
 * inventory_adjustment.create is the only enabled type for this phase:
 * it never mutates inventory_balances (only createDraft's preview
 * negative-stock check reads it), requires no live price/tax
 * recalculation, and needs no fresh authorization beyond the standard
 * branch-scoped permission check already enforced online. Posting an
 * adjustment — the action that actually moves stock — remains
 * online-only, per CLAUDE.md section 40 ("Block operations requiring
 * ... fresh stock truth ... when offline").
 */
final class OfflineOperationRegistry
{
    /**
     * @return array<string, array{handler: class-string, permission: string, currentPayloadVersion: int}>
     */
    public static function all(): array
    {
        return [
            'inventory_adjustment.create' => [
                'handler' => InventoryAdjustmentCreateHandler::class,
                'permission' => 'inventory.adjustments.create',
                'currentPayloadVersion' => 1,
            ],
        ];
    }

    public static function find(string $operationType): ?array
    {
        return self::all()[$operationType] ?? null;
    }
}
