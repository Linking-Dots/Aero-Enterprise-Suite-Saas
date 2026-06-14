<?php

declare(strict_types=1);

namespace Aero\Auth\Providers;

use Illuminate\Auth\EloquentUserProvider;

/**
 * Landlord user provider — resolves platform admins (landlords) from the unified
 * {@see \Aero\Auth\Models\User} model on the CENTRAL connection.
 *
 * Auth-identity unification (Unit 4): the former central-bound User
 * subclass was eliminated, so the shared User model is connection-agnostic.
 * Landlords are rows in the central `users` table, so this provider pins every
 * retrieval (retrieveById / retrieveByCredentials / retrieveByToken — all of
 * which build their query from createModel()) onto the 'central' connection.
 *
 * User's EnforcesTenantContext guard no-ops for central-connection instances
 * (Unit 2B), so a landlord query never trips tenant isolation. validateCredentials
 * is a connection-agnostic hash check and needs no override.
 */
class LandlordUserProvider extends EloquentUserProvider
{
    /**
     * {@inheritDoc}
     */
    public function createModel()
    {
        return parent::createModel()->setConnection('central');
    }
}
