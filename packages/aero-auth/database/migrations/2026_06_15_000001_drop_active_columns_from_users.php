<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the vestigial `active` / `is_active` boolean columns from `users`.
 *
 * Active/inactive is now managed exclusively via Laravel SoftDeletes (Boss ruling
 * 2026-06-14/15): active = NOT soft-deleted. Every reader was repointed to
 * trashed() in the auth relocate-and-merge program (Unit 3c), so these columns
 * are dead. This forward migration removes them.
 *
 * Forward/corrective migration (NOT an edit of the create-migration): the
 * standalone host already ran 0001_01_01_000002_create_users_table, so editing
 * that migration would not remove the columns there. Running this last in the
 * chain drops them everywhere — on a fresh install the columns are created by the
 * create-migration and then dropped here. Guarded with hasColumn() so it is
 * idempotent and safe regardless of prior state.
 *
 * Plain Schema (default connection): in SaaS the central-migration phase runs with
 * the active connection pointed at central; in standalone the default IS the only
 * DB — mirroring how the create-migration targets `users`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['active', 'is_active'],
                fn ($col) => Schema::hasColumn('users', $col)
            ));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true);
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }
};
