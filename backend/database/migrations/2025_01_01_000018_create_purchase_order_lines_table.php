<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            // Deviation from DATABASE_DESIGN.md: references the product's
            // canonical stock unit directly. The dedicated `product_units`
            // table (approved purchase/sales unit conversions) has not been
            // built yet, so per-line unit conversion is not available.
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('product_sku_snapshot', 100);
            $table->string('product_name_snapshot', 255);
            $table->decimal('ordered_quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('tax_rate', 9, 4)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('net_amount', 19, 4);
            $table->decimal('tax_amount', 19, 4);
            $table->decimal('total_amount', 19, 4);
            $table->timestamp('expected_receipt_at', 6)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['purchase_order_id', 'line_number'], 'uq_purchase_order_lines_number');
            $table->unique(['purchase_order_id', 'product_id'], 'uq_purchase_order_lines_product');
            $table->index('product_id', 'ix_po_lines_product');
            $table->index(['purchase_order_id', 'ordered_quantity', 'received_quantity'], 'ix_po_lines_open');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
