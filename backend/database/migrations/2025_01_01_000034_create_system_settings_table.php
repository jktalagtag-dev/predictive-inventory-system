<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->string('setting_key', 160);
            $table->string('value_type', 16);
            $table->json('value_json');
            $table->boolean('is_sensitive')->default(false);
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // branch_id is nullable, and MySQL unique indexes treat each NULL
            // as distinct, so a plain unique(branch_id, setting_key) already
            // enforces "one row per (branch, key)" including the global
            // (branch_id IS NULL) scope without a separate sentinel column.
            $table->unique(['branch_id', 'setting_key'], 'uq_system_settings_scope_key');
            $table->index('setting_key', 'ix_system_settings_key');
            $table->index(['branch_id', 'setting_key'], 'ix_system_settings_branch_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
