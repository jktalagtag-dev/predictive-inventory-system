<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Governance\Services\AuditLogger;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\GoodsReceipt;
use App\Domains\Inventory\Models\GoodsReceiptLine;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Procurement\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the goods-receipt state machine: draft -> posted -> reversed.
 * Posting is the only action that mutates inventory_balances, appends
 * inventory_movements, and updates the source purchase order's line
 * received-quantity totals and header status; all of it happens inside one
 * transaction with the receipt state change (REST_API_SPECIFICATION.md
 * section 9.3).
 *
 * Deviation from REST_API_SPECIFICATION.md/DATABASE_DESIGN.md, matching the
 * existing PurchaseOrderService deviation: no approved direct-receipt policy
 * exists yet, so every receipt must be created against an existing purchase
 * order, and every line must reference an actual purchase_order_line_id —
 * product/unit identity is derived from that line, never trusted from the
 * client, which also removes an entire class of mismatched-receiving bugs.
 */
class GoodsReceiptService
{
    private const RECEIVABLE_PO_STATUSES = ['ordered', 'partially_received'];

    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * @param array{branch_id:int, purchase_order_id:int, supplier_delivery_number:?string, received_at:\DateTimeInterface, notes:?string, lines:array} $data
     */
    public function createDraft(array $data, User $actor, string $correlationId): GoodsReceipt
    {
        return DB::transaction(function () use ($data, $actor, $correlationId) {
            $po = PurchaseOrder::query()->with('lines')->findOrFail($data['purchase_order_id']);

            if (! in_array($po->status, self::RECEIVABLE_PO_STATUSES, true)) {
                throw new GoodsReceiptException('PO_NOT_RECEIVABLE', 422, 'This purchase order is not in a state that can be received against.');
            }

            if ($po->branch_id !== (int) $data['branch_id']) {
                throw new GoodsReceiptException('BRANCH_MISMATCH', 422, 'The receiving branch must match the purchase order branch.');
            }

            if (! empty($data['supplier_delivery_number'])) {
                $duplicate = GoodsReceipt::query()
                    ->where('purchase_order_id', $po->id)
                    ->where('supplier_delivery_number', $data['supplier_delivery_number'])
                    ->whereIn('status', ['draft', 'posted'])
                    ->exists();

                if ($duplicate) {
                    throw new GoodsReceiptException('DUPLICATE_DELIVERY_REFERENCE', 409, 'This supplier delivery reference has already been recorded against this purchase order.');
                }
            }

            $receipt = GoodsReceipt::query()->create([
                'purchase_order_id' => $po->id,
                'branch_id' => $data['branch_id'],
                'receipt_number' => 'GR-'.strtoupper(Str::random(10)),
                'status' => 'draft',
                'supplier_delivery_number' => $data['supplier_delivery_number'] ?? null,
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->replaceLines($receipt, $po, $data['lines'], $actor);

            $receipt->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'goods_receipt.created', 'goods_receipt', $receipt->id, $receipt->branch_id, $correlationId,
                null, ['status' => $receipt->status, 'receiptNumber' => $receipt->receipt_number, 'purchaseOrderId' => $po->id],
            );

            return $receipt;
        });
    }

    /**
     * @param array{supplier_delivery_number?:?string, received_at?:\DateTimeInterface, notes?:?string, lines?:array} $data
     */
    public function updateDraft(GoodsReceipt $receipt, array $data, User $actor, string $correlationId): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $data, $actor, $correlationId) {
            $locked = GoodsReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if ($locked->status !== 'draft') {
                throw new GoodsReceiptException('ILLEGAL_STATE', 409, 'Only a draft goods receipt can be updated.');
            }

            $before = ['supplierDeliveryNumber' => $locked->supplier_delivery_number, 'receivedAt' => $locked->received_at?->toIso8601String()];

            $locked->fill(array_filter([
                'supplier_delivery_number' => array_key_exists('supplier_delivery_number', $data) ? $data['supplier_delivery_number'] : null,
                'received_at' => $data['received_at'] ?? null,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
            ], fn ($value) => $value !== null));
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            if (array_key_exists('lines', $data)) {
                $po = PurchaseOrder::query()->with('lines')->findOrFail($locked->purchase_order_id);
                $this->replaceLines($locked, $po, $data['lines'], $actor);
            }

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'goods_receipt.updated', 'goods_receipt', $locked->id, $locked->branch_id, $correlationId,
                $before, ['supplierDeliveryNumber' => $locked->supplier_delivery_number, 'receivedAt' => $locked->received_at?->toIso8601String()],
            );

