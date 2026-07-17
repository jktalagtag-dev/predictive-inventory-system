<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_run_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('forecast_run_id')->constrained('forecast_runs')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_sku_snapshot', 100);
            $table->string('product_name_snapshot', 255);
            $table->unsignedSmallInteger('history_period_count');
            $table->decimal('demand_total', 18, 4);
            $table->decimal('forecast_quantity', 18, 4)->nullable();
            $table->string('cold_start_status', 32);
            $table->decimal('manual_quantity', 18, 4)->nullable();
            $table->string('manual_reason', 500)->nullable();
            $table->timestamp('manual_expires_at', 6)->nullable();
            $table->json('input_snapshot');
            $table->timestamp('created_at', 6)->useCurrent();

            $table->unique(['forecast_run_id', 'product_id'], 'uq_forecast_run_items_product');
            $table->index(['product_id', 'created_at'], 'ix_forecast_items_product');
            $table->index('cold_start_status', 'ix_forecast_items_cold_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_run_items');
    }
};
