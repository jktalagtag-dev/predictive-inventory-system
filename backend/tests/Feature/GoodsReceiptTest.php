<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Procurement\Models\Supplier;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Product $product;

    private Supplier $supplier;

    private UnitOfMeasure $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->branch = Branch::query()->create(['code' => 'MAIN', 'name' => 'Main Branch', 'country_code' => 'PH', 'row_version' => 1]);
        $category = Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);
        $this->unit = UnitOfMeasure::query()->create(['code' => 'EA', 'name' => 'Each', 'symbol' => 'ea', 'dimension' => 'count', 'is_active' => true, 'row_version' => 1]);
        $this->product = Product::query()->create([
            'category_id' => $category->id, 'stock_unit_id' => $this->unit->id, 'sku' => 'SHX-FLT-010',
            'name' => 'Filter', 'product_type' => 'stock', 'default_tax_rate' => '12.0000', 'row_version' => 1,
        ]);
        $this->supplier = Supplier::query()->create([
            'code' => 'AQUAPURE', 'legal_name' => 'AquaPure', 'country_code' => 'PH',
            'default_currency_code' => 'PHP', 'row_version' => 1,
        ]);
    }

    private function userWithRole(string $roleCode, bool $assignBranch = true): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role->id, ['effective_from' => now(), 'created_at' => now()]);

        if ($assignBranch) {
            $user->branches()->attach($this->branch->id, ['is_default' => true, 'created_at' => now()]);
        }

        return $user;
    }

    /** @see InventoryAdjustmentTest::actAs() for why forgetGuards() is required. */
    private function actAs(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');
    }

    /**
     * Creates and fully advances a purchase order (draft -> submitted ->
     * approved -> ordered) for the given quantity, returning its PO id and
     * line id. Uses a fresh manager/owner pair so it never collides with
     * the self-approval rule.
     */
    private function createOrderedPurchaseOrder(float $orderedQuantity = 10): array
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $poId = $this->postJson('/api/v1/purchase-orders', [
            'branchId' => $this->branch->id,
            'supplierId' => $this->supplier->id,
            'currencyCode' => 'PHP',
            'lines' => [[
                'productId' => $this->product->id,
                'unitId' => $this->unit->id,
                'orderedQuantity' => $orderedQuantity,
                'unitCost' => '100.0000',
            ]],
        ])->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/submit", ['version' => 1])->assertOk();

        $this->actAs($owner);
        $this->postJson("/api/v1/purchase-orders/{$poId}/approvals", ['decision' => 'approved', 'version' => 2])->assertOk();
        $this->postJson("/api/v1/purchase-orders/{$poId}/mark-ordered", ['orderedAt' => now()->toIso8601String(), 'version' => 3])->assertOk();

        $poLineId = PurchaseOrder::query()->find($poId)->lines()->first()->id;

        return [$poId, $poLineId];
    }

    private function receiptPayload(int $poId, int $poLineId, float $received, float $accepted, float $rejected = 0, ?string $deliveryNumber = null): array
    {
        return [
            'branchId' => $this->branch->id,
            'purchaseOrderId' => $poId,
            'supplierDeliveryNumber' => $deliveryNumber,
            'receivedAt' => now()->toIso8601String(),
            'lines' => [[
                'purchaseOrderLineId' => $poLineId,
                'receivedQuantity' => $received,
                'acceptedQuantity' => $accepted,
                'rejectedQuantity' => $rejected,
                'rejectionReason' => $rejected > 0 ? 'Damaged in transit' : null,
            ]],
        ];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/goods-receipts?branchId=1')->assertStatus(401);
    }

    public function test_full_receipt_fully_receives_the_purchase_order_and_updates_balance(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $create = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 10, 10));
        $create->assertCreated();
        $create->assertJsonPath('data.status', 'draft');
        $receiptId = $create->json('data.id');

        $post = $this->postJson("/api/v1/goods-receipts/{$receiptId}/post", ['version' => 1], ['Idempotency-Key' => 'receipt-post-1']);
        $post->assertOk();
        $post->assertJsonPath('data.status', 'posted');

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('10.0000', $balance->on_hand_quantity);

        $this->assertSame('received', PurchaseOrder::query()->find($poId)->status);
    }

    public function test_partial_receipt_marks_po_partially_received_then_received_on_completion(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $firstId = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 6, 6))->json('data.id');
        $this->postJson("/api/v1/goods-receipts/{$firstId}/post", ['version' => 1], ['Idempotency-Key' => 'partial-1'])->assertOk();

        $this->assertSame('partially_received', PurchaseOrder::query()->find($poId)->status);

        $secondId = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 4, 4))->json('data.id');
        $this->postJson("/api/v1/goods-receipts/{$secondId}/post", ['version' => 1], ['Idempotency-Key' => 'partial-2'])->assertOk();

        $this->assertSame('received', PurchaseOrder::query()->find($poId)->status);

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('10.0000', $balance->on_hand_quantity);
    }

    public function test_over_receipt_beyond_ordered_quantity_is_rejected(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $response = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 15, 15));
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'OVER_RECEIPT_NOT_ALLOWED');
    }

    public function test_rejected_quantity_without_reason_is_rejected(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $payload = $this->receiptPayload($poId, $poLineId, 10, 8, 2);
        $payload['lines'][0]['rejectionReason'] = null;

        $response = $this->postJson('/api/v1/goods-receipts', $payload);
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'REJECTION_REASON_REQUIRED');
    }

    public function test_accepted_plus_rejected_must_equal_received(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $response = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 10, 5, 2));
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'QUANTITY_MISMATCH');
    }

    public function test_duplicate_supplier_delivery_reference_is_rejected(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 5, 5, 0, 'DR-001'))->assertCreated();

        $response = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 5, 5, 0, 'DR-001'));
        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'DUPLICATE_DELIVERY_REFERENCE');
    }

    public function test_receiving_against_a_draft_purchase_order_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'branchId' => $this->branch->id,
            'supplierId' => $this->supplier->id,
            'currencyCode' => 'PHP',
            'lines' => [['productId' => $this->product->id, 'unitId' => $this->unit->id, 'orderedQuantity' => 10, 'unitCost' => '100.0000']],
        ])->json('data.id');
        $poLineId = PurchaseOrder::query()->find($poId)->lines()->first()->id;

        $response = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 5, 5));
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'PO_NOT_RECEIVABLE');
    }

    public function test_posting_is_idempotent_and_does_not_double_apply(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $receiptId = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 10, 10))->json('data.id');

        $first = $this->postJson("/api/v1/goods-receipts/{$receiptId}/post", ['version' => 1], ['Idempotency-Key' => 'same-key']);
        $first->assertOk();

        $second = $this->postJson("/api/v1/goods-receipts/{$receiptId}/post", ['version' => 1], ['Idempotency-Key' => 'same-key']);
        $second->assertOk();

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('10.0000', $balance->on_hand_quantity);
    }

    public function test_posting_without_idempotency_key_is_rejected(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $receiptId = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 10, 10))->json('data.id');

        $this->postJson("/api/v1/goods-receipts/{$receiptId}/post", ['version' => 1])->assertStatus(422);
    }

    public function test_reversal_restores_balance_and_purchase_order_status(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $receiptId = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 10, 10))->json('data.id');
        $this->postJson("/api/v1/goods-receipts/{$receiptId}/post", ['version' => 1], ['Idempotency-Key' => 'reversal-post'])->assertOk();

        $this->assertSame('received', PurchaseOrder::query()->find($poId)->status);

        $reverse = $this->postJson("/api/v1/goods-receipts/{$receiptId}/reverse", ['version' => 2, 'reason' => 'Wrong item delivered'], ['Idempotency-Key' => 'reversal-1']);
        $reverse->assertOk();
        $reverse->assertJsonPath('data.status', 'reversed');
        $this->assertNotNull($reverse->json('data.reversalReceiptId'));

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('0.0000', $balance->on_hand_quantity);

        $this->assertSame('ordered', PurchaseOrder::query()->find($poId)->status);
    }

    public function test_staff_can_create_draft_but_not_post(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $create = $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 10, 10));
        $create->assertCreated();

        $receiptId = $create->json('data.id');
        $this->postJson("/api/v1/goods-receipts/{$receiptId}/post", ['version' => 1], ['Idempotency-Key' => 'staff-post'])->assertStatus(403);
    }

    public function test_list_endpoint_reports_line_count_without_loading_full_lines(): void
    {
        [$poId, $poLineId] = $this->createOrderedPurchaseOrder(10);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $this->postJson('/api/v1/goods-receipts', $this->receiptPayload($poId, $poLineId, 10, 10))->assertCreated();

        $index = $this->getJson("/api/v1/goods-receipts?branchId={$this->branch->id}");
        $index->assertOk();
        $index->assertJsonPath('data.0.lineCount', 1);
        $index->assertJsonPath('data.0.lines', []);
    }

    public function test_user_without_branch_access_cannot_view_goods_receipts(): void
    {
        $staff = $this->userWithRole('staff', assignBranch: false);
        $this->actAs($staff);

        $this->getJson("/api/v1/goods-receipts?branchId={$this->branch->id}")->assertStatus(403);
    }
}
