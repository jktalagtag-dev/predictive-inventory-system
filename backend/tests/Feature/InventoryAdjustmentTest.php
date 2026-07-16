<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Product $product;

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

    /**
     * Sanctum's RequestGuard caches the resolved user for the lifetime of
     * the guard instance, so switching actors mid-test via actingAs()
     * alone does not take effect on the next 'auth:sanctum' request.
     * Forgetting resolved guards forces re-resolution against the newly
     * authenticated user.
     */
    private function actAs(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');
    }

    private function draftPayload(float $delta = 50): array
    {
        return [
            'branchId' => $this->branch->id,
            'reasonCode' => 'count_correction',
            'effectiveAt' => now()->toIso8601String(),
            'lines' => [['productId' => $this->product->id, 'quantityDelta' => $delta]],
        ];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/inventory/adjustments?branchId=1')->assertStatus(401);
    }

    public function test_full_lifecycle_draft_approve_post_reverse(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $create = $this->postJson('/api/v1/inventory/adjustments', $this->draftPayload(50));
        $create->assertCreated();
        $create->assertJsonPath('data.status', 'pending_approval');
        $create->assertJsonPath('data.lines.0.afterQuantity', '50.0000');
        $adjustmentId = $create->json('data.id');

        // Manager cannot approve their own draft.
        $selfApprove = $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve", ['version' => 1]);
        $selfApprove->assertStatus(403);
        $selfApprove->assertJsonPath('error.code', 'SELF_APPROVAL_DENIED');

        $this->actAs($owner);
        $approve = $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve", ['version' => 1]);
        $approve->assertOk();
        $approve->assertJsonPath('data.approvedByUserId', (string) $owner->id);

        $post = $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/post", ['version' => 2], ['Idempotency-Key' => 'post-key-1']);
        $post->assertOk();
        $post->assertJsonPath('data.status', 'posted');

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('50.0000', $balance->on_hand_quantity);

        $reverse = $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/reverse", ['version' => 3, 'reason' => 'Miscount'], ['Idempotency-Key' => 'reverse-key-1']);
        $reverse->assertOk();
        $reverse->assertJsonPath('data.status', 'reversed');

        $balance->refresh();
        $this->assertSame('0.0000', $balance->on_hand_quantity);
    }

    public function test_posting_is_idempotent_and_does_not_double_apply(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $adjustmentId = $this->postJson('/api/v1/inventory/adjustments', $this->draftPayload(20))->json('data.id');

        $this->actAs($owner);
        $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve", ['version' => 1])->assertOk();

        $first = $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/post", ['version' => 2], ['Idempotency-Key' => 'same-key']);
        $first->assertOk();

        $second = $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/post", ['version' => 2], ['Idempotency-Key' => 'same-key']);
        $second->assertOk();

        $balance = InventoryBalance::query()->where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertSame('20.0000', $balance->on_hand_quantity);
    }

    public function test_posting_without_idempotency_key_is_rejected(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $adjustmentId = $this->postJson('/api/v1/inventory/adjustments', $this->draftPayload(10))->json('data.id');

        $this->actAs($owner);
        $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve", ['version' => 1])->assertOk();

        $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/post", ['version' => 2])->assertStatus(422);
    }

    public function test_negative_stock_is_rejected_on_draft_creation(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $response = $this->postJson('/api/v1/inventory/adjustments', $this->draftPayload(-5));

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'NEGATIVE_STOCK');
    }

    public function test_stale_version_on_approve_returns_conflict(): void
    {
        $manager = $this->userWithRole('manager');
        $owner = $this->userWithRole('owner');

        $this->actAs($manager);
        $adjustmentId = $this->postJson('/api/v1/inventory/adjustments', $this->draftPayload(10))->json('data.id');

        $this->actAs($owner);
        $response = $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve", ['version' => 999]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'VERSION_CONFLICT');
    }

    public function test_user_without_branch_access_cannot_view_adjustments(): void
    {
        $staff = $this->userWithRole('staff', assignBranch: false);
        $this->actAs($staff);

        $this->getJson("/api/v1/inventory/adjustments?branchId={$this->branch->id}")->assertStatus(403);
    }

    public function test_staff_can_create_draft_but_not_approve(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $create = $this->postJson('/api/v1/inventory/adjustments', $this->draftPayload(5));
        $create->assertCreated();

        $adjustmentId = $create->json('data.id');
        $this->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve", ['version' => 1])->assertStatus(403);
    }
}
