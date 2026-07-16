<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64);
            $table->string('legal_name', 255);
            $table->string('tax_identifier', 80)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 48)->nullable();
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->string('postal_code', 24)->nullable();
            $table->char('country_code', 2);
            $table->char('default_currency_code', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at', 6)->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->unique('code', 'uq_suppliers_code');
            $table->unique('tax_identifier', 'uq_suppliers_tax_identifier');
            $table->index(['is_active', 'legal_name'], 'ix_suppliers_active_name');
            $table->index('deleted_at', 'ix_suppliers_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
