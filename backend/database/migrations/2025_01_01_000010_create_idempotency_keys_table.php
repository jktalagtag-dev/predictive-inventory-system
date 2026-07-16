<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('operation_scope', 80);
            $table->string('idempotency_key', 128);
            $table->char('request_hash', 64);
            $table->string('status', 24)->default('processing');
            $table->unsignedSmallInteger('response_status_code')->nullable();
            $table->json('response_body')->nullable();
            $table->string('resource_type', 64)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->char('correlation_id', 36);
            $table->timestamp('expires_at', 6);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['actor_user_id', 'operation_scope', 'idempotency_key'], 'uq_idempotency_actor_scope_key');
            $table->index('expires_at', 'ix_idempotency_expiry');
            $table->index('correlation_id', 'ix_idempotency_correlation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
