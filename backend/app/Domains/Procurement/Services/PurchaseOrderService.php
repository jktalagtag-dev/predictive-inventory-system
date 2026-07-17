<?php

namespace App\Domains\Procurement\Services;

use App\Domains\Catalog\Models\Product;
use App\Domains\Governance\Services\AuditLogger;
use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Procurement\Models\PurchaseOrderLine;
use App\Domains\Procurement\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the purchase-order state machine:
 * draft -> submitted -> approved -> ordered -> closed
 *                   \-> draft (rejected)
 * draft/submitted/approved/ordered -> cancelled
 *
 * Deviations from DATABASE_DESIGN.md, both driven by dependencies not yet
 * built (product_units, goods_receipts): PO lines reference the product's
 * canonical stock unit directly instead of a product_units row, and "close"
 * is only reachable from "ordered" since there is no receiving workflow yet
 * to track partial/complete fulfillment.
 *
 * Every state-changing method writes an audit entry inside the same
 * transaction as the fact it describes (CLAUDE.md section 55).
 */
class PurchaseOrderService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function createDraft(array $data, User $actor, string $correlationId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actor, $correlationId) {
            $supplier = Supplier::query()->findOrFail($data['supplier_id']);
            if (! $supplier->is_active) {
                throw new PurchaseOrderException('INACTIVE_SUPPLIER', 422, 'This supplier is not active and cannot receive new purchase orders.');
            }

