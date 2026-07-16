<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('po_number', 64);
            $table->string('status', 32)->default('draft');
            $table->char('currency_code', 3);
            $table->timestamp('ordered_at', 6)->nullable();
            $table->timestamp('expected_receipt_at', 6)->nullable();
            $table->timestamp('submitted_at', 6)->nullable();
            $table->timestamp('approved_at', 6)->nullable();
            $table->timestamp('cancelled_at', 6)->nullable();
            $table->decimal('subtotal_amount', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('total_amount', 19, 4)->default(0);
            $table->string('supplier_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique('po_number', 'uq_purchase_orders_number');
            $table->index(['branch_id', 'status', 'expected_receipt_at'], 'ix_purchase_orders_branch_status_date');
            $table->index(['supplier_id', 'status'], 'ix_purchase_orders_supplier_status');
            $table->index('created_at', 'ix_purchase_orders_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
