<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public const UNITS = [
        ['code' => 'EA', 'name' => 'Each', 'symbol' => 'ea', 'dimension' => 'count'],
        ['code' => 'BOX', 'name' => 'Box', 'symbol' => 'box', 'dimension' => 'count'],
        ['code' => 'BAG', 'name' => 'Bag', 'symbol' => 'bag', 'dimension' => 'count'],
        ['code' => 'L', 'name' => 'Liter', 'symbol' => 'L', 'dimension' => 'volume'],
        ['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'kg', 'dimension' => 'mass'],
    ];

    public function run(): void
    {
        foreach (self::UNITS as $unit) {
            UnitOfMeasure::query()->updateOrCreate(['code' => $unit['code']], [...$unit, 'is_active' => true, 'row_version' => 1]);
        }
    }
}
