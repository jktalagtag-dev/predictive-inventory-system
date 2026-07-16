<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branches', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->boolean('is_default')->default(false);
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->primary(['user_id', 'branch_id'], 'pk_user_branches');
            $table->index('branch_id', 'ix_user_branches_branch');
        });

        // A single default branch per user is enforced in UserBranchAssignmentService
        // rather than a DB-level partial unique index, since MySQL/SQLite unique
        // indexes cannot express "unique only where is_default = true" portably.
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branches');
    }
};
