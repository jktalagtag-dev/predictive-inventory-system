<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Governance\Models\AuditLog;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryAdjustment;
use App\Domains\Inventory\Models\InventoryBalance;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncTest extends TestCase
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
            'name' => 'Filter', 'product_type' => 'stock', 'default_tax_rate' => '12.0000',
            'selling_price' => '100.0000', 'row_version' => 1,
        ]);

        InventoryBalance::query()->create([
            'branch_id' => $this->branch->id, 'product_id' => $this->product->id,
            'on_hand_quantity' => '10.0000', 'reserved_quantity' => '0.0000',
            'available_quantity' => '10.0000', 'incoming_quantity' => '0.0000', 'row_version' => 1,
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

    private function adjustmentOperation(string $clientOperationId, float $quantityDelta = 5, ?string $idempotencyKey = null, ?string $dependencyOperationId = null): array
    {
        return [
            'clientOperationId' => $clientOperationId,
            'operationType' => 'inventory_adjustment.create',
            'branchId' => $this->branch->id,
            'payloadVersion' => 1,
            'idempotencyKey' => $idempotencyKey ?? Str::uuid()->toString(),
            'dependencyOperationId' => $dependencyOperationId,
            'payload' => [
                'reasonCode' => 'count_correction',
                'reasonNote' => 'Cycle count offline',
                'effectiveAt' => now()->toIso8601String(),
                'lines' => [
                    ['productId' => $this->product->id, 'quantityDelta' => $quantityDelta, 'notes' => null],
                ],
            ],
        ];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/v1/sync/operations', ['operations' => []])->assertStatus(401);
    }

    public function test_valid_operation_is_accepted_and_creates_the_resource(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $clientOperationId = Str::uuid()->toString();
        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$this->adjustmentOperation($clientOperationId)]]);

        $response->assertOk();
        $response->assertJsonPath("data.results.{$clientOperationId}.status", 'accepted');
        $response->assertJsonPath("data.results.{$clientOperationId}.serverResource.type", 'inventory_adjustment');
        $this->assertSame(1, InventoryAdjustment::query()->count());

        $this->assertTrue(AuditLog::query()->where('action', 'sync_operation.accepted')->exists());
    }

    public function test_replaying_the_same_client_operation_id_does_not_duplicate_the_resource(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $operation = $this->adjustmentOperation(Str::uuid()->toString());

        $first = $this->postJson('/api/v1/sync/operations', ['operations' => [$operation]]);
        $first->assertOk();
        $firstResourceId = $first->json("data.results.{$operation['clientOperationId']}.serverResource.id");

        $second = $this->postJson('/api/v1/sync/operations', ['operations' => [$operation]]);
        $second->assertOk();
        $second->assertJsonPath("data.results.{$operation['clientOperationId']}.status", 'accepted');
        $this->assertSame($firstResourceId, $second->json("data.results.{$operation['clientOperationId']}.serverResource.id"));

        $this->assertSame(1, InventoryAdjustment::query()->count());
    }

    public function test_same_client_operation_id_with_changed_payload_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $clientOperationId = Str::uuid()->toString();
        $this->postJson('/api/v1/sync/operations', ['operations' => [$this->adjustmentOperation($clientOperationId, 5)]])->assertOk();

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$this->adjustmentOperation($clientOperationId, 7)]]);
        $response->assertOk();
        $response->assertJsonPath("data.results.{$clientOperationId}.status", 'rejected');
        $response->assertJsonPath("data.results.{$clientOperationId}.error.code", 'DUPLICATE_OPERATION');
        $this->assertSame(1, InventoryAdjustment::query()->count());
    }

    public function test_same_idempotency_key_with_different_payload_across_different_operation_ids_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $sharedKey = Str::uuid()->toString();
        $first = $this->adjustmentOperation(Str::uuid()->toString(), 5, $sharedKey);
        $second = $this->adjustmentOperation(Str::uuid()->toString(), 9, $sharedKey);

        $this->postJson('/api/v1/sync/operations', ['operations' => [$first]])->assertOk();

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$second]]);
        $response->assertOk();
        $response->assertJsonPath("data.results.{$second['clientOperationId']}.status", 'rejected');
        $response->assertJsonPath("data.results.{$second['clientOperationId']}.error.code", 'DUPLICATE_OPERATION');
        $this->assertSame(1, InventoryAdjustment::query()->count());
    }

    public function test_dependent_operation_is_blocked_until_prerequisite_is_accepted(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $prerequisiteId = Str::uuid()->toString();
        $dependentId = Str::uuid()->toString();

        // Prerequisite fails validation (unsupported operation type), so it
        // never reaches 'accepted' — the dependent must stay blocked.
        $failingPrerequisite = [
            'clientOperationId' => $prerequisiteId,
            'operationType' => 'not_a_real_operation',
            'branchId' => $this->branch->id,
            'payloadVersion' => 1,
            'idempotencyKey' => Str::uuid()->toString(),
            'dependencyOperationId' => null,
            'payload' => ['note' => 'placeholder'],
        ];
        $dependent = $this->adjustmentOperation($dependentId, 3, null, $prerequisiteId);

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$failingPrerequisite, $dependent]]);
        $response->assertOk();
        $response->assertJsonPath("data.results.{$prerequisiteId}.status", 'rejected');
        $response->assertJsonPath("data.results.{$dependentId}.status", 'pending_dependency');
        $this->assertSame(0, InventoryAdjustment::query()->count());
    }

    public function test_dependent_operation_processes_once_prerequisite_is_accepted_in_the_same_batch(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $prerequisiteId = Str::uuid()->toString();
        $dependentId = Str::uuid()->toString();

        $prerequisite = $this->adjustmentOperation($prerequisiteId, 2);
        $dependent = $this->adjustmentOperation($dependentId, 3, null, $prerequisiteId);

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$prerequisite, $dependent]]);
        $response->assertOk();
        $response->assertJsonPath("data.results.{$prerequisiteId}.status", 'accepted');
        $response->assertJsonPath("data.results.{$dependentId}.status", 'accepted');
        $this->assertSame(2, InventoryAdjustment::query()->count());
    }

    public function test_stale_stock_snapshot_produces_a_conflict_not_a_plain_rejection(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        // Only 10 on hand; requesting a delta that drives it negative.
        $clientOperationId = Str::uuid()->toString();
        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$this->adjustmentOperation($clientOperationId, -50)]]);

        $response->assertOk();
        $response->assertJsonPath("data.results.{$clientOperationId}.status", 'conflicted');
        $response->assertJsonPath("data.results.{$clientOperationId}.error.code", 'STALE_STOCK_SNAPSHOT');
        $this->assertNotNull($response->json("data.results.{$clientOperationId}.error.conflictPayload"));
        $this->assertTrue(AuditLog::query()->where('action', 'sync_operation.conflicted')->exists());
    }

    public function test_unsupported_operation_type_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $clientOperationId = Str::uuid()->toString();
        $operation = [
            'clientOperationId' => $clientOperationId,
            'operationType' => 'sales.finalize',
            'branchId' => $this->branch->id,
            'payloadVersion' => 1,
            'idempotencyKey' => Str::uuid()->toString(),
            'dependencyOperationId' => null,
            'payload' => ['note' => 'placeholder'],
        ];

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$operation]]);
        $response->assertOk();
        $response->assertJsonPath("data.results.{$clientOperationId}.status", 'rejected');
        $response->assertJsonPath("data.results.{$clientOperationId}.error.code", 'UNSUPPORTED_OFFLINE_OPERATION');
    }

    public function test_unsupported_payload_version_is_rejected(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $operation = $this->adjustmentOperation(Str::uuid()->toString());
        $operation['payloadVersion'] = 99;

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$operation]]);
        $response->assertOk();
        $response->assertJsonPath("data.results.{$operation['clientOperationId']}.status", 'rejected');
        $response->assertJsonPath("data.results.{$operation['clientOperationId']}.error.code", 'UNSUPPORTED_PAYLOAD_VERSION');
    }

    public function test_operation_outside_actor_branch_scope_is_rejected(): void
    {
        $otherBranch = Branch::query()->create(['code' => 'ANNEX', 'name' => 'Annex Branch', 'country_code' => 'PH', 'row_version' => 1]);
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $operation = $this->adjustmentOperation(Str::uuid()->toString());
        $operation['branchId'] = $otherBranch->id;

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$operation]]);
        $response->assertOk();
        $response->assertJsonPath("data.results.{$operation['clientOperationId']}.status", 'rejected');
        $response->assertJsonPath("data.results.{$operation['clientOperationId']}.error.code", 'FORBIDDEN');
    }

    public function test_status_endpoint_is_visible_to_originator_and_denied_to_unrelated_staff(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $operation = $this->adjustmentOperation(Str::uuid()->toString());
        $this->postJson('/api/v1/sync/operations', ['operations' => [$operation]])->assertOk();

        $ownStatus = $this->getJson("/api/v1/sync/operations/{$operation['clientOperationId']}");
        $ownStatus->assertOk();
        $ownStatus->assertJsonPath('data.status', 'accepted');

        $otherStaff = $this->userWithRole('staff');
        $this->actAs($otherStaff);
        $this->getJson("/api/v1/sync/operations/{$operation['clientOperationId']}")->assertStatus(403);

        $manager = $this->userWithRole('manager');
        $this->actAs($manager);
        $this->getJson("/api/v1/sync/operations/{$operation['clientOperationId']}")->assertOk();
    }

    public function test_batch_rejects_duplicate_client_operation_ids_within_the_same_request(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $operation = $this->adjustmentOperation(Str::uuid()->toString());

        $response = $this->postJson('/api/v1/sync/operations', ['operations' => [$operation, $operation]]);
        $response->assertStatus(422);
    }
}
