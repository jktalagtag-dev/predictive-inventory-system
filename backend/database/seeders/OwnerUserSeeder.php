<?php

namespace Database\Seeders;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\Role;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the first Owner account for local development and CI only.
 * Production environments must provision the initial Owner through an
 * approved out-of-band process, never this seeder.
 */
class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $email = env('OWNER_SEED_EMAIL', 'owner@stevenhydrotech.example');
        $password = env('OWNER_SEED_PASSWORD', 'ChangeMe!12345');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'password_hash' => Hash::make($password),
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'display_name' => 'Maria Santos',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $ownerRole = Role::query()->where('code', 'owner')->firstOrFail();
        $user->roles()->syncWithoutDetaching([
            $ownerRole->id => ['effective_from' => now(), 'created_at' => now()],
        ]);

        $mainBranch = Branch::query()->where('code', 'MAIN')->first();
        if ($mainBranch) {
            $user->branches()->syncWithoutDetaching([
                $mainBranch->id => ['is_default' => true, 'created_at' => now()],
            ]);
        }
    }
}
