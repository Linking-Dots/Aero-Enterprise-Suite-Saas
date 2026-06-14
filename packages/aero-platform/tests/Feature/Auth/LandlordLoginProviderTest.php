<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Auth;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Safety-net for the auth-identity unification rename (LandlordUser -> User, Unit 4).
 *
 * The whole platform suite authenticates via actingAs($admin, 'landlord'), which
 * BYPASSES the user provider — so landlord LOGIN (the provider resolving a
 * credential set against the central `users` table) is otherwise untested. This
 * pins the real behavior the rename must preserve:
 *
 *   1. the `landlord` guard's provider resolves a landlord by email
 *      (retrieveByCredentials) from the CENTRAL connection, and
 *   2. validateCredentials checks the password hash correctly.
 *
 * After LandlordUser is renamed to Aero\Auth\Models\User and the landlord guard
 * is repointed at a central-binding provider, this test MUST still pass — that is
 * the guarantee that the rename did not silently break landlord login.
 */
class LandlordLoginProviderTest extends TestCase
{
    /** The actual provider the `landlord` guard uses (config-driven, not hardcoded). */
    private function landlordProvider()
    {
        return Auth::guard('landlord')->getProvider();
    }

    public function test_landlord_provider_resolves_credentials_on_central_connection(): void
    {
        $landlord = LandlordUser::factory()->create([
            'email' => 'landlord-login@example.com',
            'password' => Hash::make('secret-pass-123'),
        ]);

        $retrieved = $this->landlordProvider()->retrieveByCredentials([
            'email' => 'landlord-login@example.com',
            'password' => 'secret-pass-123',
        ]);

        $this->assertNotNull($retrieved, 'landlord provider must resolve the landlord by email');
        $this->assertSame($landlord->getKey(), $retrieved->getAuthIdentifier());
        $this->assertSame(
            'central',
            $retrieved->getConnectionName(),
            'landlord identity must resolve on the central connection'
        );
    }

    public function test_landlord_provider_validates_password(): void
    {
        LandlordUser::factory()->create([
            'email' => 'pw-check@example.com',
            'password' => Hash::make('right-password'),
        ]);

        $provider = $this->landlordProvider();
        $retrieved = $provider->retrieveByCredentials(['email' => 'pw-check@example.com']);

        $this->assertNotNull($retrieved);
        $this->assertTrue(
            $provider->validateCredentials($retrieved, ['password' => 'right-password']),
            'correct password must validate'
        );
        $this->assertFalse(
            $provider->validateCredentials($retrieved, ['password' => 'wrong-password']),
            'incorrect password must NOT validate'
        );
    }

    public function test_landlord_persisted_in_central_users_table(): void
    {
        $landlord = LandlordUser::factory()->create(['email' => 'table-check@example.com']);

        $this->assertDatabaseHas('users', ['email' => 'table-check@example.com']);
        $this->assertSame('central', $landlord->getConnectionName());
    }
}
