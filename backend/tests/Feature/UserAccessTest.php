<?php

namespace Tests\Feature;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, BranchSeeder::class]);
    }

    private function userWithRole(string $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role->id, ['effective_from' => now(), 'created_at' => now()]);

        return $user;
    }

    public function test_owner_can_list_and_create_users(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $this->getJson('/api/v1/users')->assertOk();

        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $staffRole = Role::query()->where('code', 'staff')->firstOrFail();

        $response = $this->postJson('/api/v1/users', [
            'firstName' => 'Lea',
            'lastName' => 'Cruz',
            'email' => 'lea.cruz@example.test',
            'roleIds' => [$staffRole->id],
            'branchIds' => [$branch->id],
            'defaultBranchId' => $branch->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.email', 'lea.cruz@example.test');
        $response->assertJsonPath('data.version', 1);
    }

    public function test_manager_cannot_manage_users(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actingAs($manager, 'web');

        $this->getJson('/api/v1/users')->assertStatus(403);
    }

    public function test_staff_cannot_manage_users(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actingAs($staff, 'web');

        $this->getJson('/api/v1/users')->assertStatus(403);
    }

    public function test_last_active_owner_cannot_be_deactivated(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $response = $this->patchJson("/api/v1/users/{$owner->id}", [
            'isActive' => false,
            'version' => $owner->row_version,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'LAST_OWNER_PROTECTED');
    }

    public function test_deactivating_a_non_owner_user_succeeds(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $staff = $this->userWithRole('staff');

        $response = $this->patchJson("/api/v1/users/{$staff->id}", [
            'isActive' => false,
            'version' => $staff->row_version,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.isActive', false);
    }

    public function test_manager_cannot_update_an_owner_account_even_if_granted_users_update(): void
    {
        $manager = $this->userWithRole('manager');
        $managerRole = Role::query()->where('code', 'manager')->firstOrFail();
        $usersUpdatePermission = \App\Domains\Identity\Models\Permission::query()
            ->where('code', 'users.update')
            ->firstOrFail();
        $managerRole->permissions()->attach($usersUpdatePermission->id, ['created_at' => now()]);

        $this->actingAs($manager, 'web');

        $owner = $this->userWithRole('owner');

        $response = $this->patchJson("/api/v1/users/{$owner->id}", [
            'isActive' => false,
            'version' => $owner->row_version,
        ]);

        $response->assertStatus(403);
    }

    public function test_manager_cannot_create_a_branch(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actingAs($manager, 'web');

        $this->postJson('/api/v1/branches', [
            'code' => 'NORTH',
            'name' => 'North Branch',
            'countryCode' => 'PH',
        ])->assertStatus(403);
    }

    public function test_owner_can_create_a_branch(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $this->postJson('/api/v1/branches', [
            'code' => 'NORTH',
            'name' => 'North Branch',
            'countryCode' => 'PH',
        ])->assertCreated();
    }
}
