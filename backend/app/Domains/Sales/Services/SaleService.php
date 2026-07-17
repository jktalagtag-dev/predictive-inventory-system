<?php

namespace App\Domains\Sales\Services;

use App\Domains\Catalog\Models\Product;
use App\Domains\Governance\Services\AuditLogger;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleLine;
use App\Domains\Sales\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns POS sale finalization and its two compensating workflows, void and
 * refund (REST_API_SPECIFICATION.md section 11). A finalized sale is an
 * immutable business document — price, tax, and discount are always
 * recalculated server-side from the current product master, never trusted
 * from the client, and stock availability is revalidated inside the same
 * transaction that creates the sale (CLAUDE.md section 43/49/73).
 *
 * Deviation from DATABASE_DESIGN.md, matching the existing PurchaseOrderLine
 * / GoodsReceiptLine deviation: sale lines reference the product's canonical
 * stock unit directly, since the dedicated product_units table has not been
 * built yet.
 *
 * Deviation: void() does not create a compensating `sales` document — it
 * flips the original sale to `voided` and appends compensating inventory
 * movements, since a void is a same-transaction correction with nothing
 * further to reconcile. refund() does create a compensating `sales`
 * document (status `refunded`, linked via `reverses_sale_id`), matching
 * that column's documented purpose, because refunds can be partial and
 * need their own receipt and payment trail. Partial refunds do not
 * re-derive a proportional discount from the original line; the refunded
 * amount uses the original line's unit price and tax rate with no
 * discount component, which is documented here as a deliberate
 * simplification pending an approved partial-discount-refund policy.
 */
