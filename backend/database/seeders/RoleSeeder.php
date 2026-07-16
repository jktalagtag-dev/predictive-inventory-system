<?php

namespace Database\Seeders;

use App\Domains\Identity\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public const ROLES = [
        ['code' => 'owner', 'name' => 'Owner', 'description' => 'Strategic visibility and full governance controls.', 'is_system_role' => true],
        ['code' => 'manager', 'name' => 'Manager', 'description' => 'Operational oversight, approvals, and procurement control.', 'is_system_role' => true],
        ['code' => 'staff', 'name' => 'Staff', 'description' => 'Least-privilege access to assigned operational workflows.', 'is_system_role' => true],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::query()->updateOrCreate(['code' => $role['code']], $role);
        }
    }
}
