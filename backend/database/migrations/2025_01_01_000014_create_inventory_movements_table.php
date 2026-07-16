<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('movement_type', 32);
            $table->decimal('quantity_delta', 18, 4);
            $table->decimal('on_hand_after_quantity', 18, 4)->nullable();
            $table->decimal('unit_cost', 19, 4)->nullable();
            $table->string('reference_type', 64);
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('reverses_movement_id')->nullable()->constrained('inventory_movements')->restrictOnDelete();
            $table->timestamp('effective_at', 6);
            $table->timestamp('posted_at', 6)->useCurrent();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('correlation_id', 36);
            $table->string('idempotency_key', 128)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['branch_id', 'product_id', 'effective_at', 'id'], 'ix_movements_branch_product_effective');
            $table->index(['product_id', 'effective_at', 'id'], 'ix_movements_product_effective');
            $table->index(['reference_type', 'reference_id'], 'ix_movements_reference');
            $table->index('correlation_id', 'ix_movements_correlation');
            $table->index(['actor_user_id', 'effective_at'], 'ix_movements_actor_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
