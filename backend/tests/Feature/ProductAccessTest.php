<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\UnitOfMeasure;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function userWithRole(string $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role->id, ['effective_from' => now(), 'created_at' => now()]);

        return $user;
    }

    private function category(): Category
    {
        return Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);
    }

    private function unit(): UnitOfMeasure
    {
        return UnitOfMeasure::query()->create(['code' => 'EA', 'name' => 'Each', 'symbol' => 'ea', 'dimension' => 'count', 'is_active' => true, 'row_version' => 1]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }

    public function test_owner_can_create_read_update_and_archive_a_product(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');
        $category = $this->category();
        $unit = $this->unit();

        $create = $this->postJson('/api/v1/products', [
            'categoryId' => $category->id,
            'stockUnitId' => $unit->id,
            'sku' => 'SHX-FLT-010',
            'name' => '10 in. Sediment Filter Cartridge',
            'productType' => 'stock',
            'defaultTaxRate' => '12.0000',
        ]);
        $create->assertCreated();
        $create->assertJsonPath('data.stock', null);
        $productId = $create->json('data.id');

        $this->getJson("/api/v1/products/{$productId}")->assertOk();

        $update = $this->patchJson("/api/v1/products/{$productId}", [
            'name' => 'Updated name',
            'version' => 1,
        ]);
        $update->assertOk();
        $update->assertJsonPath('data.name', 'Updated name');

        $archive = $this->deleteJson("/api/v1/products/{$productId}?version=2");
        $archive->assertOk();

        $this->getJson('/api/v1/products')->assertJsonCount(0, 'data');
    }

    public function test_manager_can_create_products(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actingAs($manager, 'web');
        $category = $this->category();
        $unit = $this->unit();

        $this->postJson('/api/v1/products', [
            'categoryId' => $category->id,
            'stockUnitId' => $unit->id,
            'sku' => 'SHX-MEMB-01',
            'name' => 'Reverse Osmosis Membrane',
            'productType' => 'stock',
            'defaultTaxRate' => '12.0000',
        ])->assertCreated();
    }

    public function test_staff_can_read_but_not_create_products(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actingAs($staff, 'web');

        $this->getJson('/api/v1/products')->assertOk();

        $category = $this->category();
        $unit = $this->unit();

        $this->postJson('/api/v1/products', [
            'categoryId' => $category->id,
            'stockUnitId' => $unit->id,
            'sku' => 'SHX-FLT-010',
            'name' => 'Filter',
            'productType' => 'stock',
            'defaultTaxRate' => '12.0000',
        ])->assertStatus(403);
    }

    public function test_creating_a_duplicate_sku_returns_conflict(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');
        $category = $this->category();
        $unit = $this->unit();

        Product::query()->create([
            'category_id' => $category->id, 'stock_unit_id' => $unit->id, 'sku' => 'SHX-FLT-010',
            'name' => 'Filter', 'product_type' => 'stock', 'default_tax_rate' => '12.0000', 'row_version' => 1,
        ]);

        $response = $this->postJson('/api/v1/products', [
            'categoryId' => $category->id,
            'stockUnitId' => $unit->id,
            'sku' => 'SHX-FLT-010',
            'name' => 'Another product',
            'productType' => 'stock',
            'defaultTaxRate' => '12.0000',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'DUPLICATE_SKU');
    }

    public function test_creating_a_duplicate_barcode_returns_conflict(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');
        $category = $this->category();
        $unit = $this->unit();

        Product::query()->create([
            'category_id' => $category->id, 'stock_unit_id' => $unit->id, 'sku' => 'SHX-FLT-010',
            'barcode' => '4801234567891', 'name' => 'Filter', 'product_type' => 'stock',
            'default_tax_rate' => '12.0000', 'row_version' => 1,
        ]);

        $response = $this->postJson('/api/v1/products', [
            'categoryId' => $category->id,
            'stockUnitId' => $unit->id,
            'sku' => 'SHX-FLT-020',
            'barcode' => '4801234567891',
            'name' => 'Another product',
            'productType' => 'stock',
            'defaultTaxRate' => '12.0000',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'DUPLICATE_BARCODE');
    }

    public function test_stale_version_on_update_returns_conflict(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');
        $category = $this->category();
        $unit = $this->unit();

        $product = Product::query()->create([
            'category_id' => $category->id, 'stock_unit_id' => $unit->id, 'sku' => 'SHX-FLT-010',
            'name' => 'Filter', 'product_type' => 'stock', 'default_tax_rate' => '12.0000', 'row_version' => 1,
        ]);

        $response = $this->patchJson("/api/v1/products/{$product->id}", [
            'name' => 'Renamed',
            'version' => 999,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'VERSION_CONFLICT');
    }

    public function test_inactive_category_cannot_be_used_for_a_new_product(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');
        $unit = $this->unit();
        $inactiveCategory = Category::query()->create(['code' => 'SVC', 'name' => 'Services', 'is_active' => false, 'row_version' => 1]);

        $response = $this->postJson('/api/v1/products', [
            'categoryId' => $inactiveCategory->id,
            'stockUnitId' => $unit->id,
            'sku' => 'SHX-SVC-001',
            'name' => 'Installation Service',
            'productType' => 'service',
            'defaultTaxRate' => '12.0000',
        ]);

        $response->assertStatus(422);
    }
}
