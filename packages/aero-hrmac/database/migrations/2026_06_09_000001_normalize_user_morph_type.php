<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dependency decoupling — Phase 2 (cut auth → core by moving the User model out of
 * aero-core into aero-auth).
 *
 * model_has_roles / model_has_permissions persist a polymorphic model_type. Before
 * this phase the tenant User was stored as its raw class FQN, so the model could not
 * change package without orphaning every existing role/permission row. The User morph
 * identity is now bound to a stable morph key ('user') via Relation::morphMap()
 * (registered in AeroCoreServiceProvider).
 *
 * This migration normalises any pre-existing rows that were written with a legacy User
 * FQN to the new 'user' morph key, in whichever context it runs (tenant / standalone /
 * central). It only ever touches User rows — User and every other morph type
 * are untouched — and is idempotent (safe to re-run).
 */
return new class extends Migration
{
    /**
     * Legacy model_type strings that historically identified the tenant User model.
     *  - Aero\Core\Models\User : the package model FQN (now Aero\Auth\Models\User)
     *  - App\Models\User       : written by the installation wizard's AdminUserStep
     */
    private array $legacyUserTypes = [
        'Aero\\Core\\Models\\User',
        'Aero\\Auth\\Models\\User',
        'App\\Models\\User',
    ];

    public function up(): void
    {
        // Each pivot keys on a different owning column; the full unique key is
        // (<owner>, model_id, model_type). Because a single user+role/permission pair
        // may exist under MORE THAN ONE legacy User FQN (e.g. the core model wrote
        // Aero\Core\Models\User while the installer wrote App\Models\User), a blind
        // UPDATE to 'user' would collide on that unique key. So we delete the legacy
        // rows and re-insert a single de-duplicated 'user' row per pair.
        $tables = [
            'model_has_roles'       => 'role_id',
            'model_has_permissions' => 'permission_id',
        ];

        foreach ($tables as $table => $owner) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $legacyRows = DB::table($table)
                ->whereIn('model_type', $this->legacyUserTypes)
                ->get([$owner, 'model_id']);

            if ($legacyRows->isEmpty()) {
                continue;
            }

            DB::table($table)
                ->whereIn('model_type', $this->legacyUserTypes)
                ->delete();

            foreach ($legacyRows as $row) {
                // updateOrInsert is a no-op when a 'user' row already exists for this
                // pair, so duplicates (and any pre-existing 'user' row) collapse cleanly.
                DB::table($table)->updateOrInsert(
                    [$owner => $row->{$owner}, 'model_id' => $row->model_id, 'model_type' => 'user'],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        // Restore the canonical tenant User FQN. Best-effort: the original rows may have
        // used any of the legacy strings, so we normalise to the current class name.
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->where('model_type', 'user')
                ->update(['model_type' => 'Aero\\Core\\Models\\User']);
        }
    }
};
