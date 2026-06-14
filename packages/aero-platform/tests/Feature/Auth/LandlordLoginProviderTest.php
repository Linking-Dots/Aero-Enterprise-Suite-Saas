<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Auth;

use Aero\Platform\Database\Factories\LandlordUserFactory;
use Aero\Platform\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Safety-net for landlord LOGIN under the unified, context-free auth.
 *
 * The whole platform suite authenticates via actingAs($admin, 'landlord'), which
 * BYPASSES the user provider — so landlord login (the provider resolving a credential
 * set and validating the password) is otherwise untested.
 *
 * Auth is context-free: the `landlord` guard is plain eloquent over the unified
 * Aero\Auth\Models\User on whatever connection is ACTIVE. There is no landlord/central
 * binding in auth — in production the admin domain's active connection is the central
 * DB; isolation is the infrastructure's concern. This pins that login resolves a
 * landlord by email and validates the password.
 */
class LandlordLoginProviderTest extends TestCase
{
    /** The actual provider the `landlord` guard uses (config-driven, not hardcoded). */
    private function landlordProvider()
    {
        return Auth::guard('landlord')->getProvider();
    }

    public function test_landlord_provider_resolves_credentials(): void
    {
        $landlord = LandlordUserFactory::new()->create([
            'email' => 'landlord-login@example.com',
            'password' => Hash::make('secret-pass-123'),
        ]);

        $retrieved = $this->landlordProvider()->retrieveByCredentials([
            'email' => 'landlord-login@example.com',
            'password' => 'secret-pass-123',
        ]);

        $this->assertNotNull($retrieved, 'landlord provider must resolve the landlord by email');
        $this->assertSame($landlord->getKey(), $retrieved->getAuthIdentifier());
    }

    public function test_landlord_provider_validates_password(): void
    {
        LandlordUserFactory::new()->create([
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

    public function test_landlord_persisted_in_users_table(): void
    {
        LandlordUserFactory::new()->create(['email' => 'table-check@example.com']);

        $this->assertDatabaseHas('users', ['email' => 'table-check@example.com']);
    }
}
