<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120);
            $table->string('name', 160);
            $table->string('module', 80);
            $table->string('description', 500)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->unique('code', 'uq_permissions_code');
            $table->index('module', 'ix_permissions_module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
