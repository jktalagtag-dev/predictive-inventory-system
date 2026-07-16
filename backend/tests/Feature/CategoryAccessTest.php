<?php

namespace Tests\Feature;

use App\Domains\Catalog\Models\Category;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryAccessTest extends TestCase
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

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/categories')->assertStatus(401);
    }

    public function test_owner_can_create_read_update_and_archive_a_category(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $create = $this->postJson('/api/v1/categories', [
            'code' => 'FILT',
            'name' => 'Filter Cartridges',
            'isActive' => true,
        ]);
        $create->assertCreated();
        $categoryId = $create->json('data.id');

        $this->getJson("/api/v1/categories/{$categoryId}")->assertOk();

        $update = $this->patchJson("/api/v1/categories/{$categoryId}", [
            'name' => 'Filter Cartridges Updated',
            'version' => 1,
        ]);
        $update->assertOk();
        $update->assertJsonPath('data.name', 'Filter Cartridges Updated');

        $archive = $this->deleteJson("/api/v1/categories/{$categoryId}?version=2");
        $archive->assertOk();

        $this->getJson('/api/v1/categories')->assertJsonCount(0, 'data');
    }

    public function test_manager_can_create_categories(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actingAs($manager, 'web');

        $this->postJson('/api/v1/categories', [
            'code' => 'MEMB',
            'name' => 'Membranes',
        ])->assertCreated();
    }

    public function test_staff_can_read_but_not_create_categories(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actingAs($staff, 'web');

        $this->getJson('/api/v1/categories')->assertOk();

        $this->postJson('/api/v1/categories', [
            'code' => 'FILT',
            'name' => 'Filter Cartridges',
        ])->assertStatus(403);
    }

    public function test_staff_cannot_archive_categories(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');
        $category = Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);

        $staff = $this->userWithRole('staff');
        $this->actingAs($staff, 'web');

        $this->deleteJson("/api/v1/categories/{$category->id}?version=1")->assertStatus(403);
    }

    public function test_creating_a_duplicate_code_returns_conflict(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);

        $response = $this->postJson('/api/v1/categories', [
            'code' => 'FILT',
            'name' => 'Another Name',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'DUPLICATE_CATEGORY_CODE');
    }

    public function test_assigning_a_category_as_its_own_parent_is_rejected(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $category = Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);

        $response = $this->patchJson("/api/v1/categories/{$category->id}", [
            'parentCategoryId' => $category->id,
            'version' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_PARENT_CATEGORY');
    }

    public function test_stale_version_on_update_returns_conflict(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $category = Category::query()->create(['code' => 'FILT', 'name' => 'Filter Cartridges', 'row_version' => 1]);

        $response = $this->patchJson("/api/v1/categories/{$category->id}", [
            'name' => 'Renamed',
            'version' => 999,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'VERSION_CONFLICT');
    }
}
