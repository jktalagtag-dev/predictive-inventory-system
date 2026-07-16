<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('stock_unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('sku', 100);
            $table->string('barcode', 100)->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('product_type', 32);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->boolean('is_expiry_tracked')->default(false);
            $table->decimal('default_tax_rate', 9, 4);
            $table->timestamp('deleted_at', 6)->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->unique('sku', 'uq_products_sku');
            $table->unique('barcode', 'uq_products_barcode');
            $table->index(['category_id', 'is_active'], 'ix_products_category_active');
            $table->index('name', 'ix_products_name');
            $table->index('deleted_at', 'ix_products_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
