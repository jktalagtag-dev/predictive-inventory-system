<?php

namespace Database\Seeders;

use App\Domains\Identity\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'country_code' => 'PH',
                'is_active' => true,
            ],
        );
    }
}
