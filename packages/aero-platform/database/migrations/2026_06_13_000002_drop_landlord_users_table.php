<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auth-identity unification — Unit 2D, step 2 (data-move + drop landlord_users).
 *
 * Mechanism B (Unit 2C) moved landlords into the central `users` table and
 * Unit 2D step 1 (2026_06_13_000001) repointed the 16 surviving FK constraints
 * from `landlord_users` to `users`. This migration finishes the job: it moves
 * any residual `landlord_users` rows into `users` (no-op in every real DB — the
 * SaaS admin is written straight to `users` by AdminUserStep, and the standalone
 * admin lives in `users` too, so the table is empty everywhere) and then drops
 * `landlord_users`.
 *
 * KEEP `landlord_password_reset_tokens`: the landlord password broker is still
 * wired as `auth.passwords.landlord_users` (LandlordAuthContext::passwordBroker())
 * and uses that token table. It is a separate table created in the same
 * create-migration, so dropping `landlord_users` leaves it intact.
 *
 * Forward/corrective migration (NOT an edit of the create-migrations): the
 * standalone host already ran the create-migrations, so this runs last in the
 * chain — on a fresh install `landlord_users` is created, its FKs are attached,
 * repointed by 000001, then dropped here. By this point all real FKs reference
 * `users` and the landlord_roles FK tables are already dropped (2026_06_04),
 * so the drop is FK-safe.
 *
 * Reversibility: the forward data-move is a no-op (nothing to restore), so down()
 * only recreates an EMPTY bigint `landlord_users` table (mirroring the
 * 2025_12_01 update-migration structure) to keep the migrate:rollback chain
 * consistent. It does not attempt to move rows back.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('central');

        if (! $schema->hasTable('landlord_users')) {
            return;
        }

        // Move any residual landlord rows into central `users`, preserving id.
        // INSERT IGNORE so a pre-existing `users` row with the same id/unique key
        // is left untouched. SELECT order matches the INSERT column list 1:1.
        // users.user_name is NOT NULL while landlord_users.user_name is nullable,
        // hence COALESCE(user_name, email). Every other users column omitted here
        // is nullable or defaulted.
        //
        // NOTE: `active`/`is_active` were dropped from `users` by the column-dedup
        // unit (2026_06_15_000001) — SoftDeletes (`deleted_at`, moved below) is now
        // the SOLE source of truth for active/inactive. That drop runs in the auth
        // tier BEFORE platform, so `users` has no `active` column by the time this
        // platform migration runs; the data-move must NOT reference it.
        DB::connection('central')->statement(
            'INSERT IGNORE INTO users ('
            .'id, user_name, phone, email, password, name, '
            .'profile_image, timezone, two_factor_secret, two_factor_recovery_codes, '
            .'two_factor_confirmed_at, last_login_at, last_login_ip, email_verified_at, '
            .'remember_token, created_at, updated_at, deleted_at'
            .') SELECT '
            .'id, COALESCE(user_name, email), phone, email, password, name, '
            .'profile_image, timezone, two_factor_secret, two_factor_recovery_codes, '
            .'two_factor_confirmed_at, last_login_at, last_login_ip, email_verified_at, '
            .'remember_token, created_at, updated_at, deleted_at'
            .' FROM landlord_users'
        );

        $schema->dropIfExists('landlord_users');
    }

    public function down(): void
    {
        $schema = Schema::connection('central');

        if ($schema->hasTable('landlord_users')) {
            return;
        }

        // Recreate the bigint landlord_users structure (mirror of
        // 2025_12_01_131931 update-migration up()) — EMPTY. No data is restored.
        $schema->create('landlord_users', function (Blueprint $table) {
            $table->id();

            $table->string('phone')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name');
            $table->string('user_name')->unique()->nullable();

            $table->boolean('active')->default(true);
            $table->softDeletes();

            $table->string('profile_image')->nullable();
            $table->string('timezone')->default('UTC');

            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('active');
            $table->index(['email', 'active']);
        });
    }
};
