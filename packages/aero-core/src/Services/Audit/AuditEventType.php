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

    // Tenant settings (CA-2)
    case SETTINGS_UPDATED = 'core.settings.updated';

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

    // P-7 — Integrations / API Keys
    case API_KEY_CREATED = 'platform.api_key.created';
    case API_KEY_REVOKED = 'platform.api_key.revoked';

    // P-7 — Webhook Endpoints
    case WEBHOOK_ENDPOINT_CREATED = 'platform.webhook.endpoint_created';
    case WEBHOOK_ENDPOINT_UPDATED = 'platform.webhook.endpoint_updated';
    case WEBHOOK_ENDPOINT_DELETED = 'platform.webhook.endpoint_deleted';
    case WEBHOOK_ENDPOINT_TESTED = 'platform.webhook.endpoint_tested';
    case WEBHOOK_SECRET_ROTATED = 'platform.webhook.secret_rotated';
    case WEBHOOK_LOG_REPLAYED = 'platform.webhook.log_replayed';

    // P-7 — Feature Flags
    case FEATURE_FLAG_CREATED = 'platform.feature_flag.created';
    case FEATURE_FLAG_UPDATED = 'platform.feature_flag.updated';
    case FEATURE_FLAG_ARCHIVED = 'platform.feature_flag.archived';
    case FEATURE_FLAG_TOGGLED = 'platform.feature_flag.toggled';
    case FEATURE_FLAG_OVERRIDE_SET = 'platform.feature_flag.override_set';
    case FEATURE_FLAG_OVERRIDE_REMOVED = 'platform.feature_flag.override_removed';

    // P-7 — Experiments
    case EXPERIMENT_STARTED = 'platform.experiment.started';
    case EXPERIMENT_STOPPED = 'platform.experiment.stopped';

    // P-7 — Tenant Communications
    case BROADCAST_CREATED = 'platform.broadcast.created';
    case BROADCAST_PUBLISHED = 'platform.broadcast.published';
    case BROADCAST_DISMISSED_ALL = 'platform.broadcast.dismissed_all';
    case EMAIL_BLAST_CREATED = 'platform.email_blast.created';
    case EMAIL_BLAST_SENT = 'platform.email_blast.sent';
    case MAINTENANCE_WINDOW_SCHEDULED = 'platform.maintenance_window.scheduled';
    case MAINTENANCE_WINDOW_CANCELLED = 'platform.maintenance_window.cancelled';

    // P-8 — Advanced Billing: Coupons & Campaigns
    case COUPON_CREATED = 'platform.coupon.created';
    case COUPON_UPDATED = 'platform.coupon.updated';
    case COUPON_ARCHIVED = 'platform.coupon.archived';
    case COUPON_BULK_GENERATED = 'platform.coupon.bulk_generated';
    case CAMPAIGN_CREATED = 'platform.campaign.created';
    case CAMPAIGN_LAUNCHED = 'platform.campaign.launched';
    case CAMPAIGN_ENDED = 'platform.campaign.ended';

    // P-8 — Advanced Billing: Add-ons & Metered Billing
    case ADDON_CREATED = 'platform.addon.created';
    case ADDON_UPDATED = 'platform.addon.updated';
    case ADDON_ARCHIVED = 'platform.addon.archived';
    case USAGE_METER_CREATED = 'platform.usage_meter.created';
    case USAGE_METER_CONFIGURED = 'platform.usage_meter.configured';
    case PAYG_CONFIG_UPDATED = 'platform.payg.config_updated';

    // P-8 — Advanced Billing: Refunds & Credit Notes
    case REFUND_CREATED = 'platform.refund.created';
    case REFUND_APPROVED = 'platform.refund.approved';
    case REFUND_PROCESSED = 'platform.refund.processed';
    case CREDIT_NOTE_CREATED = 'platform.credit_note.created';
    case CREDIT_NOTE_APPLIED = 'platform.credit_note.applied';

    // P-8 — Advanced Billing: Dunning
    case DUNNING_RULE_UPSERTED = 'platform.dunning.rule_upserted';
    case DUNNING_PAYMENT_RETRY = 'platform.dunning.payment_retry';
    case DUNNING_MARKED_UNCOLLECTIBLE = 'platform.dunning.marked_uncollectible';

    // P-9 — Tax Engine
    case TAX_RATE_UPSERTED = 'platform.tax.rate_upserted';
    case TAX_PROVIDER_CONFIGURED = 'platform.tax.provider_configured';
    case TAX_REPORT_GENERATED = 'platform.tax.report_generated';
    case TAX_REPORT_EXPORTED = 'platform.tax.report_exported';
    case TAX_W9_GENERATED = 'platform.tax.w9_generated';

    // P-9 — Multi-Currency
    case CURRENCY_UPSERTED = 'platform.currency.upserted';
    case EXCHANGE_RATES_SYNCED = 'platform.currency.rates_synced';
    case EXCHANGE_RATE_SET_MANUAL = 'platform.currency.rate_set_manual';
    case REGIONAL_PRICE_UPSERTED = 'platform.currency.regional_price_upserted';

    // P-9 — Invoicing Engine
    case INVOICE_CREATED = 'platform.invoice.created';
    case INVOICE_UPDATED = 'platform.invoice.updated';
    case PLATFORM_INVOICE_SENT = 'platform.invoice.sent';
    case INVOICE_VOIDED = 'platform.invoice.voided';
    case INVOICE_PDF_DOWNLOADED = 'platform.invoice.pdf_downloaded';
    case INVOICE_SETTINGS_UPDATED = 'platform.invoice.settings_updated';
    case INVOICE_TEMPLATE_UPSERTED = 'platform.invoice.template_upserted';
    case INVOICE_BRANDING_UPDATED = 'platform.invoice.branding_updated';

    // P-9 — Payment Methods Admin
    case PAYMENT_METHOD_ADDED = 'platform.payment_method.added';
    case PAYMENT_METHOD_REMOVED = 'platform.payment_method.removed';
    case PAYMENT_METHOD_SET_DEFAULT = 'platform.payment_method.set_default';
    case THREE_DS_CONFIG_UPDATED = 'platform.payment_method.3ds_config_updated';

    // P-9 — Subscription Lifecycle
    case SUBSCRIPTION_TRIAL_EXTENDED = 'platform.subscription.trial_extended';
    case SUBSCRIPTION_TRIAL_CONVERTED = 'platform.subscription.trial_converted';
    case SUBSCRIPTION_PLAN_CHANGED = 'platform.subscription.plan_changed';
    case SUBSCRIPTION_PAUSED = 'platform.subscription.paused';
    case SUBSCRIPTION_RESUMED = 'platform.subscription.resumed';
    case SUBSCRIPTION_CANCELLATION_FLOW_UPDATED = 'platform.subscription.cancellation_flow_updated';

    // P-9 — Reseller Partners
    case PARTNER_CREATED = 'platform.partner.created';
    case PARTNER_UPDATED = 'platform.partner.updated';
    case PARTNER_APPROVED = 'platform.partner.approved';
    case PARTNER_SUSPENDED = 'platform.partner.suspended';
    case PARTNER_COMMISSION_PAYOUT_PROCESSED = 'platform.partner.commission_payout_processed';
    case PARTNER_TENANT_REASSIGNED = 'platform.partner.tenant_reassigned';
    case PARTNER_PORTAL_CONFIGURED = 'platform.partner.portal_configured';

    // P-10 — White-Label
    case CUSTOM_DOMAIN_ADDED = 'platform.white_label.domain_added';
    case CUSTOM_DOMAIN_VERIFIED = 'platform.white_label.domain_verified';
    case CUSTOM_DOMAIN_REMOVED = 'platform.white_label.domain_removed';
    case SSL_PROVISIONED = 'platform.white_label.ssl_provisioned';
    case SSL_RENEWED = 'platform.white_label.ssl_renewed';
    case TENANT_BRANDING_UPDATED = 'platform.white_label.branding_updated';
    case TENANT_CSS_UPDATED = 'platform.white_label.css_updated';
    case DKIM_CONFIGURED = 'platform.white_label.dkim_configured';
    case DKIM_VERIFIED = 'platform.white_label.dkim_verified';

    // P-10 — Backup & Restore
    case BACKUP_SCHEDULE_CREATED = 'platform.backup.schedule_created';
    case BACKUP_SCHEDULE_UPDATED = 'platform.backup.schedule_updated';
    case BACKUP_SCHEDULE_DELETED = 'platform.backup.schedule_deleted';
    case BACKUP_MANUAL_TRIGGERED = 'platform.backup.manual_triggered';
    case BACKUP_RESTORE_INITIATED = 'platform.backup.restore_initiated';
    case BACKUP_STORAGE_CONFIGURED = 'platform.backup.storage_configured';

    // P-10 — Status & Incidents
    case STATUS_COMPONENT_CREATED = 'platform.status.component_created';
    case STATUS_COMPONENT_UPDATED = 'platform.status.component_updated';
    case STATUS_COMPONENT_DELETED = 'platform.status.component_deleted';
    case STATUS_COMPONENT_STATUS_SET = 'platform.status.component_status_set';
    case STATUS_INCIDENT_CREATED = 'platform.status.incident_created';
    case STATUS_INCIDENT_UPDATED = 'platform.status.incident_updated';
    case STATUS_INCIDENT_RESOLVED = 'platform.status.incident_resolved';
    case STATUS_POSTMORTEM_CREATED = 'platform.status.postmortem_created';
    case STATUS_POSTMORTEM_PUBLISHED = 'platform.status.postmortem_published';

    // P-10 — Platform Security
    case STAFF_SESSION_FORCE_LOGOUT = 'platform.security.session_force_logout';
    case STAFF_MFA_ENFORCED = 'platform.security.mfa_enforced';
    case STAFF_MFA_RESET = 'platform.security.mfa_reset';
    case STAFF_SSO_CONFIGURED = 'platform.security.sso_configured';
    case IP_ALLOWLIST_ENTRY_ADDED = 'platform.security.ip_allowlist_added';
    case IP_ALLOWLIST_ENTRY_REMOVED = 'platform.security.ip_allowlist_removed';

    // P-10 — Security Center
    case SECURITY_INCIDENT_CREATED = 'platform.security_center.incident_created';
    case SECURITY_INCIDENT_TENANTS_NOTIFIED = 'platform.security_center.tenants_notified';
    case PENTEST_REPORT_UPLOADED = 'platform.security_center.pentest_uploaded';
    case PENTEST_REPORT_SHARED = 'platform.security_center.pentest_shared';

    // P-11 — Customer Success
    case CS_HEALTH_SCORES_COMPUTED = 'platform.customer_success.health_scores_computed';
    case CS_NPS_SURVEY_SENT = 'platform.customer_success.nps_survey_sent';
    case CS_CSM_ASSIGNED = 'platform.customer_success.csm_assigned';
    case CS_PLAYBOOK_EXECUTED = 'platform.customer_success.playbook_executed';

    // P-11 — Help Center
    case KB_ARTICLE_CREATED = 'platform.help_center.article_created';
    case KB_ARTICLE_UPDATED = 'platform.help_center.article_updated';
    case KB_ARTICLE_PUBLISHED = 'platform.help_center.article_published';
    case KB_ARTICLE_DELETED = 'platform.help_center.article_deleted';
    case TICKET_REPLIED = 'platform.help_center.ticket_replied';
    case TICKET_ASSIGNED = 'platform.help_center.ticket_assigned';
    case TICKET_ESCALATED = 'platform.help_center.ticket_escalated';
    case TICKET_CLOSED = 'platform.help_center.ticket_closed';

    // P-11 — Compliance
    case DPA_SIGNED = 'platform.compliance.dpa_signed';
    case TOS_PUBLISHED = 'platform.compliance.tos_published';
    case TOS_RE_ACCEPTANCE_REQUIRED = 'platform.compliance.tos_re_acceptance_required';
    case DSAR_FULFILLED = 'platform.compliance.dsar_fulfilled';

    // P-11 — Multi-Region
    case REGION_ENABLED = 'platform.multi_region.region_enabled';
    case REGION_DISABLED = 'platform.multi_region.region_disabled';
    case TENANT_REGION_ASSIGNED = 'platform.multi_region.tenant_assigned';
    case REGION_CDN_CONFIGURED = 'platform.multi_region.cdn_configured';

    // P-11 — Secrets Management
    case KMS_KEY_ROTATED = 'platform.secrets.kms_key_rotated';
    case TENANT_DEK_ROTATED = 'platform.secrets.tenant_dek_rotated';
    case VAULT_SECRET_STORED = 'platform.secrets.vault_secret_stored';
    case VAULT_SECRET_REVOKED = 'platform.secrets.vault_secret_revoked';

    // P-11 — Observability
    case OBSERVABILITY_ALERT_CREATED = 'platform.observability.alert_created';

    // P-11 — Disaster Recovery
    case DR_RUNBOOK_CREATED = 'platform.dr.runbook_created';
    case DR_RUNBOOK_EXECUTED = 'platform.dr.runbook_executed';
    case DR_RTO_RPO_CONFIGURED = 'platform.dr.rto_rpo_configured';
    case DR_DRILL_SCHEDULED = 'platform.dr.drill_scheduled';

    // P-11 — Enterprise SCIM
    case SCIM_ENDPOINT_CONFIGURED = 'platform.scim.endpoint_configured';
    case SCIM_TOKEN_ROTATED = 'platform.scim.token_rotated';

    // P-11 — Contract Management
    case MSA_CREATED = 'platform.contracts.msa_created';
    case MSA_SIGNED = 'platform.contracts.msa_signed';
    case ORDER_FORM_CREATED = 'platform.contracts.order_form_created';
    case ORDER_FORM_SENT = 'platform.contracts.order_form_sent';
    case ORDER_FORM_ACTIVATED = 'platform.contracts.order_form_activated';
    case RATE_CARD_CREATED = 'platform.contracts.rate_card_created';

    // P-11 — API Gateway
    case API_RATE_LIMIT_UPDATED = 'platform.api_gateway.rate_limit_updated';
    case API_QUOTA_CONFIGURED = 'platform.api_gateway.quota_configured';

    // P-11 — Resource Provisioning
    case DB_POOL_CREATED = 'platform.provisioning.db_pool_created';
    case STORAGE_BACKEND_CREATED = 'platform.provisioning.storage_backend_created';
    case AUTO_SCALING_CONFIGURED = 'platform.provisioning.auto_scaling_configured';

    // P-11 — Release Management
    case RELEASE_VERSION_PUBLISHED = 'platform.release.version_published';
    case RELEASE_ROLLOUT_INITIATED = 'platform.release.rollout_initiated';
    case RELEASE_ROLLED_BACK = 'platform.release.rolled_back';
    case CHANGELOG_PUBLISHED = 'platform.release.changelog_published';

    // P-11 — License Management
    case LICENSE_KEY_GENERATED = 'platform.license.key_generated';
    case LICENSE_KEY_REVOKED = 'platform.license.key_revoked';
    case LICENSE_KEY_EXTENDED = 'platform.license.key_extended';
    case LICENSE_ACTIVATED = 'platform.license.activated';
    case LICENSE_DEACTIVATED = 'platform.license.deactivated';
}
