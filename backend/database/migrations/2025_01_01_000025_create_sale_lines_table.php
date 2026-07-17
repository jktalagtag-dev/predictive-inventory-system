<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            // Deviation from DATABASE_DESIGN.md, matching purchase_order_lines
            // and goods_receipt_lines: references the product's canonical
            // stock unit directly since the dedicated product_units table
            // (approved sales unit conversions) has not been built yet.
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('product_sku_snapshot', 100);
            $table->string('product_name_snapshot', 255);
            $table->decimal('quantity', 18, 4);
            $table->decimal('stock_quantity_delta', 18, 4);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('tax_rate', 9, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('line_total_amount', 19, 4);
            $table->string('override_reason', 500)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->unique(['sale_id', 'line_number'], 'uq_sale_lines_number');
            $table->index(['product_id', 'sale_id'], 'ix_sale_lines_product_date');
            $table->index('sale_id', 'ix_sale_lines_sale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
    }
};