            $po = PurchaseOrder::query()->create([
                'branch_id' => $data['branch_id'],
                'supplier_id' => $data['supplier_id'],
                'po_number' => 'PO-'.strtoupper(Str::random(10)),
                'status' => 'draft',
                'currency_code' => $data['currency_code'],
                'expected_receipt_at' => $data['expected_receipt_at'] ?? null,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->replaceLines($po, $data['lines'], $actor);
            $this->recalculateTotals($po);

            $po->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'purchase_order.created', 'purchase_order', $po->id, $po->branch_id, $correlationId,
                null, ['status' => $po->status, 'poNumber' => $po->po_number, 'supplierId' => $po->supplier_id],
            );

            return $po;
        });
    }

    public function updateDraft(PurchaseOrder $po, array $data, User $actor, string $correlationId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data, $actor, $correlationId) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

            if ($locked->status !== 'draft') {
                throw new PurchaseOrderException('ILLEGAL_STATE', 409, 'Only a draft purchase order can be updated.');
            }

            $before = ['totalAmount' => $locked->total_amount, 'supplierReference' => $locked->supplier_reference];

            $locked->fill(array_filter([
                'currency_code' => $data['currency_code'] ?? null,
                'expected_receipt_at' => $data['expected_receipt_at'] ?? null,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
            ], fn ($value) => $value !== null));
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            if (array_key_exists('lines', $data)) {
                $this->replaceLines($locked, $data['lines'], $actor);
                $this->recalculateTotals($locked);
            }

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'purchase_order.updated', 'purchase_order', $locked->id, $locked->branch_id, $correlationId,
                $before, ['totalAmount' => $locked->total_amount, 'supplierReference' => $locked->supplier_reference],
            );

            return $locked;
        });
    }

    public function submit(PurchaseOrder $po, User $actor, string $correlationId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $actor, $correlationId) {
            $locked = PurchaseOrder::query()->lockForUpdate()->with('lines')->findOrFail($po->id);

            if ($locked->status !== 'draft') {
                throw new PurchaseOrderException('ILLEGAL_STATE', 409, 'Only a draft purchase order can be submitted.');
            }

            if ($locked->lines->isEmpty()) {
                throw new PurchaseOrderException('EMPTY_ORDER', 422, 'A purchase order needs at least one line before it can be submitted.');
            }

            $locked->status = 'submitted';
            $locked->submitted_at = now();
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'purchase_order.submitted', 'purchase_order', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'draft'], ['status' => 'submitted'],
            );

            return $locked;
        });
    }

    public function decide(PurchaseOrder $po, string $decision, ?string $reason, User $actor, string $correlationId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $decision, $reason, $actor, $correlationId) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

            if ($locked->status !== 'submitted') {
                throw new PurchaseOrderException('ILLEGAL_STATE', 409, 'Only a submitted purchase order can be approved or rejected.');
            }

            if ($locked->created_by_user_id === $actor->id) {
                throw new PurchaseOrderException('SELF_APPROVAL_DENIED', 403, 'You cannot approve or reject a purchase order you created yourself.');
            }

            if ($decision === 'rejected' && ! $reason) {
                throw new PurchaseOrderException('REASON_REQUIRED', 422, 'A reason is required to reject a purchase order.');
            }

            $locked->approvals()->create([
                'approval_stage' => 1,
                'decision' => $decision,
                'decision_by_user_id' => $actor->id,
                'decision_at' => now(),
                'reason' => $reason,
                'policy_snapshot' => ['requiresSeparationOfDuties' => true, 'stage' => 1],
            ]);

            if ($decision === 'approved') {
                $locked->status = 'approved';
                $locked->approved_at = now();
            } else {
                $locked->status = 'draft';
            }

            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load(['lines', 'approvals']);

            $this->auditLogger->record(
                $actor, $decision === 'approved' ? 'purchase_order.approved' : 'purchase_order.rejected',
                'purchase_order', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'submitted'], ['status' => $locked->status, 'reason' => $reason],
            );

            return $locked;
        });
    }

    public function markOrdered(PurchaseOrder $po, \DateTimeInterface $orderedAt, ?string $supplierReference, User $actor, string $correlationId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $orderedAt, $supplierReference, $actor, $correlationId) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

            if ($locked->status !== 'approved') {
                throw new PurchaseOrderException('ILLEGAL_STATE', 409, 'Only an approved purchase order can be marked ordered.');
            }

            $locked->status = 'ordered';
            $locked->ordered_at = $orderedAt;
            if ($supplierReference !== null) {
                $locked->supplier_reference = $supplierReference;
            }
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'purchase_order.ordered', 'purchase_order', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'approved'], ['status' => 'ordered', 'orderedAt' => $locked->ordered_at?->toIso8601String()],
            );

            return $locked;
        });
    }

    public function cancel(PurchaseOrder $po, string $reason, User $actor, string $correlationId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $reason, $actor, $correlationId) {
            $locked = PurchaseOrder::query()->lockForUpdate()->with('lines')->findOrFail($po->id);

            if (in_array($locked->status, ['received', 'cancelled', 'closed'], true)) {
                throw new PurchaseOrderException('ILLEGAL_STATE', 409, 'This purchase order cannot be cancelled from its current state.');
            }

            $hasReceipts = $locked->lines->contains(fn (PurchaseOrderLine $line) => bccomp($line->received_quantity, '0', 4) === 1);
            if ($hasReceipts) {
                throw new PurchaseOrderException('HAS_RECEIPTS', 409, 'A purchase order with recorded receipts cannot be cancelled.');
            }

            $previousStatus = $locked->status;
            $locked->status = 'cancelled';
            $locked->cancelled_at = now();
            $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '')."Cancelled: {$reason}");
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'purchase_order.cancelled', 'purchase_order', $locked->id, $locked->branch_id, $correlationId,
                ['status' => $previousStatus], ['status' => 'cancelled', 'reason' => $reason],
            );

            return $locked;
        });
    }

    public function close(PurchaseOrder $po, ?string $reason, User $actor, string $correlationId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $reason, $actor, $correlationId) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

            if ($locked->status !== 'ordered') {
                throw new PurchaseOrderException('ILLEGAL_STATE', 409, 'Only an ordered purchase order can be closed.');
            }

            $locked->status = 'closed';
            if ($reason) {
                $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '')."Closed: {$reason}");
            }
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $locked->refresh()->load('lines');

            $this->auditLogger->record(
                $actor, 'purchase_order.closed', 'purchase_order', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'ordered'], ['status' => 'closed', 'reason' => $reason],
            );

            return $locked;
        });
    }

    /**
     * @param array<int, array{product_id:int, unit_id:int, ordered_quantity:string, unit_cost:string, tax_rate?:string, discount_amount?:string, expected_receipt_at?:?\DateTimeInterface, notes?:?string}> $lines
     */
    private function replaceLines(PurchaseOrder $po, array $lines, User $actor): void
    {
        $po->lines()->delete();

        $lineNumber = 1;
        foreach ($lines as $line) {
            $product = Product::query()->findOrFail($line['product_id']);

            $quantity = $line['ordered_quantity'];
            $unitCost = $line['unit_cost'];
            $taxRate = $line['tax_rate'] ?? '0';
            $discount = $line['discount_amount'] ?? '0';

            $grossAmount = bcmul($quantity, $unitCost, 4);
            $netAmount = bcsub($grossAmount, $discount, 4);
            $taxAmount = bcdiv(bcmul($netAmount, $taxRate, 6), '100', 4);
            $totalAmount = bcadd($netAmount, $taxAmount, 4);

            PurchaseOrderLine::query()->create([
                'purchase_order_id' => $po->id,
                'line_number' => $lineNumber++,
                'product_id' => $product->id,
                'unit_id' => $line['unit_id'],
                'product_sku_snapshot' => $product->sku,
                'product_name_snapshot' => $product->name,
                'ordered_quantity' => $quantity,
                'unit_cost' => $unitCost,
                'tax_rate' => $taxRate,
                'discount_amount' => $discount,
                'net_amount' => $netAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'expected_receipt_at' => $line['expected_receipt_at'] ?? null,
                'notes' => $line['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);
        }
    }

    private function recalculateTotals(PurchaseOrder $po): void
    {
        $lines = $po->lines()->get();

        $subtotal = $lines->reduce(fn ($carry, $line) => bcadd($carry, $line->net_amount, 4), '0.0000');
        $tax = $lines->reduce(fn ($carry, $line) => bcadd($carry, $line->tax_amount, 4), '0.0000');
        $discount = $lines->reduce(fn ($carry, $line) => bcadd($carry, $line->discount_amount, 4), '0.0000');
        $total = $lines->reduce(fn ($carry, $line) => bcadd($carry, $line->total_amount, 4), '0.0000');

        $po->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'total_amount' => $total,
        ]);
    }
}
