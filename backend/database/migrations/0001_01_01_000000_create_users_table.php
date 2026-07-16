<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 254);
            $table->string('password_hash', 255);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('display_name', 201);
            $table->string('phone', 48)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at', 6)->nullable();
            $table->timestamp('last_login_at', 6)->nullable();
            $table->timestamp('password_changed_at', 6)->nullable();
            $table->timestamp('mfa_enabled_at', 6)->nullable();
            $table->rememberToken();
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('updated_at', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->unique('email', 'uq_users_email');
            $table->index(['is_active', 'email'], 'ix_users_active_email');
            $table->index('last_login_at', 'ix_users_last_login_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email', 254)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at', 6)->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
