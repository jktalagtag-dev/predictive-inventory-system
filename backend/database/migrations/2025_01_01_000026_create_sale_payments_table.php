<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->string('payment_method', 32);
            $table->decimal('amount', 19, 4);
            $table->char('currency_code', 3);
            $table->string('external_reference', 160)->nullable();
            $table->timestamp('received_at', 6);
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index('sale_id', 'ix_sale_payments_sale');
            $table->index('external_reference', 'ix_sale_payments_reference');
            $table->index(['payment_method', 'received_at'], 'ix_sale_payments_method_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
