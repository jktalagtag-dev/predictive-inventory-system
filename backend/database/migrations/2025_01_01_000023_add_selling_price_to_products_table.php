<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deviation from DATABASE_DESIGN.md: the product master (section 5.3)
     * does not define a selling price column, and no dedicated pricing
     * table exists yet. POS finalization (REST_API_SPECIFICATION.md
     * section 11.2) requires a server-authoritative "authorized current
     * price" to recalculate against, so a single active selling price is
     * added directly to products as the simplest correct source of truth
     * until an approved price-list domain is introduced.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('selling_price', 19, 4)->default(0)->after('default_tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('selling_price');
        });
    }
};
