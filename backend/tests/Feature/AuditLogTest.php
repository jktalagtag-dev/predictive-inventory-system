<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Governance\Models\AuditLog;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\Supplier;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/audit-logs')->assertStatus(401);
    }

    public function test_staff_cannot_view_audit_logs(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $this->getJson('/api/v1/audit-logs')->assertStatus(403);
    }

    public function test_manager_can_view_audit_logs(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $this->getJson('/api/v1/audit-logs')->assertOk();
    }

    public function test_purchase_order_lifecycle_writes_linked_audit_entries(): void
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
                'unitId' => UnitOfMeasure::query()->where('code', 'EA')->value('id'),
                'orderedQuantity' => 10,
                'unitCost' => '100.0000',
            ]],
        ])->json('data.id');

        $this->assertSame(1, AuditLog::query()->where('entity_type', 'purchase_order')->where('entity_id', $poId)->where('action', 'purchase_order.created')->count());

        $this->postJson("/api/v1/purchase-orders/{$poId}/submit", ['version' => 1])->assertOk();
        $this->assertSame(1, AuditLog::query()->where('entity_type', 'purchase_order')->where('entity_id', $poId)->where('action', 'purchase_order.submitted')->count());

        $this->actAs($owner);
        $this->postJson("/api/v1/purchase-orders/{$poId}/approvals", ['decision' => 'approved', 'version' => 2])->assertOk();

        $approvalEntry = AuditLog::query()->where('entity_type', 'purchase_order')->where('entity_id', $poId)->where('action', 'purchase_order.approved')->first();
        $this->assertNotNull($approvalEntry);
        $this->assertSame($owner->id, $approvalEntry->actor_user_id);
        $this->assertSame('approved', $approvalEntry->changes['after']['status']);
        $this->assertNotEmpty($approvalEntry->correlation_id);
    }

    public function test_inventory_adjustment_post_and_reverse_write_audit_entries(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $adjustmentId = $this->postJson('/api/v1/inventory/adjustments', [
            'branchId' => $this->branch->id,
            'reasonCode' => 'count_correction',
            'effectiveAt' => now()->toIso8601String(),
            'lines' => [['productId' => $this->product->id, 'quantityDelta' => 25]],
        ])->json('data.id');

        $this->actAs($owner);
        $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve", ['version' => 1])->assertOk();
        $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/post", ['version' => 2], ['Idempotency-Key' => 'audit-post-1'])->assertOk();
        $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/reverse", ['version' => 3, 'reason' => 'Miscount'], ['Idempotency-Key' => 'audit-reverse-1'])->assertOk();

        $this->assertSame(1, AuditLog::query()->where('entity_type', 'inventory_adjustment')->where('entity_id', $adjustmentId)->where('action', 'inventory_adjustment.posted')->count());
        $this->assertSame(1, AuditLog::query()->where('entity_type', 'inventory_adjustment')->where('entity_id', $adjustmentId)->where('action', 'inventory_adjustment.reversed')->count());
    }

    public function test_searching_audit_logs_is_itself_audited(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $this->getJson('/api/v1/audit-logs')->assertOk();

        $this->assertSame(1, AuditLog::query()->where('action', 'audit_log.searched')->where('actor_user_id', $owner->id)->count());
    }

    public function test_audit_logs_can_be_filtered_by_entity(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'branchId' => $this->branch->id,
            'supplierId' => $this->supplier->id,
            'currencyCode' => 'PHP',
            'lines' => [[
                'productId' => $this->product->id,
                'unitId' => UnitOfMeasure::query()->where('code', 'EA')->value('id'),
                'orderedQuantity' => 5,
                'unitCost' => '50.0000',
            ]],
        ])->json('data.id');

        $response = $this->getJson("/api/v1/audit-logs?entityType=purchase_order&entityId={$poId}");
        $response->assertOk();
        $response->assertJsonPath('data.0.entityType', 'purchase_order');
        $response->assertJsonPath('data.0.entityId', (string) $poId);
    }
}
