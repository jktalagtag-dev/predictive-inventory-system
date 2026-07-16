<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->unsignedSmallInteger('approval_stage');
            $table->string('decision', 16);
            $table->foreignId('decision_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('decision_at', 6);
            $table->string('reason', 1000)->nullable();
            $table->json('policy_snapshot');
            $table->timestamp('created_at', 6)->useCurrent();

            $table->unique(['purchase_order_id', 'approval_stage', 'decision_by_user_id'], 'uq_po_approval_stage_decision');
            $table->index(['purchase_order_id', 'approval_stage'], 'ix_po_approvals_order_stage');
            $table->index(['decision_by_user_id', 'decision_at'], 'ix_po_approvals_actor_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_approvals');
    }
};
