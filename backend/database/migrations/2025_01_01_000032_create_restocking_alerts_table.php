<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restocking_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reorder_policy_id')->constrained('reorder_policies')->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->string('severity', 16);
            $table->decimal('available_quantity_snapshot', 18, 4);
            $table->decimal('incoming_quantity_snapshot', 18, 4);
            $table->decimal('reorder_point_snapshot', 18, 4);
            $table->decimal('recommended_order_quantity', 18, 4)->nullable();
            $table->timestamp('first_triggered_at', 6);
            $table->timestamp('last_evaluated_at', 6);
            $table->timestamp('resolved_at', 6)->nullable();
            $table->string('dismissal_reason', 500)->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['status', 'severity', 'last_evaluated_at'], 'ix_restocking_alerts_status_severity');
            $table->index(['assigned_to_user_id', 'status'], 'ix_restocking_alerts_assignee_status');
            $table->index('reorder_policy_id', 'ix_restocking_alerts_policy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restocking_alerts');
    }
};
