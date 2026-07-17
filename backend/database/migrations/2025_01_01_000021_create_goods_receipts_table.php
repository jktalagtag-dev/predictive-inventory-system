<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('receipt_number', 64);
            $table->string('status', 24)->default('draft');
            $table->string('supplier_delivery_number', 120)->nullable();
            $table->timestamp('received_at', 6);
            $table->timestamp('posted_at', 6)->nullable();
            $table->timestamp('reversed_at', 6)->nullable();
            $table->foreignId('reversal_receipt_id')->nullable()->constrained('goods_receipts')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique('receipt_number', 'uq_goods_receipts_number');
            $table->index(['purchase_order_id', 'status'], 'ix_goods_receipts_po_status');
            $table->index(['branch_id', 'received_at'], 'ix_goods_receipts_branch_date');
            $table->index('supplier_delivery_number', 'ix_goods_receipts_delivery_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
