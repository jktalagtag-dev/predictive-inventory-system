<?php

namespace Tests\Feature;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
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

    /** @see InventoryAdjustmentTest::actAs() for why forgetGuards() is required. */
    private function actAs(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/settings')->assertStatus(401);
    }

    public function test_list_returns_registry_defaults_when_unoverridden(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->getJson('/api/v1/settings');
        $response->assertOk();

        $taxSetting = collect($response->json('data'))->firstWhere('key', 'pos.default_tax_rate');
        $this->assertNotNull($taxSetting);
        $this->assertFalse($taxSetting['isOverridden']);
        $this->assertSame('0.1200', $taxSetting['value']);
        $this->assertSame(0, $taxSetting['version']);
    }

    public function test_staff_cannot_read_settings(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actAs($staff);

        $this->getJson('/api/v1/settings')->assertStatus(403);
    }

    public function test_manager_cannot_change_owner_only_setting(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actAs($manager);

        $response = $this->putJson('/api/v1/settings/security.session_lifetime_minutes', [
            'valueType' => 'integer', 'value' => 60, 'version' => 0,
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_change_owner_only_setting_and_manager_can_read_result(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $update = $this->putJson('/api/v1/settings/security.session_lifetime_minutes', [
            'valueType' => 'integer', 'value' => 60, 'version' => 0,
        ]);
        $update->assertOk();
        $update->assertJsonPath('data.value', 60);
        $update->assertJsonPath('data.version', 1);

        $manager = $this->userWithRole('manager');
        $this->actAs($manager);
        $response = $this->getJson('/api/v1/settings/security.session_lifetime_minutes');
        $response->assertOk();
        $response->assertJsonPath('data.value', 60);
    }

    public function test_stale_version_returns_conflict(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $this->putJson('/api/v1/settings/pos.default_tax_rate', [
            'valueType' => 'decimal', 'value' => '0.1000', 'version' => 0,
        ])->assertOk();

        $response = $this->putJson('/api/v1/settings/pos.default_tax_rate', [
            'valueType' => 'decimal', 'value' => '0.0800', 'version' => 0,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'VERSION_CONFLICT');
    }

    public function test_unknown_setting_key_returns_404(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $this->getJson('/api/v1/settings/not.a.real.setting')->assertStatus(404);
    }

    public function test_value_type_mismatch_is_rejected(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);

        $response = $this->putJson('/api/v1/settings/pos.default_tax_rate', [
            'valueType' => 'integer', 'value' => 5, 'version' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_SETTING_VALUE');
    }

    public function test_sensitive_setting_is_redacted_for_managers_without_sensitive_permission(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actAs($owner);
        $this->putJson('/api/v1/settings/security.mfa_required_for_owner', [
            'valueType' => 'boolean', 'value' => true, 'version' => 0,
        ])->assertOk();

        $manager = $this->userWithRole('manager');
        $this->actAs($manager);
        $response = $this->getJson('/api/v1/settings/security.mfa_required_for_owner');
        $response->assertOk();
        $response->assertJsonPath('data.isRedacted', true);
        $this->assertNull($response->json('data.value'));

        $this->actAs($owner);
        $ownerView = $this->getJson('/api/v1/settings/security.mfa_required_for_owner');
        $ownerView->assertJsonPath('data.isRedacted', false);
        $ownerView->assertJsonPath('data.value', true);
    }
}