            return $locked;
        });
    }

    public function post(GoodsReceipt $receipt, User $actor, string $correlationId): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $actor, $correlationId) {
            $locked = GoodsReceipt::query()->lockForUpdate()->with('lines')->findOrFail($receipt->id);

            if ($locked->status !== 'draft') {
                throw new GoodsReceiptException('ILLEGAL_STATE', 409, 'Only a draft goods receipt can be posted.');
            }

            $po = PurchaseOrder::query()->lockForUpdate()->with('lines')->findOrFail($locked->purchase_order_id);

            foreach ($locked->lines as $line) {
                if (bccomp($line->accepted_quantity, '0', 4) === 1) {
                    $this->applyLineToBalance($locked, $line, $line->accepted_quantity, 'receipt', null, $actor, $correlationId);
                }

                if ($line->purchase_order_line_id !== null) {
                    /** @var PurchaseOrderLine $poLine */
                    $poLine = $po->lines->firstWhere('id', $line->purchase_order_line_id);
                    $poLine->received_quantity = bcadd($poLine->received_quantity, $line->accepted_quantity, 4);
                    $poLine->save();
                }
            }

            $this->recomputePurchaseOrderStatus($po, $actor);

            $locked->status = 'posted';
            $locked->posted_at = now();
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'goods_receipt.posted', 'goods_receipt', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'draft'], ['status' => 'posted', 'purchaseOrderStatus' => $po->status],
            );

            return $locked;
        });
    }

    public function reverse(GoodsReceipt $receipt, string $reason, User $actor, string $correlationId): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $reason, $actor, $correlationId) {
            $locked = GoodsReceipt::query()->lockForUpdate()->with('lines')->findOrFail($receipt->id);

            if ($locked->status !== 'posted') {
                throw new GoodsReceiptException('ILLEGAL_STATE', 409, 'Only a posted goods receipt can be reversed.');
            }

            $po = PurchaseOrder::query()->lockForUpdate()->with('lines')->findOrFail($locked->purchase_order_id);

            $compensating = GoodsReceipt::query()->create([
                'purchase_order_id' => $locked->purchase_order_id,
                'branch_id' => $locked->branch_id,
                'receipt_number' => 'GR-'.strtoupper(Str::random(10)),
                'status' => 'posted',
                'received_at' => now(),
                'posted_at' => now(),
                'notes' => $reason,
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $lineNumber = 1;
            foreach ($locked->lines as $originalLine) {
                $originalMovement = InventoryMovement::query()
                    ->where('reference_type', 'goods_receipt_line')
                    ->where('reference_id', $originalLine->id)
                    ->first();

                $compensatingLine = GoodsReceiptLine::query()->create([
                    'goods_receipt_id' => $compensating->id,
                    'purchase_order_line_id' => $originalLine->purchase_order_line_id,
                    'line_number' => $lineNumber++,
                    'product_id' => $originalLine->product_id,
                    'unit_id' => $originalLine->unit_id,
                    'product_sku_snapshot' => $originalLine->product_sku_snapshot,
                    'product_name_snapshot' => $originalLine->product_name_snapshot,
                    'received_quantity' => $originalLine->accepted_quantity,
                    'accepted_quantity' => $originalLine->accepted_quantity,
                    'rejected_quantity' => '0.0000',
                    'unit_cost' => $originalLine->unit_cost,
                    'notes' => "Reversal of receipt {$locked->receipt_number}: {$reason}",
                    'created_by_user_id' => $actor->id,
                ]);

                if (bccomp($originalLine->accepted_quantity, '0', 4) === 1) {
                    $this->applyLineToBalance(
                        $compensating,
                        $compensatingLine,
                        (string) bcmul($originalLine->accepted_quantity, '-1', 4),
                        'reversal',
                        $originalMovement?->id,
                        $actor,
                        $correlationId,
                    );
                }

                if ($originalLine->purchase_order_line_id !== null) {
                    /** @var PurchaseOrderLine $poLine */
                    $poLine = $po->lines->firstWhere('id', $originalLine->purchase_order_line_id);
                    $poLine->received_quantity = bcsub($poLine->received_quantity, $originalLine->accepted_quantity, 4);
                    $poLine->save();
                }
            }

            $this->recomputePurchaseOrderStatus($po, $actor);

            $locked->status = 'reversed';
            $locked->reversed_at = now();
            $locked->reversal_receipt_id = $compensating->id;
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'goods_receipt.reversed', 'goods_receipt', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'posted'], ['status' => 'reversed', 'reason' => $reason, 'reversalReceiptId' => $compensating->id],
            );

            return $locked;
        });
    }

    /**
     * @param array<int, array{purchase_order_line_id:int, received_quantity:string, accepted_quantity:string, rejected_quantity:string, unit_cost:string, lot_number?:?string, serial_number?:?string, expiry_date?:?string, rejection_reason?:?string, notes?:?string}> $lines
     */
    private function replaceLines(GoodsReceipt $receipt, PurchaseOrder $po, array $lines, User $actor): void
    {
        $receipt->lines()->delete();

        $lineNumber = 1;
        foreach ($lines as $line) {
            /** @var PurchaseOrderLine|null $poLine */
            $poLine = $po->lines->firstWhere('id', $line['purchase_order_line_id']);
            if (! $poLine) {
                throw new GoodsReceiptException('INVALID_PO_LINE', 422, 'One or more receipt lines do not belong to the selected purchase order.');
            }

            $received = $line['received_quantity'];
            $accepted = $line['accepted_quantity'];
            $rejected = $line['rejected_quantity'];

            if (bccomp(bcadd($accepted, $rejected, 4), $received, 4) !== 0) {
                throw new GoodsReceiptException('QUANTITY_MISMATCH', 422, "Accepted plus rejected quantity must equal received quantity for {$poLine->product_sku_snapshot}.");
            }

            if (bccomp($rejected, '0', 4) === 1 && empty($line['rejection_reason'])) {
                throw new GoodsReceiptException('REJECTION_REASON_REQUIRED', 422, "A rejection reason is required for {$poLine->product_sku_snapshot} since a rejected quantity was recorded.");
            }

            $product = $poLine->product;

            if ($product->is_lot_tracked && empty($line['lot_number'])) {
                throw new GoodsReceiptException('LOT_NUMBER_REQUIRED', 422, "A lot number is required for {$poLine->product_sku_snapshot}.");
            }

            if ($product->is_serial_tracked && empty($line['serial_number'])) {
                throw new GoodsReceiptException('SERIAL_NUMBER_REQUIRED', 422, "A serial number is required for {$poLine->product_sku_snapshot}.");
            }

            if ($product->is_expiry_tracked && empty($line['expiry_date'])) {
                throw new GoodsReceiptException('EXPIRY_DATE_REQUIRED', 422, "An expiry date is required for {$poLine->product_sku_snapshot}.");
            }

            $remainingOrdered = bcsub($poLine->ordered_quantity, $poLine->received_quantity, 4);
            if (bccomp($accepted, $remainingOrdered, 4) === 1) {
                throw new GoodsReceiptException('OVER_RECEIPT_NOT_ALLOWED', 422, "Accepted quantity for {$poLine->product_sku_snapshot} exceeds the remaining ordered quantity.");
            }

            GoodsReceiptLine::query()->create([
                'goods_receipt_id' => $receipt->id,
                'purchase_order_line_id' => $poLine->id,
                'line_number' => $lineNumber++,
                'product_id' => $poLine->product_id,
                'unit_id' => $poLine->unit_id,
                'product_sku_snapshot' => $poLine->product_sku_snapshot,
                'product_name_snapshot' => $poLine->product_name_snapshot,
                'received_quantity' => $received,
                'accepted_quantity' => $accepted,
                'rejected_quantity' => $rejected,
                'unit_cost' => $line['unit_cost'] ?? $poLine->unit_cost,
                'lot_number' => $line['lot_number'] ?? null,
                'serial_number' => $line['serial_number'] ?? null,
                'expiry_date' => $line['expiry_date'] ?? null,
                'rejection_reason' => $line['rejection_reason'] ?? null,
                'notes' => $line['notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);
        }
    }

    private function recomputePurchaseOrderStatus(PurchaseOrder $po, User $actor): void
    {
        if (! in_array($po->status, ['ordered', 'partially_received', 'received'], true)) {
            return;
        }

        $po->load('lines');
        $totalOrdered = $po->lines->reduce(fn ($carry, PurchaseOrderLine $line) => bcadd($carry, $line->ordered_quantity, 4), '0.0000');
        $totalReceived = $po->lines->reduce(fn ($carry, PurchaseOrderLine $line) => bcadd($carry, $line->received_quantity, 4), '0.0000');

        $newStatus = match (true) {
            bccomp($totalReceived, '0', 4) <= 0 => 'ordered',
            bccomp($totalReceived, $totalOrdered, 4) >= 0 => 'received',
            default => 'partially_received',
        };

        if ($newStatus !== $po->status) {
            $po->status = $newStatus;
            $po->updated_by_user_id = $actor->id;
            $po->row_version = $po->row_version + 1;
            $po->save();
        }
    }

    private function lockOrCreateBalance(int $branchId, int $productId): InventoryBalance
    {
        $balance = InventoryBalance::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        return InventoryBalance::query()->create([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'on_hand_quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
            'incoming_quantity' => 0,
            'row_version' => 1,
        ]);
    }

    private function applyLineToBalance(
        GoodsReceipt $receipt,
        GoodsReceiptLine $line,
        string $quantityDelta,
        string $movementType,
        ?int $reversesMovementId,
        User $actor,
        string $correlationId,
    ): void {
        $balance = $this->lockOrCreateBalance($receipt->branch_id, $line->product_id);

        $newOnHand = bcadd($balance->on_hand_quantity, $quantityDelta, 4);

        $balance->on_hand_quantity = $newOnHand;
        $balance->available_quantity = bcsub($newOnHand, $balance->reserved_quantity, 4);
        $balance->last_movement_at = now();
        $balance->row_version = $balance->row_version + 1;
        $balance->save();

        InventoryMovement::query()->create([
            'branch_id' => $receipt->branch_id,
            'product_id' => $line->product_id,
            'movement_type' => $movementType,
            'quantity_delta' => $quantityDelta,
            'on_hand_after_quantity' => $newOnHand,
            'unit_cost' => $line->unit_cost,
            'reference_type' => 'goods_receipt_line',
            'reference_id' => $line->id,
            'reverses_movement_id' => $reversesMovementId,
            'effective_at' => $receipt->received_at,
            'posted_at' => now(),
            'actor_user_id' => $actor->id,
            'correlation_id' => $correlationId,
        ]);
    }
}
