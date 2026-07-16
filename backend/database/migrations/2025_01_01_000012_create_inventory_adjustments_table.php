<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('adjustment_number', 64);
            $table->string('status', 24)->default('draft');
            $table->string('reason_code', 64);
            $table->string('reason_note', 1000)->nullable();
            $table->timestamp('effective_at', 6);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at', 6)->nullable();
            $table->timestamp('posted_at', 6)->nullable();
            $table->foreignId('reversal_adjustment_id')->nullable()->constrained('inventory_adjustments')->restrictOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique('adjustment_number', 'uq_inventory_adjustments_number');
            $table->index(['branch_id', 'status', 'effective_at'], 'ix_inventory_adjustments_branch_status_date');
            $table->index(['reason_code', 'effective_at'], 'ix_inventory_adjustments_reason_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
