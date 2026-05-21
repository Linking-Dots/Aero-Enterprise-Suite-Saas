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

    // HRM — Performance Management
    case REVIEW_CYCLE_ACTIVATED = 'hrm.performance.cycle_activated';
    case PERFORMANCE_REVIEW_FINALIZED = 'hrm.performance.review_finalized';
    case GOAL_CLOSED = 'hrm.performance.goal_closed';
    case FEEDBACK_360_OPENED = 'hrm.performance.feedback_360_opened';
    case PIP_CREATED = 'hrm.performance.pip_created';

    // Tenant lifecycle (P-1)
    case TENANT_CREATED = 'platform.tenant.created';
    case TENANT_UPDATED = 'platform.tenant.updated';
    case TENANT_ACTIVATED = 'platform.tenant.activated';
    case TENANT_FROZEN = 'platform.tenant.frozen';
    case TENANT_UNFROZEN = 'platform.tenant.unfrozen';
    case TENANT_ARCHIVED = 'platform.tenant.archived';
    case TENANT_RESTORED = 'platform.tenant.restored';
    case TENANT_PURGED = 'platform.tenant.purged';
    case TENANT_BYOC_UPDATED = 'platform.tenant.byoc_updated';
    case TENANT_EXPORT_REQUESTED = 'platform.tenant.export_requested';
    case TENANT_IMPERSONATION_STARTED = 'platform.tenant.impersonation_started';
    case TENANT_IMPERSONATION_ENDED = 'platform.tenant.impersonation_ended';
    case TENANT_PROVISIONING_QUEUED = 'platform.tenant.provisioning_queued';
    case TENANT_PROVISIONING_RETRIED = 'platform.tenant.provisioning_retried';
    case TENANT_APPROVED = 'platform.tenant.approved';
    case TENANT_REJECTED = 'platform.tenant.rejected';
    case TENANT_TRIAL_EXTENDED = 'platform.tenant.trial_extended';
    case TENANT_TRIAL_CONVERTED = 'platform.tenant.trial_converted';
    case TENANT_DOMAIN_ADDED = 'platform.tenant.domain_added';
    case TENANT_DOMAIN_REMOVED = 'platform.tenant.domain_removed';
    case TENANT_DOMAIN_VERIFIED = 'platform.tenant.domain_verified';
    case TENANT_DB_MIGRATED = 'platform.tenant.db_migrated';
    case TENANT_DB_BACKUP_REQUESTED = 'platform.tenant.db_backup_requested';
    case TENANT_BULK_OPERATION_QUEUED = 'platform.tenant.bulk_operation_queued';
    case PRODUCT_SUBSCRIPTIONS_CANCELLED = 'platform.product_subscriptions.cancelled';

    // Plans & billing (P-2)
    case PLAN_CREATED = 'platform.plan.created';
    case PLAN_UPDATED = 'platform.plan.updated';
    case PLAN_DELETED = 'platform.plan.deleted';
    case PLAN_ARCHIVED = 'platform.plan.archived';
    case PLAN_CLONED = 'platform.plan.cloned';
    case SUBSCRIPTION_CANCELLED = 'platform.subscription.cancelled';
    case SUBSCRIPTION_UPGRADED = 'platform.subscription.upgraded';
    case INVOICE_GENERATED = 'platform.invoice.generated';
    case INVOICE_MARKED_PAID = 'platform.invoice.marked_paid';
    case PAYMENT_GATEWAY_UPDATED = 'platform.payment_gateway.updated';

    // P-4 settings / users / roles
    case PLATFORM_SETTING_UPDATED = 'platform.setting.updated';
    case LANDLORD_USER_CREATED = 'platform.user.created';
    case LANDLORD_USER_UPDATED = 'platform.user.updated';
    case LANDLORD_USER_DELETED = 'platform.user.deleted';
    case LANDLORD_USER_STATUS_TOGGLED = 'platform.user.status_toggled';
    case LANDLORD_ROLE_CREATED = 'platform.role.created';
    case LANDLORD_ROLE_UPDATED = 'platform.role.updated';
    case LANDLORD_ROLE_DELETED = 'platform.role.deleted';
    case LANDLORD_ROLE_CLONED = 'platform.role.cloned';
    case MODULE_TOGGLED = 'platform.module.toggled';
    case MODULE_CONFIGURED = 'platform.module.configured';
}
