<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            // Deviation from DATABASE_DESIGN.md: references suppliers.id
            // directly since the supplier_products table has not been
            // built yet (same gap documented on the lead-time migration).
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->decimal('safety_stock_quantity', 18, 4)->default(0);
            $table->string('safety_stock_basis', 32)->default('policy_minimum');
            $table->decimal('lead_time_days_override', 10, 2)->nullable();
            $table->string('lead_time_basis', 32)->default('product_default');
            $table->decimal('reorder_point_quantity', 18, 4)->nullable();
            $table->timestamp('rop_calculated_at', 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['branch_id', 'product_id'], 'uq_reorder_policies_branch_product');
            $table->index(['preferred_supplier_id', 'is_active'], 'ix_reorder_policies_supplier_active');
            $table->index(['branch_id', 'is_active', 'reorder_point_quantity'], 'ix_reorder_policies_active_rop');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_policies');
    }
};
