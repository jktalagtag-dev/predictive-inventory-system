<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role_snapshot', 120)->nullable();
            $table->string('action', 120);
            $table->string('entity_type', 80);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->char('correlation_id', 36);
            $table->string('ip_address', 45)->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->json('changes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['entity_type', 'entity_id'], 'ix_audit_logs_entity');
            $table->index(['actor_user_id', 'created_at'], 'ix_audit_logs_actor_date');
            $table->index(['branch_id', 'created_at'], 'ix_audit_logs_branch_date');
            $table->index('correlation_id', 'ix_audit_logs_correlation');
            $table->index(['action', 'created_at'], 'ix_audit_logs_action_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
