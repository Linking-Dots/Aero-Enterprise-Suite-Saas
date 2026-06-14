<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Unit\Models;

use Aero\Auth\Models\UserInvitation;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/**
 * Regression pin for the relocate-and-merge of UserInvitation (aero-core -> aero-auth).
 *
 * When UserInvitation dropped Aero\Contracts\Models\TenantModel for a plain
 * Illuminate Model (auth is context-free), it MUST re-declare getAuditLabel(),
 * which TenantModel previously supplied. AuditService::log() calls
 * $subject->getAuditLabel() on the audit subject, and InvitationController::accept()
 * passes the accepted invitation AS the subject. Without getAuditLabel() the audit
 * write throws and is silently swallowed (catch \Throwable), dropping the audit row
 * for every accepted invitation. These tests fail closed if it is ever removed again.
 */
class UserInvitationTest extends TestCase
{
    public function test_is_a_plain_eloquent_model_not_tenant_model(): void
    {
        $invitation = new UserInvitation();

        $this->assertInstanceOf(Model::class, $invitation);
        // Context-free auth: identity models are plain Models, not TenantModel.
        $this->assertNotInstanceOf(
            TenantModel::class,
            $invitation,
            'UserInvitation must be a plain Model (auth is context-free), not a TenantModel.'
        );
    }

    public function test_provides_audit_label_as_subject_for_audit_service(): void
    {
        // AuditService::log() calls $subject->getAuditLabel(); the accept flow passes
        // the invitation as the subject. The method must exist and return the key as a
        // string (matching the TenantModel/TenantInvitation precedent).
        $this->assertTrue(
            method_exists(UserInvitation::class, 'getAuditLabel'),
            'UserInvitation must declare getAuditLabel() — required by AuditService::log().'
        );

        $invitation = new UserInvitation();
        $invitation->setAttribute('id', 42);

        $this->assertSame('42', $invitation->getAuditLabel());
    }
}
