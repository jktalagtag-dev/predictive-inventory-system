<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restocking_alert_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restocking_alert_id')->constrained('restocking_alerts')->restrictOnDelete();
            $table->string('event_type', 32);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->json('details')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at', 6);

            $table->index(['restocking_alert_id', 'occurred_at'], 'ix_alert_events_alert_date');
            $table->index(['event_type', 'occurred_at'], 'ix_alert_events_type_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restocking_alert_events');
    }
};
