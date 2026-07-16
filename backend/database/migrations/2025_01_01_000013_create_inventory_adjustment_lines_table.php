<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_adjustment_id')->constrained('inventory_adjustments')->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_sku_snapshot', 100);
            $table->string('product_name_snapshot', 255);
            $table->decimal('before_quantity', 18, 4);
            $table->decimal('quantity_delta', 18, 4);
            $table->decimal('after_quantity', 18, 4);
            $table->decimal('unit_cost', 19, 4)->nullable();
            $table->decimal('cost_impact_amount', 19, 4)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['inventory_adjustment_id', 'line_number'], 'uq_inventory_adjustment_lines_number');
            $table->unique(['inventory_adjustment_id', 'product_id'], 'uq_inventory_adjustment_lines_product');
            $table->index('product_id', 'ix_adjustment_lines_product');
            $table->index('inventory_adjustment_id', 'ix_adjustment_lines_adjustment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_lines');
    }
};
