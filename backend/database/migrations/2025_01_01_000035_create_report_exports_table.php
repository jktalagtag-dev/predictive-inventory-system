<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->string('report_code', 120);
            $table->string('format', 8);
            $table->string('status', 24)->default('queued');
            $table->json('filters_snapshot');
            $table->timestamp('data_cutoff_at', 6)->nullable();
            $table->string('storage_key', 512)->nullable()->unique();
            $table->string('file_name')->nullable();
            $table->string('content_type', 120)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->timestamp('expires_at', 6)->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->timestamp('requested_at', 6)->useCurrent();
            $table->timestamp('completed_at', 6)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['report_code', 'requested_at'], 'ix_report_exports_report_date');
            $table->index(['requested_by_user_id', 'status', 'requested_at'], 'ix_report_exports_requester_status');
            $table->index('expires_at', 'ix_report_exports_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
