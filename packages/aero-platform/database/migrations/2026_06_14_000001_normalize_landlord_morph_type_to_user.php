<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auth-identity unification — Unit 4 (LandlordUser -> User morph normalization).
 *
 * Before Unit 4, landlords were the LandlordUser subclass, which was NOT in the
 * morph map, so its role pivot rows in `model_has_roles` were written with the
 * class FQN 'Aero\Platform\Models\LandlordUser' as model_type (and
 * 2026_06_09_000001_normalize_user_morph_type deliberately skipped them).
 *
 * Unit 4 eliminated LandlordUser: landlords are now the unified
 * Aero\Auth\Models\User, which IS in the morph map as 'user'
 * (AeroCoreServiceProvider::registerIdentityModelAliases). So every existing
 * landlord role row must be repointed from the old FQN to the 'user' morph key,
 * or those landlords would lose their roles.
 *
 * Runs on the CENTRAL connection (landlord roles live in the central
 * model_has_roles). No-op on a fresh install (no rows yet) and idempotent.
 */
return new class extends Migration
{
    private const OLD_TYPE = 'Aero\\Platform\\Models\\LandlordUser';

    private const NEW_TYPE = 'user';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('model_has_roles')) {
            return;
        }

        DB::connection('central')->table('model_has_roles')
            ->where('model_type', self::OLD_TYPE)
            ->update(['model_type' => self::NEW_TYPE]);
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('model_has_roles')) {
            return;
        }

        // Central model_has_roles only holds landlord rows, so reversing every
        // 'user' row back to the legacy FQN is safe in this context.
        DB::connection('central')->table('model_has_roles')
            ->where('model_type', self::NEW_TYPE)
            ->update(['model_type' => self::OLD_TYPE]);
    }
};
