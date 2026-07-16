<?php

namespace Tests\Feature;

use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitOfMeasureAccessTest extends TestCase
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

    public function test_staff_can_read_but_not_manage_units(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actingAs($staff, 'web');

        $this->getJson('/api/v1/units-of-measure')->assertOk();

        $this->postJson('/api/v1/units-of-measure', [
            'code' => 'PC', 'name' => 'Piece', 'symbol' => 'pc', 'dimension' => 'count',
        ])->assertStatus(403);
    }

    public function test_manager_can_create_and_update_units(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actingAs($manager, 'web');

        $create = $this->postJson('/api/v1/units-of-measure', [
            'code' => 'PC', 'name' => 'Piece', 'symbol' => 'pc', 'dimension' => 'count',
        ]);
        $create->assertCreated();

        $unitId = $create->json('data.id');
        $update = $this->patchJson("/api/v1/units-of-measure/{$unitId}", [
            'symbol' => 'pcs',
            'version' => 1,
        ]);
        $update->assertOk();
        $update->assertJsonPath('data.symbol', 'pcs');
    }

    public function test_duplicate_unit_code_is_rejected(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $this->postJson('/api/v1/units-of-measure', [
            'code' => 'PC', 'name' => 'Piece', 'symbol' => 'pc', 'dimension' => 'count',
        ])->assertCreated();

        $response = $this->postJson('/api/v1/units-of-measure', [
            'code' => 'PC', 'name' => 'Piece Two', 'symbol' => 'pc2', 'dimension' => 'count',
        ]);

        $response->assertStatus(422);
    }
}
