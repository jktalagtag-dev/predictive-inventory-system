<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->string('model_code', 32)->default('sma');
            $table->string('model_version', 32);
            $table->string('period_grain', 16);
            $table->unsignedSmallInteger('window_periods');
            $table->date('history_start_date');
            $table->date('history_end_date');
            $table->timestamp('data_cutoff_at', 6);
            $table->string('status', 24)->default('queued');
            $table->timestamp('started_at', 6)->nullable();
            $table->timestamp('completed_at', 6)->nullable();
            $table->json('parameters_snapshot');
            $table->string('failure_code', 80)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['branch_id', 'status', 'created_at'], 'ix_forecast_runs_branch_status_date');
            $table->index('data_cutoff_at', 'ix_forecast_runs_cutoff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_runs');
    }
};
