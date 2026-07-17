<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deviation from DATABASE_DESIGN.md: CLAUDE.md section 52 requires
     * "supplier-product lead time as the primary value and product default
     * only when supplier data is unavailable," but the supplier_products
     * table (DATABASE_DESIGN.md section 5.7) has not been built yet — the
     * same gap already documented for purchase_order_lines/sale_lines unit
     * conversion. Until that table exists, a product-level default is the
     * only available lead-time source besides an explicit reorder-policy
     * override.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('default_lead_time_days', 10, 2)->nullable()->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('default_lead_time_days');
        });
    }
};
