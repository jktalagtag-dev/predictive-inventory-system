<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_operations', function (Blueprint $table): void {
            $table->id();
            $table->char('client_operation_id', 36);
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('operation_type', 80);
            $table->unsignedSmallInteger('payload_version');
            $table->char('payload_hash', 64);
            $table->foreignId('idempotency_key_id')->nullable()->constrained('idempotency_keys')->restrictOnDelete();
            $table->string('status', 24)->default('received');
            $table->char('dependency_operation_id', 36)->nullable();
            $table->string('server_resource_type', 64)->nullable();
            $table->unsignedBigInteger('server_resource_id')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->json('conflict_payload')->nullable();
            $table->timestamp('received_at', 6);
            $table->timestamp('resolved_at', 6)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique('client_operation_id', 'uq_sync_client_operation_id');
            $table->index('operation_type', 'ix_sync_operation_type');
            $table->index(['actor_user_id', 'status', 'received_at'], 'ix_sync_actor_status_date');
            $table->index(['branch_id', 'status'], 'ix_sync_branch_status');
            $table->index('dependency_operation_id', 'ix_sync_dependency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
    }
};
