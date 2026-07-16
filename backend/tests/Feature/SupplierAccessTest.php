<?php

namespace Tests\Feature;

use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\Supplier;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierAccessTest extends TestCase
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

    private function supplierPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'AQUAPURE',
            'legalName' => 'AquaPure Filtration Supplies Inc.',
            'countryCode' => 'PH',
            'defaultCurrencyCode' => 'PHP',
        ], $overrides);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/suppliers')->assertStatus(401);
    }

    public function test_owner_can_create_read_and_update_a_supplier(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $create = $this->postJson('/api/v1/suppliers', $this->supplierPayload());
        $create->assertCreated();
        $supplierId = $create->json('data.id');

        $this->getJson("/api/v1/suppliers/{$supplierId}")->assertOk();

        $update = $this->patchJson("/api/v1/suppliers/{$supplierId}", ['legalName' => 'AquaPure Holdings Inc.', 'version' => 1]);
        $update->assertOk();
        $update->assertJsonPath('data.legalName', 'AquaPure Holdings Inc.');
    }

    public function test_manager_can_create_suppliers(): void
    {
        $manager = $this->userWithRole('manager');
        $this->actingAs($manager, 'web');

        $this->postJson('/api/v1/suppliers', $this->supplierPayload())->assertCreated();
    }

    public function test_staff_can_read_but_not_create_suppliers(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actingAs($staff, 'web');

        $this->getJson('/api/v1/suppliers')->assertOk();
        $this->postJson('/api/v1/suppliers', $this->supplierPayload())->assertStatus(403);
    }

    public function test_duplicate_supplier_code_is_rejected(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        Supplier::query()->create([
            'code' => 'AQUAPURE', 'legal_name' => 'AquaPure', 'country_code' => 'PH',
            'default_currency_code' => 'PHP', 'row_version' => 1,
        ]);

        $response = $this->postJson('/api/v1/suppliers', $this->supplierPayload(['legalName' => 'Different Name']));

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'DUPLICATE_SUPPLIER_CODE');
    }

    public function test_supplier_contact_can_be_created_and_primary_is_exclusive(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner, 'web');

        $supplier = Supplier::query()->create([
            'code' => 'AQUAPURE', 'legal_name' => 'AquaPure', 'country_code' => 'PH',
            'default_currency_code' => 'PHP', 'row_version' => 1,
        ]);

        $first = $this->postJson("/api/v1/suppliers/{$supplier->id}/contacts", [
            'fullName' => 'Jose Rizal', 'email' => 'jose@aquapure.example', 'isPrimary' => true,
        ]);
        $first->assertCreated();
        $first->assertJsonPath('data.isPrimary', true);

        $second = $this->postJson("/api/v1/suppliers/{$supplier->id}/contacts", [
            'fullName' => 'Maria Clara', 'email' => 'maria@aquapure.example', 'isPrimary' => true,
        ]);
        $second->assertCreated();
        $second->assertJsonPath('data.isPrimary', true);

        $firstContactId = $first->json('data.id');
        $this->assertFalse(\App\Domains\Procurement\Models\SupplierContact::query()->find($firstContactId)->is_primary);
    }
}
