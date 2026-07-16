<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24);
            $table->string('name', 80);
            $table->string('symbol', 16);
            $table->string('dimension', 32);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at', 6)->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->unique('code', 'uq_units_code');
            $table->unique('name', 'uq_units_name');
            $table->index(['dimension', 'is_active'], 'ix_units_dimension_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
    }
};
