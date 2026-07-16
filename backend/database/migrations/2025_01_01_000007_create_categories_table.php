<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('code', 64);
            $table->string('name', 160);
            $table->string('description', 1000)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at', 6)->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->unique('code', 'uq_categories_code');
            $table->unique(['parent_category_id', 'name'], 'uq_categories_parent_name');
            $table->index(['parent_category_id', 'is_active'], 'ix_categories_parent_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
