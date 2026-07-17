<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            // Deviation from DATABASE_DESIGN.md, matching purchase_order_lines:
            // references the product's canonical stock unit directly since the
            // dedicated product_units table has not been built yet.
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('product_sku_snapshot', 100);
            $table->string('product_name_snapshot', 255);
            $table->decimal('received_quantity', 18, 4);
            $table->decimal('accepted_quantity', 18, 4);
            $table->decimal('rejected_quantity', 18, 4);
            $table->decimal('unit_cost', 19, 4);
            $table->string('lot_number', 120)->nullable();
            $table->string('serial_number', 120)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['goods_receipt_id', 'line_number'], 'uq_goods_receipt_lines_number');
            $table->index('purchase_order_line_id', 'ix_gr_lines_po_line');
            $table->index('product_id', 'ix_gr_lines_product');
            $table->index(['product_id', 'lot_number'], 'ix_gr_lines_lot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
