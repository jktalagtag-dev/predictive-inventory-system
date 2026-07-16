<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('on_hand_quantity', 18, 4)->default(0);
            $table->decimal('reserved_quantity', 18, 4)->default(0);
            $table->decimal('available_quantity', 18, 4)->default(0);
            $table->decimal('incoming_quantity', 18, 4)->default(0);
            $table->timestamp('last_movement_at', 6)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['branch_id', 'product_id'], 'uq_inventory_balances_branch_product');
            $table->index(['product_id', 'branch_id'], 'ix_inventory_balances_product_branch');
            $table->index(['branch_id', 'available_quantity'], 'ix_inventory_balances_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
