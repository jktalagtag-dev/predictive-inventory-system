<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('full_name', 160);
            $table->string('job_title', 120)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 48)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at', 6)->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->index(['supplier_id', 'is_active'], 'ix_supplier_contacts_supplier_active');
        });

        // "Only one active primary contact per supplier" is enforced in
        // SupplierService rather than a DB-level partial unique index, for
        // the same portability reason documented on user_branches.is_default.
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');
    }
};
