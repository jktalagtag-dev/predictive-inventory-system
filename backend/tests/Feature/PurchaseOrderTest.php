<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\Supplier;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Product $product;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->branch = Branch::query()->create(['code' => 'MAIN', 'name' => 'Main Branch', 'country_code' => 'PH', 'row_version' => 1]);
        $category = Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);
        $unit = UnitOfMeasure::query()->create(['code' => 'EA', 'name' => 'Each', 'symbol' => 'ea', 'dimension' => 'count', 'is_active' => true, 'row_version' => 1]);
        $this->product = Product::query()->create([
            'category_id' => $category->id, 'stock_unit_id' => $unit->id, 'sku' => 'SHX-FLT-010',
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

    private function draftPayload(): array
    {
        return [
            'branchId' => $this->branch->id,
            'supplierId' => $this->supplier->id,
            'currencyCode' => 'PHP',
            'lines' => [[
                'productId' => $this->product->id,
                'unitId' => UnitOfMeasure::query()->where('code', 'EA')->value('id'),
                'orderedQuantity' => 10,
                'unitCost' => '100.0000',
                'taxRate' => '12.0000',
            ]],
        ];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/purchase-orders?branchId=1')->assertStatus(401);
    }

    public function test_draft_computes_line_and_header_totals(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $response = $this->postJson('/api/v1/purchase-orders', $this->draftPayload());

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'draft');
        // net = 10 * 100 = 1000; tax = 1000 * 12% = 120; total = 1120
        $response->assertJsonPath('data.lines.0.netAmount', '1000.0000');
        $response->assertJsonPath('data.lines.0.taxAmount', '120.0000');
        $response->assertJsonPath('data.lines.0.totalAmount', '1120.0000');
        $response->assertJsonPath('data.subtotalAmount', '1000.0000');
        $response->assertJsonPath('data.totalAmount', '1120.0000');
    }

    public function test_full_lifecycle_draft_submit_approve_order_close(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $create = $this->postJson('/api/v1/purchase-orders', $this->draftPayload());
        $poId = $create->json('data.id');

        $submit = $this->postJson("/api/v1/purchase-orders/{$poId}/submit", ['version' => 1]);
        $submit->assertOk();
        $submit->assertJsonPath('data.status', 'submitted');

        // Manager cannot approve their own submission.
        $selfApprove = $this->postJson("/api/v1/purchase-orders/{$poId}/approvals", ['decision' => 'approved', 'version' => 2]);
        $selfApprove->assertStatus(403);
        $selfApprove->assertJsonPath('error.code', 'SELF_APPROVAL_DENIED');

        $this->actAs($owner);
        $approve = $this->postJson("/api/v1/purchase-orders/{$poId}/approvals", ['decision' => 'approved', 'version' => 2]);
        $approve->assertOk();
        $approve->assertJsonPath('data.status', 'approved');

        $order = $this->postJson("/api/v1/purchase-orders/{$poId}/mark-ordered", ['orderedAt' => now()->toIso8601String(), 'version' => 3]);
        $order->assertOk();
        $order->assertJsonPath('data.status', 'ordered');

        $close = $this->postJson("/api/v1/purchase-orders/{$poId}/close", ['version' => 4]);
        $close->assertOk();
        $close->assertJsonPath('data.status', 'closed');
    }

    public function test_rejection_reverts_to_draft(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $poId = $this->postJson('/api/v1/purchase-orders', $this->draftPayload())->json('data.id');
        $this->postJson("/api/v1/purchase-orders/{$poId}/submit", ['version' => 1])->assertOk();

        $this->actAs($owner);
        $reject = $this->postJson("/api/v1/purchase-orders/{$poId}/approvals", ['decision' => 'rejected', 'reason' => 'Unit cost too high', 'version' => 2]);
        $reject->assertOk();
        $reject->assertJsonPath('data.status', 'draft');
    }

    public function test_rejection_without_reason_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $poId = $this->postJson('/api/v1/purchase-orders', $this->draftPayload())->json('data.id');
        $this->postJson("/api/v1/purchase-orders/{$poId}/submit", ['version' => 1])->assertOk();

        $this->actAs($owner);
        $reject = $this->postJson("/api/v1/purchase-orders/{$poId}/approvals", ['decision' => 'rejected', 'version' => 2]);
        $reject->assertStatus(422);
    }

    public function test_inactive_supplier_cannot_receive_new_purchase_orders(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $this->supplier->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/purchase-orders', $this->draftPayload());
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INACTIVE_SUPPLIER');
    }

    public function test_submitting_without_lines_is_impossible_since_creation_requires_lines(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $payload = $this->draftPayload();
        $payload['lines'] = [];

        $response = $this->postJson('/api/v1/purchase-orders', $payload);
        $response->assertStatus(422);
    }

    public function test_cancel_requires_reason_and_is_blocked_after_closure(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $poId = $this->postJson('/api/v1/purchase-orders', $this->draftPayload())->json('data.id');

        $noReason = $this->postJson("/api/v1/purchase-orders/{$poId}/cancel", ['version' => 1]);
        $noReason->assertStatus(422);

        $cancel = $this->postJson("/api/v1/purchase-orders/{$poId}/cancel", ['reason' => 'No longer needed', 'version' => 1]);
        $cancel->assertOk();
        $cancel->assertJsonPath('data.status', 'cancelled');

        $cancelAgain = $this->postJson("/api/v1/purchase-orders/{$poId}/cancel", ['reason' => 'Retry', 'version' => 2]);
        $cancelAgain->assertStatus(409);
    }

    public function test_stale_version_on_submit_returns_conflict(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $poId = $this->postJson('/api/v1/purchase-orders', $this->draftPayload())->json('data.id');

        $response = $this->postJson("/api/v1/purchase-orders/{$poId}/submit", ['version' => 999]);
        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'VERSION_CONFLICT');
    }

    public function test_user_without_branch_access_cannot_view_purchase_orders(): void
    {
        $staff = $this->userWithRole('staff', assignBranch: false);
        $this->actAs($staff);

        $this->getJson("/api/v1/purchase-orders?branchId={$this->branch->id}")->assertStatus(403);
    }
}
