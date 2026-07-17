<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Reporting\Models\ReportExport;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
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
            'on_hand_quantity' => '40.0000', 'reserved_quantity' => '0.0000',
            'available_quantity' => '40.0000', 'incoming_quantity' => '0.0000', 'row_version' => 1,
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
        $this->getJson('/api/v1/reports')->assertStatus(401);
    }

    public function test_catalog_is_permission_filtered(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson('/api/v1/reports');
        $response->assertOk();
        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertContains('inventory-on-hand', $codes);
        $this->assertContains('sales-summary', $codes);

        $staff = $this->userWithRole('staff');
        $this->actAs($staff);
        $this->getJson('/api/v1/reports')->assertStatus(403);
    }

    public function test_running_report_without_required_filter_is_rejected(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson('/api/v1/reports/inventory-on-hand');
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_REPORT_FILTER');
    }

    public function test_unknown_report_code_returns_404(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson('/api/v1/reports/not-a-real-report');
        $response->assertStatus(404);
        $response->assertJsonPath('error.code', 'UNKNOWN_REPORT');
    }

    public function test_inventory_on_hand_report_returns_scoped_rows_and_freshness_metadata(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson("/api/v1/reports/inventory-on-hand?branchId={$this->branch->id}");
        $response->assertOk();
        $response->assertJsonPath('data.rows.0.sku', 'SHX-FLT-010');
        $response->assertJsonPath('data.aggregates.totalOnHandQuantity', '40.0000');
        $response->assertJsonPath('meta.freshness', 'live');
        $response->assertJsonPath('meta.accessClassification', 'internal');
        $this->assertNotNull($response->json('meta.generatedAt'));
        $this->assertNotNull($response->json('meta.timezone'));
    }

    public function test_report_scope_rejects_unauthorized_branch(): void
    {
        // Owners have organization-wide branch visibility by design
        // (CLAUDE.md section 6), so this must use a Manager — the first
        // role actually restricted to its assigned branches — to prove
        // the scope check.
        $otherBranch = Branch::query()->create(['code' => 'ANNEX', 'name' => 'Annex Branch', 'country_code' => 'PH', 'row_version' => 1]);
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $response = $this->getJson("/api/v1/reports/inventory-on-hand?branchId={$otherBranch->id}");
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_REPORT_FILTER');
    }

    public function test_export_requires_idempotency_key(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->postJson('/api/v1/report-exports', [
            'reportCode' => 'inventory-on-hand',
            'format' => 'csv',
            'branchId' => $this->branch->id,
            'filters' => ['branchId' => $this->branch->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_export_creation_generates_downloadable_file_and_is_idempotent(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $payload = [
            'reportCode' => 'inventory-on-hand',
            'format' => 'csv',
            'branchId' => $this->branch->id,
            'filters' => ['branchId' => $this->branch->id],
        ];

        $first = $this->postJson('/api/v1/report-exports', $payload, ['Idempotency-Key' => 'export-1']);
        $first->assertStatus(201);
        $first->assertJsonPath('data.status', 'completed');
        $first->assertJsonPath('data.format', 'csv');
        $exportId = $first->json('data.id');

        $second = $this->postJson('/api/v1/report-exports', $payload, ['Idempotency-Key' => 'export-1']);
        $second->assertStatus(201);
        $this->assertSame($exportId, $second->json('data.id'));

        $download = $this->get("/api/v1/report-exports/{$exportId}/download");
        $download->assertOk();
        $download->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_download_authorization_is_rechecked_for_a_different_user(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $payload = [
            'reportCode' => 'inventory-on-hand',
            'format' => 'csv',
            'branchId' => $this->branch->id,
            'filters' => ['branchId' => $this->branch->id],
        ];
        $created = $this->postJson('/api/v1/report-exports', $payload, ['Idempotency-Key' => 'export-2']);
        $exportId = $created->json('data.id');

        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $response = $this->getJson("/api/v1/report-exports/{$exportId}/download");
        $response->assertStatus(403);
    }

    public function test_expired_export_cannot_be_downloaded(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $payload = [
            'reportCode' => 'inventory-on-hand',
            'format' => 'csv',
            'branchId' => $this->branch->id,
            'filters' => ['branchId' => $this->branch->id],
        ];
        $created = $this->postJson('/api/v1/report-exports', $payload, ['Idempotency-Key' => 'export-3']);
        $exportId = $created->json('data.id');

        ReportExport::query()->where('id', $exportId)->update(['expires_at' => now()->subDay()]);

        $response = $this->getJson("/api/v1/report-exports/{$exportId}/download");
        $response->assertStatus(410);
        $response->assertJsonPath('error.code', 'EXPORT_EXPIRED');
    }
}
