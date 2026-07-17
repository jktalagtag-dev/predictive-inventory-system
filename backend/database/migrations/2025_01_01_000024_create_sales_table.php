<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('sale_number', 64);
            $table->string('status', 24)->default('completed');
            $table->char('currency_code', 3);
            $table->timestamp('sold_at', 6);
            $table->timestamp('completed_at', 6);
            $table->timestamp('voided_at', 6)->nullable();
            $table->timestamp('refunded_at', 6)->nullable();
            $table->foreignId('reverses_sale_id')->nullable()->constrained('sales')->restrictOnDelete();
            $table->decimal('subtotal_amount', 19, 4);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('total_amount', 19, 4);
            $table->foreignId('cashier_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('idempotency_key', 128);
            $table->char('correlation_id', 36);
            $table->string('notes', 1000)->nullable();
            // Deviation from DATABASE_DESIGN.md section 8.1, which does not
            // list a concurrency column: REST_API_SPECIFICATION.md section
            // 11.4 requires a `version` field on void, matching the
            // optimistic-concurrency pattern already used for goods_receipts
            // and purchase_orders (CLAUDE.md section 14).
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();

            $table->unique('sale_number', 'uq_sales_number');
            $table->unique('idempotency_key', 'uq_sales_idempotency_key');
            $table->index(['branch_id', 'sold_at'], 'ix_sales_branch_sold_at');
            $table->index(['cashier_user_id', 'sold_at'], 'ix_sales_cashier_date');
            $table->index(['status', 'sold_at'], 'ix_sales_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
