<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64);
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->unique('code', 'uq_roles_code');
            $table->unique('name', 'uq_roles_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
