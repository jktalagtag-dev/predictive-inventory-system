<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eoq_calculations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reorder_policy_id')->constrained('reorder_policies')->restrictOnDelete();
            $table->decimal('annual_demand_quantity', 18, 4);
            $table->decimal('ordering_cost', 19, 4);
            $table->decimal('annual_holding_cost_per_unit', 19, 4);
            $table->decimal('raw_eoq_quantity', 18, 4)->nullable();
            $table->decimal('recommended_order_quantity', 18, 4)->nullable();
            $table->char('currency_code', 3);
            $table->string('formula_version', 32);
            $table->json('input_snapshot');
            $table->string('status', 24);
            $table->string('invalid_reason', 500)->nullable();
            $table->timestamp('calculated_at', 6);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['reorder_policy_id', 'calculated_at'], 'ix_eoq_policy_date');
            $table->index(['status', 'calculated_at'], 'ix_eoq_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eoq_calculations');
    }
};
