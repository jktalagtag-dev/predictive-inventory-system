<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamp('effective_from', 6);
            $table->timestamp('effective_until', 6)->nullable();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->primary(['user_id', 'role_id'], 'pk_user_roles');
            $table->index(['user_id', 'effective_from', 'effective_until'], 'ix_user_roles_active');
            $table->index('role_id', 'ix_user_roles_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
