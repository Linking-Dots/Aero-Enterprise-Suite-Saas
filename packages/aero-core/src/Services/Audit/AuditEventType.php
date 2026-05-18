<?php

declare(strict_types=1);

namespace Aero\Core\Services\Audit;

enum AuditEventType: string
{
    // Auth
    case LOGIN = 'auth.login';
    case LOGOUT = 'auth.logout';
    case LOGIN_FAILED = 'auth.login_failed';
    case PASSWORD_RESET = 'auth.password_reset';
    case MFA_ENABLED = 'auth.mfa_enabled';
    case MFA_DISABLED = 'auth.mfa_disabled';
    case SESSION_REVOKED = 'auth.session_revoked';

    // Data mutations
    case RECORD_CREATED = 'data.created';
    case RECORD_UPDATED = 'data.updated';
    case RECORD_DELETED = 'data.deleted';
    case RECORD_RESTORED = 'data.restored';

    // HRM
    case LEAVE_APPROVED = 'hrm.leave.approved';
    case LEAVE_REJECTED = 'hrm.leave.rejected';
    case LEAVE_CANCELLED = 'hrm.leave.cancelled';
    case PAYROLL_RUN = 'hrm.payroll.run';
    case PAYSLIP_GENERATED = 'hrm.payroll.payslip_generated';
    case CLOCK_IN = 'hrm.attendance.clock_in';
    case CLOCK_OUT = 'hrm.attendance.clock_out';
    case OVERTIME_APPROVED = 'hrm.overtime.approved';
    case OVERTIME_REJECTED = 'hrm.overtime.rejected';
    case SHIFT_SWAP_APPROVED = 'hrm.shift_swap.approved';

    // Finance
    case INVOICE_SENT = 'finance.invoice.sent';
    case PAYMENT_RECORDED = 'finance.payment.recorded';
    case JOURNAL_POSTED = 'finance.journal.posted';

    // Platform
    case TENANT_PROVISIONED = 'platform.tenant.provisioned';
    case TENANT_SUSPENDED = 'platform.tenant.suspended';
    case SUBSCRIPTION_CHANGED = 'platform.subscription.changed';

    // Security
    case PERMISSION_CHANGED = 'security.permission_changed';
    case ROLE_ASSIGNED = 'security.role_assigned';
    case ROLE_REVOKED = 'security.role_revoked';
    case DATA_EXPORTED = 'security.data_exported';
    case SENSITIVE_VIEWED = 'security.sensitive_viewed';

    // GDPR
    case DATA_EXPORT_REQUESTED = 'gdpr.export_requested';
    case DATA_ERASURE_REQUESTED = 'gdpr.erasure_requested';
    case DATA_ANONYMIZED = 'gdpr.anonymized';
    case CONSENT_GIVEN = 'gdpr.consent_given';
    case CONSENT_WITHDRAWN = 'gdpr.consent_withdrawn';
}
