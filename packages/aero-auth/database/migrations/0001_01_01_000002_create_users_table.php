<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical users table migration.
 *
 * Consolidates:
 *  - 0001_01_01_000002_create_users_table.php                    (base)
 *  - 2024_01_16_000002_add_two_factor_columns_to_users_table.php (absorbed – cols already present)
 *  - 2025_12_03_150826_add_phone_verification_to_users_table.php (absorbed)
 *
 * Also creates: password_reset_tokens, sessions,
 *               user_sessions_tracking, authentication_events
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── users ──────────────────────────────────────────────────────────────
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('user_name');
                $table->string('phone')->unique()->nullable();
                $table->string('email')->unique();
                $table->string('password');
                $table->boolean('force_password_reset')->default(false);

                // ── Two-Factor Authentication ──────────────────────────────────
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();

                // ── OAuth / Social Login ───────────────────────────────────────
                $table->string('oauth_provider')->nullable();
                $table->string('oauth_provider_id')->nullable();
                $table->string('oauth_token')->nullable();
                $table->string('oauth_refresh_token')->nullable();
                $table->timestamp('oauth_token_expires_at')->nullable();
                $table->string('avatar_url')->nullable();

                $table->string('name');

                // ── Account Status ─────────────────────────────────────────────
                $table->boolean('active')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamp('account_locked_at')->nullable();
                $table->string('locked_reason')->nullable();

                $table->softDeletes();

                // ── Basic Profile Info ─────────────────────────────────────────
                $table->date('date_of_joining')->nullable();
                $table->date('birthday')->nullable();
                $table->string('gender')->nullable();
                $table->string('passport_no')->nullable();
                $table->date('passport_exp_date')->nullable();
                $table->string('nationality')->nullable();
                $table->string('religion')->nullable();
                $table->string('marital_status')->nullable();
                $table->string('employment_of_spouse')->nullable();
                $table->integer('number_of_children')->nullable();

                // ── Salary & Benefits (backward-compat) ────────────────────────
                $table->string('salary_basis')->nullable();
                $table->decimal('salary_amount', 10, 2)->nullable();
                $table->string('payment_type')->nullable();
                $table->boolean('pf_contribution')->nullable();
                $table->string('pf_no')->nullable();
                $table->string('employee_pf_rate')->nullable();
                $table->string('additional_pf_rate')->nullable();
                $table->string('total_pf_rate')->nullable();
                $table->boolean('esi_contribution')->nullable();
                $table->string('esi_no')->nullable();
                $table->string('employee_esi_rate')->nullable();
                $table->string('additional_esi_rate')->nullable();
                $table->string('total_esi_rate')->nullable();

                // ── Authentication Timestamps ──────────────────────────────────
                $table->timestamp('email_verified_at')->nullable();

                // Absorbed from: add_phone_verification_to_users_table
                $table->timestamp('phone_verified_at')->nullable();
                $table->string('phone_verification_code', 6)->nullable();
                $table->timestamp('phone_verification_sent_at')->nullable();

                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip', 45)->nullable();
                $table->integer('login_count')->default(0);

                // ── User Preferences & Extended Profile ──────────────────────────────
                $table->json('notification_preferences')->nullable();
                $table->boolean('security_notifications')->default(true);

                // ── Extended profile (used by User model) ─────────────────────
                $table->string('profile_image')->nullable();
                $table->text('about')->nullable();
                $table->string('locale', 10)->nullable();
                $table->string('timezone', 50)->nullable();

                // ── Device management ──────────────────────────────────────────
                $table->boolean('single_device_login_enabled')->default(false);
                $table->timestamp('device_reset_at')->nullable();
                $table->string('device_reset_reason')->nullable();

                // ── Push notifications ─────────────────────────────────────────
                $table->string('fcm_token')->nullable();

                // ── Preferences (JSON bag) ─────────────────────────────────────
                $table->json('preferences')->nullable();

                $table->rememberToken();
                $table->timestamps();

                $table->index(['oauth_provider', 'oauth_provider_id']);
            });
        }

        // ── password_reset_tokens ──────────────────────────────────────────────
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // ── sessions ───────────────────────────────────────────────────────────
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // ── user_sessions_tracking ─────────────────────────────────────────────
        if (! Schema::hasTable('user_sessions_tracking')) {
            Schema::create('user_sessions_tracking', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('session_id', 100)->index();
                $table->string('ip_address', 45);
                $table->text('user_agent');
                $table->string('device_type', 50)->nullable();
                $table->string('device_name', 100)->nullable();
                $table->string('browser', 50)->nullable();
                $table->string('platform', 50)->nullable();
                $table->string('location', 255)->nullable();
                $table->json('device_fingerprint')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_activity')->useCurrent();
                $table->timestamp('login_at')->useCurrent();
                $table->timestamp('logout_at')->nullable();
                $table->enum('logout_type', ['manual', 'timeout', 'forced', 'admin'])->nullable();
                $table->json('security_flags')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index(['user_id', 'last_activity']);
            });
        }

        // ── authentication_events ──────────────────────────────────────────────
        if (! Schema::hasTable('authentication_events')) {
            Schema::create('authentication_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('event_type', 50);
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->string('status', 20);
                $table->string('risk_level', 20)->default('low');
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['user_id', 'event_type']);
                $table->index(['ip_address', 'occurred_at']);
                $table->index(['event_type', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication_events');
        Schema::dropIfExists('user_sessions_tracking');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