class SaleService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * @param array{branch_id:int, sold_at:\DateTimeInterface, currency_code:string, notes:?string, approved_by_user_id:?int, idempotency_key:string, lines:array, payments:array} $data
     */
    public function finalize(array $data, User $actor, string $correlationId): Sale
    {
        return DB::transaction(function () use ($data, $actor, $correlationId) {
            if (count($data['lines']) === 0) {
                throw new SaleException('EMPTY_SALE', 422, 'A sale must include at least one line.');
            }

            $productIds = array_map(fn ($line) => $line['product_id'], $data['lines']);
            if (count($productIds) !== count(array_unique($productIds))) {
                throw new SaleException('DUPLICATE_PRODUCT_LINE', 422, 'Each product may appear only once per sale.');
            }

            $approver = ! empty($data['approved_by_user_id']) ? User::query()->find($data['approved_by_user_id']) : null;

            $lineNumber = 1;
            $builtLines = [];
            $subtotal = '0.0000';
            $discountTotal = '0.0000';
            $taxTotal = '0.0000';
            $total = '0.0000';

            foreach ($data['lines'] as $lineInput) {
                $built = $this->buildSaleLine($lineInput, $actor, $approver, $lineNumber++);
                $builtLines[] = $built;
                $subtotal = bcadd($subtotal, $built['gross_amount'], 4);
                $discountTotal = bcadd($discountTotal, $built['discount_amount'], 4);
                $taxTotal = bcadd($taxTotal, $built['tax_amount'], 4);
                $total = bcadd($total, $built['total_amount'], 4);
            }

            $paymentsTotal = array_reduce($data['payments'], fn ($carry, $payment) => bcadd($carry, (string) $payment['amount'], 4), '0.0000');
            if (bccomp($paymentsTotal, $total, 4) !== 0) {
                throw new SaleException('PAYMENT_TOTAL_MISMATCH', 422, 'The total payments do not equal the sale total.');
            }

            $balances = [];
            foreach ($builtLines as $built) {
                if ($built['product']->product_type !== 'stock') {
                    continue;
                }

                $balance = $this->lockOrCreateBalance($data['branch_id'], $built['product']->id);
                if (bccomp($balance->available_quantity, $built['quantity'], 4) === -1) {
                    throw new SaleException('INSUFFICIENT_STOCK', 422, "Insufficient available stock for {$built['product']->sku}.");
                }
                $balances[$built['product']->id] = $balance;
            }

            $sale = Sale::query()->create([
                'branch_id' => $data['branch_id'],
                'sale_number' => 'SALE-'.strtoupper(Str::random(10)),
                'status' => 'completed',
                'currency_code' => $data['currency_code'],
                'sold_at' => $data['sold_at'],
                'completed_at' => now(),
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discountTotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $total,
                'cashier_user_id' => $actor->id,
                'approved_by_user_id' => $approver?->id,
                'idempotency_key' => $data['idempotency_key'],
                'correlation_id' => $correlationId,
                'notes' => $data['notes'] ?? null,
                'row_version' => 1,
            ]);

            foreach ($builtLines as $built) {
                $product = $built['product'];
                $stockDelta = $product->product_type === 'stock' ? (string) bcmul($built['quantity'], '-1', 4) : '0.0000';

                $line = SaleLine::query()->create([
                    'sale_id' => $sale->id,
                    'line_number' => $built['line_number'],
                    'product_id' => $product->id,
                    'unit_id' => $built['unit_id'],
                    'product_sku_snapshot' => $product->sku,
                    'product_name_snapshot' => $product->name,
                    'quantity' => $built['quantity'],
                    'stock_quantity_delta' => $stockDelta,
                    'unit_price' => $built['unit_price'],
                    'discount_amount' => $built['discount_amount'],
                    'tax_rate' => $built['tax_rate'],
                    'tax_amount' => $built['tax_amount'],
                    'line_total_amount' => $built['total_amount'],
                    'override_reason' => $built['override_reason'],
                ]);

                if (array_key_exists($product->id, $balances)) {
                    $this->applyMovement($balances[$product->id], $stockDelta, 'sale', null, $actor, $correlationId, $line->id);
                }
            }

            foreach ($data['payments'] as $payment) {
                SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => (string) $payment['amount'],
                    'currency_code' => $data['currency_code'],
                    'external_reference' => $payment['external_reference'] ?? null,
                    'received_at' => now(),
                ]);
            }

            $this->auditLogger->record(
                $actor, 'sale.finalized', 'sale', $sale->id, $sale->branch_id, $correlationId,
                null, ['status' => 'completed', 'saleNumber' => $sale->sale_number, 'totalAmount' => $total],
            );

            return $sale->refresh()->load(['lines', 'payments']);
        });
    }

    public function void(Sale $sale, string $reason, User $actor, string $correlationId): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $actor, $correlationId) {
            $locked = Sale::query()->lockForUpdate()->with('lines')->findOrFail($sale->id);

            if ($locked->status !== 'completed') {
                throw new SaleException('ALREADY_REVERSED', 409, 'This sale has already been voided or refunded.');
            }

            foreach ($locked->lines as $line) {
                if (bccomp($line->stock_quantity_delta, '0', 4) === 0) {
                    continue;
                }

                $originalMovement = InventoryMovement::query()
                    ->where('reference_type', 'sale_line')
                    ->where('reference_id', $line->id)
                    ->first();

                $balance = $this->lockOrCreateBalance($locked->branch_id, $line->product_id);
                $this->applyMovement($balance, (string) bcmul($line->stock_quantity_delta, '-1', 4), 'reversal', $originalMovement?->id, $actor, $correlationId, $line->id);
            }

            $locked->status = 'voided';
            $locked->voided_at = now();
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $this->auditLogger->record(
                $actor, 'sale.voided', 'sale', $locked->id, $locked->branch_id, $correlationId,
                ['status' => 'completed'], ['status' => 'voided', 'reason' => $reason],
            );

            return $locked->refresh()->load(['lines', 'payments']);
        });
    }

    /**
     * @param array{lines:array, payments:array, reason:string, idempotency_key:string} $data
     */
    public function refund(Sale $sale, array $data, User $actor, string $correlationId): Sale
    {
        return DB::transaction(function () use ($sale, $data, $actor, $correlationId) {
            $locked = Sale::query()->lockForUpdate()->with('lines')->findOrFail($sale->id);

            if (! in_array($locked->status, ['completed', 'refunded'], true)) {
                throw new SaleException('ILLEGAL_STATE', 409, 'Only a completed sale can be refunded.');
            }

            if (count($data['lines']) === 0) {
                throw new SaleException('EMPTY_REFUND', 422, 'A refund must include at least one line.');
            }

            $alreadyRefunded = $this->refundedQuantitiesByProduct($locked->id);

            $lineNumber = 1;
            $builtLines = [];
            $subtotal = '0.0000';
            $taxTotal = '0.0000';
            $total = '0.0000';

            foreach ($data['lines'] as $refundInput) {
                $originalLine = $locked->lines->firstWhere('product_id', $refundInput['product_id']);
                if (! $originalLine) {
                    throw new SaleException('INVALID_PRODUCT_UNIT', 422, 'This product was not part of the original sale.');
                }

                $refundQuantity = (string) $refundInput['quantity'];
                if (bccomp($refundQuantity, '0', 4) <= 0) {
                    throw new SaleException('INVALID_QUANTITY', 422, "Refund quantity for {$originalLine->product_sku_snapshot} must be positive.");
                }

                $remaining = bcsub($originalLine->quantity, $alreadyRefunded->get($originalLine->product_id, '0.0000'), 4);
                if (bccomp($refundQuantity, $remaining, 4) === 1) {
                    throw new SaleException('QUANTITY_ALREADY_REFUNDED', 409, "Refund quantity for {$originalLine->product_sku_snapshot} exceeds the remaining refundable quantity.");
                }

                $grossAmount = bcmul($refundQuantity, $originalLine->unit_price, 4);
                $taxAmount = bcdiv(bcmul($grossAmount, $originalLine->tax_rate, 6), '100', 4);
                $lineTotal = bcadd($grossAmount, $taxAmount, 4);

                $builtLines[] = [
                    'line_number' => $lineNumber++,
                    'product' => Product::query()->findOrFail($originalLine->product_id),
                    'unit_id' => $originalLine->unit_id,
                    'quantity' => $refundQuantity,
                    'unit_price' => $originalLine->unit_price,
                    'tax_rate' => $originalLine->tax_rate,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $lineTotal,
                    'original_line_id' => $originalLine->id,
                ];

                $subtotal = bcadd($subtotal, $grossAmount, 4);
                $taxTotal = bcadd($taxTotal, $taxAmount, 4);
                $total = bcadd($total, $lineTotal, 4);
            }

            $paymentsTotal = array_reduce($data['payments'], fn ($carry, $payment) => bcadd($carry, (string) $payment['amount'], 4), '0.0000');
            if (bccomp($paymentsTotal, $total, 4) !== 0) {
                throw new SaleException('PAYMENT_TOTAL_MISMATCH', 422, 'The total refund payments do not equal the refunded amount.');
            }

            $refundSale = Sale::query()->create([
                'branch_id' => $locked->branch_id,
                'sale_number' => 'SALE-'.strtoupper(Str::random(10)),
                'status' => 'refunded',
                'currency_code' => $locked->currency_code,
                'sold_at' => now(),
                'completed_at' => now(),
                'refunded_at' => now(),
                'reverses_sale_id' => $locked->id,
                'subtotal_amount' => $subtotal,
                'discount_amount' => '0.0000',
                'tax_amount' => $taxTotal,
                'total_amount' => $total,
                'cashier_user_id' => $actor->id,
                'idempotency_key' => $data['idempotency_key'],
                'correlation_id' => $correlationId,
                'notes' => $data['reason'],
                'row_version' => 1,
            ]);

            foreach ($builtLines as $built) {
                $product = $built['product'];
                $stockDelta = $product->product_type === 'stock' ? $built['quantity'] : '0.0000';

                $line = SaleLine::query()->create([
                    'sale_id' => $refundSale->id,
                    'line_number' => $built['line_number'],
                    'product_id' => $product->id,
                    'unit_id' => $built['unit_id'],
                    'product_sku_snapshot' => $product->sku,
                    'product_name_snapshot' => $product->name,
                    'quantity' => $built['quantity'],
                    'stock_quantity_delta' => $stockDelta,
                    'unit_price' => $built['unit_price'],
                    'discount_amount' => '0.0000',
                    'tax_rate' => $built['tax_rate'],
                    'tax_amount' => $built['tax_amount'],
                    'line_total_amount' => $built['total_amount'],
                    'override_reason' => null,
                ]);

                if (bccomp($stockDelta, '0', 4) === 1) {
                    $originalMovement = InventoryMovement::query()
                        ->where('reference_type', 'sale_line')
                        ->where('reference_id', $built['original_line_id'])
                        ->first();

                    $balance = $this->lockOrCreateBalance($refundSale->branch_id, $product->id);
                    $this->applyMovement($balance, $stockDelta, 'reversal', $originalMovement?->id, $actor, $correlationId, $line->id);
                }
            }

            foreach ($data['payments'] as $payment) {
                SalePayment::query()->create([
                    'sale_id' => $refundSale->id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => (string) $payment['amount'],
                    'currency_code' => $locked->currency_code,
                    'external_reference' => $payment['external_reference'] ?? null,
                    'received_at' => now(),
                ]);
            }

            $refundedNow = $this->refundedQuantitiesByProduct($locked->id);
            $fullyRefunded = $locked->lines->every(
                fn (SaleLine $originalLine) => bccomp($refundedNow->get($originalLine->product_id, '0.0000'), $originalLine->quantity, 4) >= 0,
            );

            if ($fullyRefunded) {
                $locked->status = 'refunded';
                $locked->refunded_at = now();
            }
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            $this->auditLogger->record(
                $actor, 'sale.refunded', 'sale', $locked->id, $locked->branch_id, $correlationId,
                null, ['refundSaleId' => $refundSale->id, 'refundTotal' => $total, 'fullyRefunded' => $fullyRefunded, 'reason' => $data['reason']],
            );

            return $refundSale->refresh()->load(['lines', 'payments', 'reversesSale']);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, string> quantity refunded so far, keyed by product ID
     */
    private function refundedQuantitiesByProduct(int $originalSaleId): \Illuminate\Support\Collection
    {
        $refundSaleIds = Sale::query()->where('reverses_sale_id', $originalSaleId)->pluck('id');

        return SaleLine::query()
            ->whereIn('sale_id', $refundSaleIds)
            ->get()
            ->groupBy('product_id')
            ->map(fn ($lines) => $lines->reduce(fn ($carry, SaleLine $line) => bcadd($carry, $line->quantity, 4), '0.0000'));
    }

    /**
     * @param array{product_id:int, unit_id:int, quantity:numeric-string|float|int, requested_unit_price?:numeric-string|float|int|null, discount_amount?:numeric-string|float|int|null, override_reason?:?string} $lineInput
     * @return array{line_number:int, product:Product, unit_id:int, quantity:string, unit_price:string, discount_amount:string, tax_rate:string, gross_amount:string, tax_amount:string, total_amount:string, override_reason:?string}
     */
    private function buildSaleLine(array $lineInput, User $actor, ?User $approver, int $lineNumber): array
    {
        $product = Product::query()->findOrFail($lineInput['product_id']);

        if (! $product->is_active) {
            throw new SaleException('INVALID_PRODUCT_UNIT', 422, "{$product->sku} is not available for sale.");
        }

        if ((int) $lineInput['unit_id'] !== $product->stock_unit_id) {
            throw new SaleException('INVALID_PRODUCT_UNIT', 422, "The selected unit does not match {$product->sku}.");
        }

        $quantity = (string) $lineInput['quantity'];
        if (bccomp($quantity, '0', 4) <= 0) {
            throw new SaleException('INVALID_QUANTITY', 422, "Quantity for {$product->sku} must be positive.");
        }

        $basePrice = (string) $product->selling_price;
        $requestedPrice = array_key_exists('requested_unit_price', $lineInput) && $lineInput['requested_unit_price'] !== null
            ? (string) $lineInput['requested_unit_price']
            : null;
        $discount = (string) ($lineInput['discount_amount'] ?? '0');
        $overrideReason = $lineInput['override_reason'] ?? null;

        $priceOverridden = $requestedPrice !== null && bccomp($requestedPrice, $basePrice, 4) !== 0;
        $discountRequested = bccomp($discount, '0', 4) === 1;

        if ($priceOverridden) {
            if (empty($overrideReason)) {
                throw new SaleException('OVERRIDE_REASON_REQUIRED', 422, "An override reason is required to change the price for {$product->sku}.");
            }
            $this->assertOverrideAuthorized($actor, $approver, 'pos.price_override', 'PRICE_OVERRIDE_FORBIDDEN', "Overriding the price for {$product->sku} requires manager authorization.");
        }

        if ($discountRequested) {
            if (empty($overrideReason)) {
                throw new SaleException('OVERRIDE_REASON_REQUIRED', 422, "An override reason is required to discount {$product->sku}.");
            }
            $this->assertOverrideAuthorized($actor, $approver, 'pos.discount_override', 'DISCOUNT_FORBIDDEN', "Applying a discount to {$product->sku} requires manager authorization.");
        }

        $unitPrice = $priceOverridden ? $requestedPrice : $basePrice;
        $taxRate = (string) $product->default_tax_rate;

        $grossAmount = bcmul($quantity, $unitPrice, 4);
        $netAmount = bcsub($grossAmount, $discount, 4);
        if (bccomp($netAmount, '0', 4) === -1) {
            throw new SaleException('INVALID_DISCOUNT', 422, "The discount for {$product->sku} exceeds the line amount.");
        }
        $taxAmount = bcdiv(bcmul($netAmount, $taxRate, 6), '100', 4);
        $totalAmount = bcadd($netAmount, $taxAmount, 4);

        return [
            'line_number' => $lineNumber,
            'product' => $product,
            'unit_id' => (int) $lineInput['unit_id'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discount,
            'tax_rate' => $taxRate,
            'gross_amount' => $grossAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'override_reason' => ($priceOverridden || $discountRequested) ? $overrideReason : null,
        ];
    }

    private function assertOverrideAuthorized(User $actor, ?User $approver, string $permission, string $errorCode, string $message): void
    {
        if ($actor->hasPermission($permission)) {
            return;
        }

        if ($approver !== null && $approver->id !== $actor->id && $approver->hasPermission($permission)) {
            return;
        }

        throw new SaleException($errorCode, 403, $message);
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

    private function applyMovement(InventoryBalance $balance, string $quantityDelta, string $movementType, ?int $reversesMovementId, User $actor, string $correlationId, int $referenceId): void
    {
        $newOnHand = bcadd($balance->on_hand_quantity, $quantityDelta, 4);

        $balance->on_hand_quantity = $newOnHand;
        $balance->available_quantity = bcsub($newOnHand, $balance->reserved_quantity, 4);
        $balance->last_movement_at = now();
        $balance->row_version = $balance->row_version + 1;
        $balance->save();

        InventoryMovement::query()->create([
            'branch_id' => $balance->branch_id,
            'product_id' => $balance->product_id,
            'movement_type' => $movementType,
            'quantity_delta' => $quantityDelta,
            'on_hand_after_quantity' => $newOnHand,
            'reference_type' => 'sale_line',
            'reference_id' => $referenceId,
            'reverses_movement_id' => $reversesMovementId,
            'effective_at' => now(),
            'posted_at' => now(),
            'actor_user_id' => $actor->id,
            'correlation_id' => $correlationId,
        ]);
    }
}
