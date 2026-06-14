<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contract for the user-invitation service.
 *
 * aero-auth provides the concrete UserInvitationService (which owns the richer
 * admin-side surface: list/invite/resend/cancel over its UserInvitation model).
 * Consumer packages depend ONLY on this interface — the token-acceptance
 * surface — so they never name aero-auth internals.
 *
 * Return types are intentionally widened to framework/abstract types so the contract
 * stays pure (it must not reference aero-auth models); the concrete implementation
 * narrows them covariantly (?object -> ?UserInvitation, Authenticatable -> User).
 */
interface UserInvitationServiceInterface
{
    /**
     * Look up an invitation by its token (null if not found).
     */
    public function getInvitationByToken(string $token): ?object;

    /**
     * Accept an invitation and create the user account, returning the new user.
     *
     * @param  array<string, mixed>  $userData
     */
    public function acceptInvitation(string $token, array $userData): Authenticatable;
}
