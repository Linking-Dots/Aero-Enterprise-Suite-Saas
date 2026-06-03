# AEOS365 — Core Admin Test Matrix (generated from config/module.php)

> 100% of the foundation + platform module hierarchy, sorted by sub-module priority.
> Each row = one component (page/feature) + its actions to verify. Run live via MCP.
> Status: ⬜ not run · ✅ pass · ❌ fail (log B-) · ⏭ skip. Generated 2026-06-03.

## CA-CORE (tenant-side foundation) — 105 components (from `packages/aero-core/config/module.php`)

| ID | Pr | Code (module.sub.component) | Route | Type | Actions | Status |
|----|----|------------------------------|-------|------|---------|--------|
| CAT-01 | 0 | `core.self_service.my-notifications` | `/notifications` | page | view, mark_read | ⬜ |
| CAT-02 | 0 | `core.self_service.my-profile` | `/profile` | page | view, edit | ⬜ |
| CAT-03 | 1 | `core.dashboard.admin-dashboard` | `/dashboard` | page | view | ⬜ |
| CAT-04 | 1 | `core.dashboard.announcements` | `/dashboard` | feature | view, create, delete | ⬜ |
| CAT-05 | 2 | `core.subscription.invoices` | `/subscription/invoices` | page | view, download | ⬜ |
| CAT-06 | 2 | `core.subscription.plans` | `/subscription/plans` | page | view, upgrade, downgrade, cancel | ⬜ |
| CAT-07 | 2 | `core.subscription.usage` | `/subscription/usage` | page | view | ⬜ |
| CAT-08 | 3 | `core.user_management.user_invitations` | `/users/invitations` | page | view, invite, resend, cancel | ⬜ |
| CAT-09 | 3 | `core.user_management.user_profile` | `/profile` | page | view, edit, change_password, upload_avatar | ⬜ |
| CAT-10 | 3 | `core.user_management.users` | `/users` | page | view, create, edit, delete, bulk_delete, activate, deactivate, bulk_toggle_status, bulk_assign_roles, reset_password, lock_account, unlock_account, impersonate, export, import | ⬜ |
| CAT-11 | 4 | `core.authentication.devices` | `/security/devices` | page | view, toggle, reset, deactivate | ⬜ |
| CAT-12 | 4 | `core.authentication.email_verification` | `/security/verify-email` | page | send, verify, resend | ⬜ |
| CAT-13 | 4 | `core.authentication.password_reset` | `/security/password-reset` | page | request, verify_token, reset | ⬜ |
| CAT-14 | 4 | `core.authentication.sessions` | `/security/sessions` | page | view, terminate, terminate_all | ⬜ |
| CAT-15 | 4 | `core.authentication.two_factor` | `/security/2fa` | feature | view, enable, disable, reset, enroll_totp, enroll_sms, enroll_email, generate_recovery_codes, verify_recovery_code | ⬜ |
| CAT-16 | 5 | `core.roles_permissions.module_access` | `/modules` | page | view, configure, toggle | ⬜ |
| CAT-17 | 5 | `core.roles_permissions.roles` | `/roles` | page | view, create, edit, delete, assign, permissions | ⬜ |
| CAT-18 | 6 | `core.audit_logs.activity_logs` | `/audit-logs/activity` | page | view, export, filter | ⬜ |
| CAT-19 | 6 | `core.audit_logs.queue_monitor` | `/audit-logs/queues` | page | view, retry, flush | ⬜ |
| CAT-20 | 6 | `core.audit_logs.security_logs` | `/audit-logs/security` | page | view, export, investigate | ⬜ |
| CAT-21 | 7 | `core.notifications.channels` | `/notifications/channels` | page | view, configure, test | ⬜ |
| CAT-22 | 7 | `core.notifications.templates` | `/notifications/templates` | page | view, create, edit, delete, preview | ⬜ |
| CAT-23 | 8 | `core.file_manager.media_library` | `/files/media` | page | view, upload, delete, organize | ⬜ |
| CAT-24 | 8 | `core.file_manager.storage` | `/files/storage` | page | view, configure, cleanup | ⬜ |
| CAT-25 | 9 | `core.organization.fiscal_year` | `/organization/fiscal-year` | page | view, manage | ⬜ |
| CAT-26 | 9 | `core.organization.org_addresses` | `/organization/addresses` | page | view, manage | ⬜ |
| CAT-27 | 9 | `core.organization.org_contacts` | `/organization/contacts` | page | view, manage | ⬜ |
| CAT-28 | 9 | `core.organization.org_identity` | `/organization/identity` | page | view, update, verify | ⬜ |
| CAT-29 | 9 | `core.organization.org_profile` | `/organization/profile` | page | view, update | ⬜ |
| CAT-30 | 10 | `core.translations_i18n.languages` | `/i18n/languages` | page | view, enable, disable | ⬜ |
| CAT-31 | 10 | `core.translations_i18n.translation_editor` | `/i18n/translations` | page | view, update, auto_translate, import, export | ⬜ |
| CAT-32 | 12 | `core.custom_fields.field_definitions` | `/custom-fields` | page | view, create, update, delete | ⬜ |
| CAT-33 | 13 | `core.tags_labels.tag_management` | `/tags` | page | view, create, update, delete, merge | ⬜ |
| CAT-34 | 14 | `core.saved_views.saved_views` | `/saved-views` | page | view, create, update, delete, share, set_default | ⬜ |
| CAT-35 | 15 | `core.form_builder.forms` | `/forms` | page | view, create, update, delete, publish | ⬜ |
| CAT-36 | 15 | `core.form_builder.submissions` | `/forms/submissions` | page | view, export, delete | ⬜ |
| CAT-37 | 16 | `core.global_search.search_index` | `/search/index` | page | view, reindex, configure | ⬜ |
| CAT-38 | 16 | `core.global_search.search_ui` | `/search` | page | use | ⬜ |
| CAT-39 | 17 | `core.api_webhooks.api_docs` | `/api/docs` | page | view | ⬜ |
| CAT-40 | 17 | `core.api_webhooks.api_keys` | `/api/keys` | page | view, create, revoke, rotate | ⬜ |
| CAT-41 | 17 | `core.api_webhooks.api_usage` | `/api/usage` | page | view, export | ⬜ |
| CAT-42 | 17 | `core.api_webhooks.pat` | `/api/pat` | page | view, create, revoke | ⬜ |
| CAT-43 | 17 | `core.api_webhooks.rate_limits` | `/api/rate-limits` | page | view, configure | ⬜ |
| CAT-44 | 17 | `core.api_webhooks.webhooks_outbound` | `/api/webhooks` | page | view, create, update, delete, test, logs, replay | ⬜ |
| CAT-45 | 18 | `core.user_preferences.accessibility` | `/preferences/accessibility` | page | view, update | ⬜ |
| CAT-46 | 18 | `core.user_preferences.locale_preferences` | `/preferences/locale` | page | view, update | ⬜ |
| CAT-47 | 18 | `core.user_preferences.notification_preferences` | `/preferences/notifications` | page | view, update, dnd, digest | ⬜ |
| CAT-48 | 18 | `core.user_preferences.theme_preferences` | `/preferences/theme` | page | view, update | ⬜ |
| CAT-49 | 19 | `core.data_export_import.exports` | `/export-import/exports` | page | view, create, download, delete | ⬜ |
| CAT-50 | 19 | `core.data_export_import.imports` | `/export-import/imports` | page | view, create, download_template | ⬜ |
| CAT-51 | 20 | `core.retention_policies.policies` | `/retention-policies` | page | view, create, update, delete, execute | ⬜ |
| CAT-52 | 21 | `core.workflow_engine.approvals` | `/workflow-instances/approvals` | page | view, approve, reject, escalate | ⬜ |
| CAT-53 | 21 | `core.workflow_engine.definitions` | `/workflows` | page | view, create, update, delete, activate, deactivate | ⬜ |
| CAT-54 | 21 | `core.workflow_engine.instances` | `/workflow-instances` | page | view, retry | ⬜ |
| CAT-55 | 21 | `core.workflow_engine.templates` | `/workflow-templates` | page | view, create, update, delete | ⬜ |
| CAT-56 | 22 | `core.email_engine.bounce_complaint` | `/email/bounces` | page | view, export | ⬜ |
| CAT-57 | 22 | `core.email_engine.deliverability` | `/email/deliverability` | page | view, configure, verify | ⬜ |
| CAT-58 | 22 | `core.email_engine.email_logs` | `/email/logs` | page | view, resend, export | ⬜ |
| CAT-59 | 22 | `core.email_engine.email_templates` | `/email/templates` | page | view, create, update, delete, preview, send_test | ⬜ |
| CAT-60 | 22 | `core.email_engine.suppression_list` | `/email/suppression` | page | view, remove | ⬜ |
| CAT-61 | 23 | `core.system_health.cache_management` | `/system-health/cache` | page | view, clear | ⬜ |
| CAT-62 | 23 | `core.system_health.health_status` | `/system-health` | page | view, run_checks | ⬜ |
| CAT-63 | 23 | `core.system_health.performance_metrics` | `/system-health/performance` | page | view, export | ⬜ |
| CAT-64 | 23 | `core.system_health.scheduled_tasks` | `/system-health/scheduled-tasks` | page | view, run_now, pause | ⬜ |
| CAT-65 | 23 | `core.system_health.storage_usage` | `/system-health/storage` | page | view, cleanup | ⬜ |
| CAT-66 | 24 | `core.mobile_pwa.mobile_app_config` | `/mobile-pwa/mobile-app` | page | view, configure | ⬜ |
| CAT-67 | 24 | `core.mobile_pwa.push_notifications` | `/mobile-pwa/push` | page | view, configure, send_test | ⬜ |
| CAT-68 | 24 | `core.mobile_pwa.pwa_config` | `/mobile-pwa/pwa` | page | view, configure | ⬜ |
| CAT-69 | 25 | `core.backup_restore.backup_config` | `/backup/config` | page | view, configure | ⬜ |
| CAT-70 | 25 | `core.backup_restore.backup_dashboard` | `/backup` | page | view | ⬜ |
| CAT-71 | 25 | `core.backup_restore.manual_backup` | `/backup/manual` | page | create, download | ⬜ |
| CAT-72 | 25 | `core.backup_restore.restore_points` | `/backup/restore` | page | view, restore | ⬜ |
| CAT-73 | 26 | `core.license_management.license_activation` | `/license/activate` | page | activate, deactivate, verify | ⬜ |
| CAT-74 | 26 | `core.license_management.license_features` | `/license/features` | page | view | ⬜ |
| CAT-75 | 26 | `core.license_management.license_overview` | `/license` | page | view | ⬜ |
| CAT-76 | 26 | `core.license_management.license_renewal` | `/license/renewal` | page | view, renew | ⬜ |
| CAT-77 | 26 | `core.license_management.updates` | `/license/updates` | page | check, download, apply | ⬜ |
| CAT-78 | 27 | `core.numbering.numbering_formats` | `/numbering/formats` | page | view, manage | ⬜ |
| CAT-79 | 27 | `core.numbering.sequences` | `/numbering/sequences` | page | view, create, update, reset | ⬜ |
| CAT-80 | 28 | `core.print_templates.header_footer` | `/print-templates/header-footer` | page | manage | ⬜ |
| CAT-81 | 28 | `core.print_templates.paper_sizes` | `/print-templates/paper` | page | manage | ⬜ |
| CAT-82 | 28 | `core.print_templates.templates` | `/print-templates` | page | view, create, update, delete, preview | ⬜ |
| CAT-83 | 29 | `core.announcements.announcement_list` | `/announcements` | page | view, create, update, delete, publish, archive | ⬜ |
| CAT-84 | 29 | `core.announcements.banners` | `/announcements/banners` | page | view, manage | ⬜ |
| CAT-85 | 30 | `core.maintenance_mode.maintenance_toggle` | `/maintenance-mode` | page | view, enable, disable, configure, allow_ip | ⬜ |
| CAT-86 | 31 | `core.trash.view` | `/trash` | page | view, restore, force_delete, empty | ⬜ |
| CAT-87 | 32 | `core.comments_mentions.activity_feed` | `/activity` | page | view, export | ⬜ |
| CAT-88 | 32 | `core.comments_mentions.comments` | `/comments` | feature | view, create, update, delete, react, mention | ⬜ |
| CAT-89 | 32 | `core.comments_mentions.mentions_inbox` | `/mentions` | page | view, mark_read | ⬜ |
| CAT-90 | 33 | `core.help_support.feedback` | `/help/feedback` | page | view, submit, vote | ⬜ |
| CAT-91 | 33 | `core.help_support.help_center` | `/help` | page | view | ⬜ |
| CAT-92 | 33 | `core.help_support.knowledge_base` | `/help/kb` | page | view, search | ⬜ |
| CAT-93 | 33 | `core.help_support.live_chat` | `/help` | feature | configure | ⬜ |
| CAT-94 | 33 | `core.help_support.onboarding_tours` | `/help/tours` | page | view, start | ⬜ |
| CAT-95 | 33 | `core.help_support.support_tickets` | `/help/tickets` | page | view, create, reply, close | ⬜ |
| CAT-96 | 33 | `core.help_support.whats_new` | `/help/whats-new` | page | view | ⬜ |
| CAT-97 | 99 | `core.settings.branding` | `/settings/branding` | page | view, update | ⬜ |
| CAT-98 | 99 | `core.settings.email_templates` | `/settings/email-templates` | page | view, create, edit, delete, preview, test_send | ⬜ |
| CAT-99 | 99 | `core.settings.general` | `/settings/system` | page | view, edit | ⬜ |
| CAT-100 | 99 | `core.settings.integrations` | `/settings/integrations` | page | view, configure, manage_keys | ⬜ |
| CAT-101 | 99 | `core.settings.ip_whitelist` | `/settings/ip-whitelist` | page | view, edit, block, geo_block | ⬜ |
| CAT-102 | 99 | `core.settings.localization` | `/settings/localization` | page | view, edit | ⬜ |
| CAT-103 | 99 | `core.settings.mail_settings` | `/settings/mail` | page | view, update, test | ⬜ |
| CAT-104 | 99 | `core.settings.password_policy` | `/settings/password-policy` | page | view, edit | ⬜ |
| CAT-105 | 99 | `core.settings.security` | `/settings/security` | page | view, edit, enable_2fa, disable_2fa | ⬜ |

## CA-PLATFORM (landlord/platform side) — 250 components (from `packages/aero-platform/config/module.php`)

| ID | Pr | Code (module.sub.component) | Route | Type | Actions | Status |
|----|----|------------------------------|-------|------|---------|--------|
| CAP-01 | 1 | `platform.platform-dashboard.dashboard-overview` | `/dashboard` | page | view | ⬜ |
| CAP-02 | 2 | `platform.tenants.tenant-databases` | `/tenants/databases` | page | view, migrate, backup | ⬜ |
| CAP-03 | 2 | `platform.tenants.tenant-domains` | `/tenants/domains` | page | view, manage | ⬜ |
| CAP-04 | 2 | `platform.tenants.tenant-list` | `/tenants` | page | view, create, edit, delete, suspend, activate, impersonate, forget | ⬜ |
| CAP-05 | 3 | `platform.platform-onboarding.onboarding-analytics` | `/onboarding/analytics` | page | view, export | ⬜ |
| CAP-06 | 3 | `platform.platform-onboarding.onboarding-automation` | `/onboarding/automation` | page | view, manage, execute | ⬜ |
| CAP-07 | 3 | `platform.platform-onboarding.onboarding-dashboard` | `/onboarding` | page | view | ⬜ |
| CAP-08 | 3 | `platform.platform-onboarding.onboarding-settings` | `/onboarding/settings` | page | view, update | ⬜ |
| CAP-09 | 3 | `platform.platform-onboarding.pending-approvals` | `/onboarding/pending` | page | view, approve, reject | ⬜ |
| CAP-10 | 3 | `platform.platform-onboarding.provisioning` | `/onboarding/provisioning` | page | view, retry | ⬜ |
| CAP-11 | 3 | `platform.platform-onboarding.trials` | `/onboarding/trials` | page | view, extend, convert | ⬜ |
| CAP-12 | 4 | `platform.plan-management.plan-details` | `/plans/{id}` | page | view, view-subscribers, view-revenue, export | ⬜ |
| CAP-13 | 4 | `platform.plan-management.plan-list` | `/plans` | page | view, create, edit, delete, archive, clone | ⬜ |
| CAP-14 | 5 | `platform.quota-management.quota-analytics` | `/quotas/analytics` | page | view, export | ⬜ |
| CAP-15 | 5 | `platform.quota-management.quota-dashboard` | `/quotas` | page | view, override, dismiss-warnings | ⬜ |
| CAP-16 | 5 | `platform.quota-management.quota-settings` | `/quotas/settings` | page | view, edit | ⬜ |
| CAP-17 | 6 | `platform.billing-management.billing-dashboard` | `/billing` | page | view | ⬜ |
| CAP-18 | 6 | `platform.billing-management.invoices` | `/billing/invoices` | page | view, generate, send, mark-paid | ⬜ |
| CAP-19 | 6 | `platform.billing-management.payment-gateways` | `/billing/gateways` | page | view, configure | ⬜ |
| CAP-20 | 6 | `platform.billing-management.subscriptions` | `/billing/subscriptions` | page | view, cancel, upgrade | ⬜ |
| CAP-21 | 7 | `platform.module-management.module-list` | `/modules` | page | view, configure, toggle-active | ⬜ |
| CAP-22 | 7 | `platform.module-management.module-pricing` | `/modules/pricing` | page | view, edit | ⬜ |
| CAP-23 | 8 | `platform.error-monitoring.error-analytics` | `/error-logs/analytics` | page | view | ⬜ |
| CAP-24 | 8 | `platform.error-monitoring.error-log-list` | `/error-logs` | page | view, resolve, delete | ⬜ |
| CAP-25 | 9 | `platform.platform-users.landlord-roles` | `/roles` | page | view, manage | ⬜ |
| CAP-26 | 9 | `platform.platform-users.landlord-user-list` | `/users` | page | view, create, edit, delete | ⬜ |
| CAP-27 | 9 | `platform.platform-users.module-access` | `/module-access` | page | view, manage | ⬜ |
| CAP-28 | 10 | `platform.platform-integrations.api-keys` | `/integrations/api` | page | view, create, revoke | ⬜ |
| CAP-29 | 10 | `platform.platform-integrations.connectors` | `/integrations/connectors` | page | view, configure | ⬜ |
| CAP-30 | 10 | `platform.platform-integrations.webhooks` | `/integrations/webhooks` | page | view, manage | ⬜ |
| CAP-31 | 11 | `platform.system-settings.branding-settings` | `/settings/branding` | page | view, edit | ⬜ |
| CAP-32 | 11 | `platform.system-settings.email-settings` | `/settings/email` | page | view, edit, test | ⬜ |
| CAP-33 | 11 | `platform.system-settings.general-settings` | `/settings` | page | view, edit | ⬜ |
| CAP-34 | 11 | `platform.system-settings.infrastructure-settings` | `/settings/infrastructure` | page | view, edit | ⬜ |
| CAP-35 | 11 | `platform.system-settings.localization-settings` | `/settings/localization` | page | view, edit | ⬜ |
| CAP-36 | 11 | `platform.system-settings.maintenance-settings` | `/settings/maintenance` | page | view, toggle | ⬜ |
| CAP-37 | 12 | `platform.developer-tools.cache-management` | `/developer/cache` | page | view, clear | ⬜ |
| CAP-38 | 12 | `platform.developer-tools.developer-dashboard` | `/developer` | page | view | ⬜ |
| CAP-39 | 12 | `platform.developer-tools.log-viewer` | `/developer/logs` | page | view, download | ⬜ |
| CAP-40 | 12 | `platform.developer-tools.queue-management` | `/developer/queues` | page | view, manage | ⬜ |
| CAP-41 | 13 | `platform.audit-logs.audit-log-list` | `/audit-logs` | page | view, export | ⬜ |
| CAP-42 | 14 | `platform.access-logs.access-log-list` | `/access-logs` | page | view, export | ⬜ |
| CAP-43 | 14 | `platform.access-logs.pii-access` | `/access-logs/pii` | page | view, export | ⬜ |
| CAP-44 | 14 | `platform.platform-analytics.analytics-dashboard` | `/analytics` | page | view | ⬜ |
| CAP-45 | 14 | `platform.platform-analytics.revenue-reports` | `/analytics/revenue` | page | view, export | ⬜ |
| CAP-46 | 14 | `platform.platform-analytics.tenant-analytics` | `/analytics/tenants` | page | view | ⬜ |
| CAP-47 | 15 | `platform.seo-management.analytics-integrations` | `/seo/analytics` | page | view, configure | ⬜ |
| CAP-48 | 15 | `platform.seo-management.page-seo` | `/seo/pages` | page | view, edit | ⬜ |
| CAP-49 | 15 | `platform.seo-management.seo-settings` | `/seo/settings` | page | view, edit | ⬜ |
| CAP-50 | 15 | `platform.seo-management.sitemap-management` | `/seo/sitemap` | page | view, generate | ⬜ |
| CAP-51 | 16 | `platform.lead-management.lead-analytics` | `/leads/analytics` | page | view, export | ⬜ |
| CAP-52 | 16 | `platform.lead-management.lead-list` | `/leads` | page | view, create, edit, delete, assign, convert | ⬜ |
| CAP-53 | 16 | `platform.lead-management.lead-pipeline` | `/leads/pipeline` | page | view, manage | ⬜ |
| CAP-54 | 17 | `platform.newsletter-management.newsletter-settings` | `/newsletter/settings` | page | view, edit | ⬜ |
| CAP-55 | 17 | `platform.newsletter-management.subscriber-list` | `/newsletter/subscribers` | page | view, create, delete, import, export | ⬜ |
| CAP-56 | 18 | `platform.affiliate-program.affiliate-analytics` | `/affiliates/analytics` | page | view, export | ⬜ |
| CAP-57 | 18 | `platform.affiliate-program.affiliate-list` | `/affiliates` | page | view, create, edit, delete, approve, suspend | ⬜ |
| CAP-58 | 18 | `platform.affiliate-program.affiliate-payouts` | `/affiliates/payouts` | page | view, create, process | ⬜ |
| CAP-59 | 18 | `platform.affiliate-program.affiliate-referrals` | `/affiliates/referrals` | page | view, approve-commission | ⬜ |
| CAP-60 | 18 | `platform.affiliate-program.affiliate-settings` | `/affiliates/settings` | page | view, edit | ⬜ |
| CAP-61 | 19 | `platform.social-authentication.social-accounts` | `/social-auth/accounts` | page | view, unlink | ⬜ |
| CAP-62 | 19 | `platform.social-authentication.social-providers` | `/social-auth/providers` | page | view, configure | ⬜ |
| CAP-63 | 20 | `platform.tenant-operations.bulk-actions` | `/tenant-operations/bulk` | page | bulk-email, bulk-suspend, bulk-plan-change | ⬜ |
| CAP-64 | 20 | `platform.tenant-operations.tenant-archive` | `/tenant-operations/archive` | page | archive, restore | ⬜ |
| CAP-65 | 20 | `platform.tenant-operations.tenant-clone` | `/tenant-operations/clone` | page | view, clone | ⬜ |
| CAP-66 | 20 | `platform.tenant-operations.tenant-export` | `/tenant-operations/export` | page | view, request, download | ⬜ |
| CAP-67 | 20 | `platform.tenant-operations.tenant-freeze` | `/tenant-operations/freeze` | page | freeze, unfreeze | ⬜ |
| CAP-68 | 20 | `platform.tenant-operations.tenant-import` | `/tenant-operations/import` | page | view, upload, process | ⬜ |
| CAP-69 | 20 | `platform.tenant-operations.tenant-migration` | `/tenant-operations/migration` | page | view, plan, execute, rollback | ⬜ |
| CAP-70 | 21 | `platform.backup-restore.backup-dashboard` | `/backup-restore` | page | view | ⬜ |
| CAP-71 | 21 | `platform.backup-restore.backup-schedules` | `/backup-restore/schedules` | page | view, create, update, delete | ⬜ |
| CAP-72 | 21 | `platform.backup-restore.backup-storage` | `/backup-restore/storage` | page | view, configure | ⬜ |
| CAP-73 | 21 | `platform.backup-restore.manual-backups` | `/backup-restore/manual` | page | view, create | ⬜ |
| CAP-74 | 21 | `platform.backup-restore.restore` | `/backup-restore/restore` | page | view, restore, pitr | ⬜ |
| CAP-75 | 21 | `platform.backup-restore.retention-policies` | `/backup-restore/retention` | page | view, manage | ⬜ |
| CAP-76 | 22 | `platform.coupons-promotions.campaigns` | `/coupons/campaigns` | page | view, create, launch, end | ⬜ |
| CAP-77 | 22 | `platform.coupons-promotions.coupons` | `/coupons` | page | view, create, update, delete, archive, bulk-generate | ⬜ |
| CAP-78 | 22 | `platform.coupons-promotions.redemptions` | `/coupons/redemptions` | page | view, export | ⬜ |
| CAP-79 | 23 | `platform.addons-metered.addons` | `/addons-metered/addons` | page | view, create, update, archive | ⬜ |
| CAP-80 | 23 | `platform.addons-metered.metered-events` | `/addons-metered/events` | page | view, export | ⬜ |
| CAP-81 | 23 | `platform.addons-metered.metered-meters` | `/addons-metered/meters` | page | view, create, configure | ⬜ |
| CAP-82 | 23 | `platform.addons-metered.pay-as-you-go` | `/addons-metered/payg` | page | view, configure | ⬜ |
| CAP-83 | 24 | `platform.refunds-credits.credit-notes` | `/refunds-credits/credit-notes` | page | view, create, apply | ⬜ |
| CAP-84 | 24 | `platform.refunds-credits.refunds` | `/refunds-credits/refunds` | page | view, create, approve, process | ⬜ |
| CAP-85 | 25 | `platform.dunning.dunning-dashboard` | `/dunning` | page | view | ⬜ |
| CAP-86 | 25 | `platform.dunning.dunning-rules` | `/dunning/rules` | page | view, manage | ⬜ |
| CAP-87 | 25 | `platform.dunning.failed-payments` | `/dunning/failed-payments` | page | view, retry, mark-uncollectible | ⬜ |
| CAP-88 | 25 | `platform.dunning.recovery-emails` | `/dunning/templates` | page | view, manage | ⬜ |
| CAP-89 | 26 | `platform.tax-engine.tax-id-validation` | `/tax-engine/validation` | page | validate | ⬜ |
| CAP-90 | 26 | `platform.tax-engine.tax-providers` | `/tax-engine/providers` | page | view, configure | ⬜ |
| CAP-91 | 26 | `platform.tax-engine.tax-rates` | `/tax-engine/rates` | page | view, manage | ⬜ |
| CAP-92 | 26 | `platform.tax-engine.tax-reports` | `/tax-engine/reports` | page | view, generate, export | ⬜ |
| CAP-93 | 26 | `platform.tax-engine.w9-1099` | `/tax-engine/w9-1099` | page | view, generate | ⬜ |
| CAP-94 | 27 | `platform.multi-currency.currencies` | `/multi-currency/currencies` | page | view, manage | ⬜ |
| CAP-95 | 27 | `platform.multi-currency.exchange-rates` | `/multi-currency/rates` | page | view, sync, manual | ⬜ |
| CAP-96 | 27 | `platform.multi-currency.regional-pricing` | `/multi-currency/regional` | page | view, manage | ⬜ |
| CAP-97 | 28 | `platform.reseller-partners.partner-commissions` | `/partners/commissions` | page | view, manage, payout | ⬜ |
| CAP-98 | 28 | `platform.reseller-partners.partner-portal` | `/partners/portal` | page | configure | ⬜ |
| CAP-99 | 28 | `platform.reseller-partners.partner-tenants` | `/partners/tenants` | page | view, reassign | ⬜ |
| CAP-100 | 28 | `platform.reseller-partners.partners` | `/partners` | page | view, create, update, approve, suspend | ⬜ |
| CAP-101 | 29 | `platform.white-label.custom-css` | `/white-label/custom-css` | page | view, edit | ⬜ |
| CAP-102 | 29 | `platform.white-label.custom-domains` | `/white-label/domains` | page | view, add, verify, remove | ⬜ |
| CAP-103 | 29 | `platform.white-label.ssl-provisioning` | `/white-label/ssl` | page | view, provision, renew, upload | ⬜ |
| CAP-104 | 29 | `platform.white-label.tenant-branding` | `/white-label/branding` | page | view, manage | ⬜ |
| CAP-105 | 29 | `platform.white-label.tenant-email-branding` | `/white-label/email` | page | view, configure, verify | ⬜ |
| CAP-106 | 30 | `platform.feature-flags.experiments` | `/feature-flags/experiments` | page | view, start, stop | ⬜ |
| CAP-107 | 30 | `platform.feature-flags.flags` | `/feature-flags` | page | view, create, update, archive, toggle | ⬜ |
| CAP-108 | 30 | `platform.feature-flags.rollouts` | `/feature-flags/rollouts` | page | view, configure | ⬜ |
| CAP-109 | 30 | `platform.feature-flags.tenant-flags` | `/feature-flags/tenant-overrides` | page | view, manage | ⬜ |
| CAP-110 | 30 | `platform.white-label.custom-css` | `/white-label/css` | page | view, edit | ⬜ |
| CAP-111 | 30 | `platform.white-label.custom-domains` | `/white-label/domains` | page | view, add, verify, remove | ⬜ |
| CAP-112 | 30 | `platform.white-label.ssl-provisioning` | `/white-label/ssl` | page | view, provision, renew | ⬜ |
| CAP-113 | 30 | `platform.white-label.tenant-branding` | `/white-label/branding` | page | view, manage | ⬜ |
| CAP-114 | 30 | `platform.white-label.tenant-email-branding` | `/white-label/email-branding` | page | view, configure, verify | ⬜ |
| CAP-115 | 31 | `platform.backup-restore.backup-dashboard` | `/backup` | page | view | ⬜ |
| CAP-116 | 31 | `platform.backup-restore.backup-schedules` | `/backup/schedules` | page | view, create, update, delete | ⬜ |
| CAP-117 | 31 | `platform.backup-restore.backup-storage` | `/backup/storage` | page | view, configure | ⬜ |
| CAP-118 | 31 | `platform.backup-restore.manual-backups` | `/backup/manual` | page | view, create | ⬜ |
| CAP-119 | 31 | `platform.backup-restore.restore` | `/backup/restore` | page | view, restore, pitr | ⬜ |
| CAP-120 | 31 | `platform.backup-restore.retention-policies` | `/backup/retention` | page | view, manage | ⬜ |
| CAP-121 | 31 | `platform.tenant-communications.broadcasts` | `/communications/broadcasts` | page | view, create, publish, dismiss-all | ⬜ |
| CAP-122 | 31 | `platform.tenant-communications.email-blasts` | `/communications/email` | page | view, create, send | ⬜ |
| CAP-123 | 31 | `platform.tenant-communications.maintenance-windows` | `/communications/maintenance` | page | view, schedule, cancel | ⬜ |
| CAP-124 | 31 | `platform.tenant-communications.targeted-messages` | `/communications/targeted` | page | view, create | ⬜ |
| CAP-125 | 32 | `platform.status-incidents.incidents` | `/status/incidents` | page | view, create, update, resolve | ⬜ |
| CAP-126 | 32 | `platform.status-incidents.incidents` | `/status/incidents` | page | view, create, update, resolve | ⬜ |
| CAP-127 | 32 | `platform.status-incidents.postmortems` | `/status/postmortems` | page | view, create, publish | ⬜ |
| CAP-128 | 32 | `platform.status-incidents.postmortems` | `/status/postmortems` | page | view, create, publish | ⬜ |
| CAP-129 | 32 | `platform.status-incidents.service-components` | `/status/components` | page | view, manage, set-status | ⬜ |
| CAP-130 | 32 | `platform.status-incidents.service-components` | `/status/components` | page | view, manage, set-status | ⬜ |
| CAP-131 | 32 | `platform.status-incidents.sla-reporting` | `/status/sla` | page | view, export | ⬜ |
| CAP-132 | 32 | `platform.status-incidents.sla-reporting` | `/status/sla` | page | view, export | ⬜ |
| CAP-133 | 32 | `platform.status-incidents.status-page` | `/status` | page | view, configure | ⬜ |
| CAP-134 | 32 | `platform.status-incidents.status-page` | `/status` | page | view, configure | ⬜ |
| CAP-135 | 32 | `platform.status-incidents.uptime-monitoring` | `/status/uptime` | page | view, configure | ⬜ |
| CAP-136 | 32 | `platform.status-incidents.uptime-monitoring` | `/status/uptime` | page | view, configure | ⬜ |
| CAP-137 | 33 | `platform.customer-success.churn-risk` | `/customer-success/churn` | page | view, run | ⬜ |
| CAP-138 | 33 | `platform.customer-success.csm-assignment` | `/customer-success/csm` | page | view, assign | ⬜ |
| CAP-139 | 33 | `platform.customer-success.health-score` | `/customer-success/health` | page | view, configure | ⬜ |
| CAP-140 | 33 | `platform.customer-success.nps-csat` | `/customer-success/nps` | page | view, send, export | ⬜ |
| CAP-141 | 33 | `platform.customer-success.onboarding-progress` | `/customer-success/onboarding-progress` | page | view | ⬜ |
| CAP-142 | 33 | `platform.customer-success.success-playbooks` | `/customer-success/playbooks` | page | view, manage, execute | ⬜ |
| CAP-143 | 33 | `platform.platform-security.impersonation` | `/security/impersonation` | page | view, start, end, audit | ⬜ |
| CAP-144 | 33 | `platform.platform-security.ip-allowlist` | `/security/ip-allowlist` | page | view, manage | ⬜ |
| CAP-145 | 33 | `platform.platform-security.landlord-roles` | `/security/roles` | page | view, create, update, delete, assign | ⬜ |
| CAP-146 | 33 | `platform.platform-security.staff-mfa` | `/security/mfa` | page | view, enforce, reset | ⬜ |
| CAP-147 | 33 | `platform.platform-security.staff-sessions` | `/security/sessions` | page | view, force-logout | ⬜ |
| CAP-148 | 33 | `platform.platform-security.staff-sso` | `/security/sso` | page | view, configure | ⬜ |
| CAP-149 | 34 | `platform.help-center.in-app-help` | `/help-center/in-app` | page | view, create, publish | ⬜ |
| CAP-150 | 34 | `platform.help-center.kb-articles` | `/help-center/articles` | page | view, create, update, delete, publish | ⬜ |
| CAP-151 | 34 | `platform.help-center.live-chat` | `/help-center/chat` | page | view, reply, configure | ⬜ |
| CAP-152 | 34 | `platform.help-center.tenant-tickets` | `/help-center/tickets` | page | view, reply, assign, escalate, close | ⬜ |
| CAP-153 | 34 | `platform.help-center.video-tutorials` | `/help-center/videos` | page | view, manage | ⬜ |
| CAP-154 | 34 | `platform.security-center.pentest-reports` | `/security-center/pentests` | page | view, upload, share | ⬜ |
| CAP-155 | 34 | `platform.security-center.security-dashboard` | `/security-center` | page | view | ⬜ |
| CAP-156 | 34 | `platform.security-center.security-incidents` | `/security-center/incidents` | page | view, create, notify | ⬜ |
| CAP-157 | 35 | `platform.compliance-legal.certifications` | `/compliance-legal/certifications` | page | view, upload | ⬜ |
| CAP-158 | 35 | `platform.compliance-legal.data-residency` | `/compliance-legal/data-residency` | page | view, configure | ⬜ |
| CAP-159 | 35 | `platform.compliance-legal.dpa` | `/compliance-legal/dpa` | page | view, manage, sign | ⬜ |
| CAP-160 | 35 | `platform.compliance-legal.platform-dsar` | `/compliance-legal/dsar` | page | view, fulfill | ⬜ |
| CAP-161 | 35 | `platform.compliance-legal.privacy-versions` | `/compliance-legal/privacy` | page | view, create | ⬜ |
| CAP-162 | 35 | `platform.compliance-legal.subprocessors` | `/compliance-legal/subprocessors` | page | view, manage, notify | ⬜ |
| CAP-163 | 35 | `platform.compliance-legal.tos-versions` | `/compliance-legal/tos` | page | view, create, require-acceptance | ⬜ |
| CAP-164 | 36 | `platform.multi-region.cdn-config` | `/multi-region/cdn` | page | view, configure, purge | ⬜ |
| CAP-165 | 36 | `platform.multi-region.regions` | `/multi-region/regions` | page | view, manage, enable, disable | ⬜ |
| CAP-166 | 36 | `platform.multi-region.tenant-region-assignment` | `/multi-region/tenant-assignment` | page | view, reassign | ⬜ |
| CAP-167 | 37 | `platform.security-center.bug-bounty` | `/security-center/bug-bounty` | page | view, manage | ⬜ |
| CAP-168 | 37 | `platform.security-center.pentest-reports` | `/security-center/pentest` | page | view, upload, share | ⬜ |
| CAP-169 | 37 | `platform.security-center.security-dashboard` | `/security-center` | page | view | ⬜ |
| CAP-170 | 37 | `platform.security-center.security-incidents` | `/security-center/incidents` | page | view, create, notify | ⬜ |
| CAP-171 | 37 | `platform.security-center.vulnerability-disclosures` | `/security-center/vulnerabilities` | page | view, manage | ⬜ |
| CAP-172 | 38 | `platform.email-deliverability.bounce-complaints` | `/email-deliverability/bounces` | page | view, export | ⬜ |
| CAP-173 | 38 | `platform.email-deliverability.dns-setup` | `/email-deliverability/dns` | page | view, configure, verify | ⬜ |
| CAP-174 | 38 | `platform.email-deliverability.sender-reputation` | `/email-deliverability/reputation` | page | view | ⬜ |
| CAP-175 | 38 | `platform.email-deliverability.suppression-list` | `/email-deliverability/suppression` | page | view, remove | ⬜ |
| CAP-176 | 39 | `platform.api-gateway.api-quotas` | `/api-gateway/quotas` | page | view, configure | ⬜ |
| CAP-177 | 39 | `platform.api-gateway.api-usage-analytics` | `/api-gateway/usage` | page | view, export | ⬜ |
| CAP-178 | 39 | `platform.api-gateway.gateway-routing` | `/api-gateway/routing` | page | view, configure | ⬜ |
| CAP-179 | 39 | `platform.api-gateway.rate-limits` | `/api-gateway/rate-limits` | page | view, manage | ⬜ |
| CAP-180 | 40 | `platform.platform-security.impersonation` | `/platform-security/impersonation` | page | view, start, end, audit | ⬜ |
| CAP-181 | 40 | `platform.platform-security.ip-allowlist` | `/platform-security/ip-allowlist` | page | view, manage | ⬜ |
| CAP-182 | 40 | `platform.platform-security.landlord-roles` | `/platform-security/roles` | page | view, create, update, delete, assign | ⬜ |
| CAP-183 | 40 | `platform.platform-security.staff-mfa` | `/platform-security/mfa` | page | view, enforce, reset | ⬜ |
| CAP-184 | 40 | `platform.platform-security.staff-sessions` | `/platform-security/sessions` | page | view, force-logout | ⬜ |
| CAP-185 | 40 | `platform.platform-security.staff-sso` | `/platform-security/sso` | page | view, configure | ⬜ |
| CAP-186 | 40 | `platform.resource-provisioning.auto-scaling` | `/provisioning/auto-scaling` | page | view, manage | ⬜ |
| CAP-187 | 40 | `platform.resource-provisioning.compute-resources` | `/provisioning/compute` | page | view, manage | ⬜ |
| CAP-188 | 40 | `platform.resource-provisioning.db-pools` | `/provisioning/db-pools` | page | view, manage, rebalance | ⬜ |
| CAP-189 | 40 | `platform.resource-provisioning.storage-backends` | `/provisioning/storage` | page | view, configure | ⬜ |
| CAP-190 | 41 | `platform.job-scheduler.cron-monitoring` | `/job-scheduler/cron` | page | view | ⬜ |
| CAP-191 | 41 | `platform.job-scheduler.scheduled-tasks` | `/job-scheduler/scheduled` | page | view, create, update, pause, run-now | ⬜ |
| CAP-192 | 41 | `platform.job-scheduler.task-history` | `/job-scheduler/history` | page | view, retry | ⬜ |
| CAP-193 | 41 | `platform.secrets-management.kms` | `/secrets/kms` | page | view, rotate, configure | ⬜ |
| CAP-194 | 41 | `platform.secrets-management.secret-audit` | `/secrets/audit` | page | view | ⬜ |
| CAP-195 | 41 | `platform.secrets-management.secrets-vault` | `/secrets/vault` | page | view, create, rotate, revoke | ⬜ |
| CAP-196 | 41 | `platform.secrets-management.tenant-deks` | `/secrets/tenant-deks` | page | view, rotate | ⬜ |
| CAP-197 | 42 | `platform.outbound-webhooks.delivery-logs` | `/outbound-webhooks/logs` | page | view, replay | ⬜ |
| CAP-198 | 42 | `platform.outbound-webhooks.event-catalog` | `/outbound-webhooks/events` | page | view | ⬜ |
| CAP-199 | 42 | `platform.outbound-webhooks.webhook-endpoints` | `/outbound-webhooks/endpoints` | page | view, create, update, delete, test | ⬜ |
| CAP-200 | 42 | `platform.outbound-webhooks.webhook-signing` | `/outbound-webhooks/signing` | page | view, rotate | ⬜ |
| CAP-201 | 43 | `platform.invoicing.invoice-branding` | `/invoicing/branding` | page | manage | ⬜ |
| CAP-202 | 43 | `platform.invoicing.invoice-numbering` | `/invoicing/numbering` | page | manage | ⬜ |
| CAP-203 | 43 | `platform.invoicing.invoice-templates` | `/invoicing/templates` | page | view, manage | ⬜ |
| CAP-204 | 43 | `platform.invoicing.invoices` | `/invoicing/invoices` | page | view, create, update, send, void, mark-paid, download-pdf | ⬜ |
| CAP-205 | 44 | `platform.payment-methods.ach-sepa` | `/payment-methods/bank-debit` | page | view, authorize | ⬜ |
| CAP-206 | 44 | `platform.payment-methods.card-vault` | `/payment-methods/cards` | page | view, tokenize | ⬜ |
| CAP-207 | 44 | `platform.payment-methods.pm-list` | `/payment-methods` | page | view, add, update, remove, set-default | ⬜ |
| CAP-208 | 44 | `platform.payment-methods.sca-3ds` | `/payment-methods/sca` | page | view, configure | ⬜ |
| CAP-209 | 45 | `platform.subscription-lifecycle.cancellations` | `/subscription-lifecycle/cancellations` | page | view, configure | ⬜ |
| CAP-210 | 45 | `platform.subscription-lifecycle.pause-resume` | `/subscription-lifecycle/pause-resume` | page | pause, resume | ⬜ |
| CAP-211 | 45 | `platform.subscription-lifecycle.plan-changes` | `/subscription-lifecycle/plan-changes` | page | view, execute | ⬜ |
| CAP-212 | 45 | `platform.subscription-lifecycle.proration` | `/subscription-lifecycle/proration` | page | preview, configure | ⬜ |
| CAP-213 | 45 | `platform.subscription-lifecycle.trials` | `/subscription-lifecycle/trials` | page | view, extend, convert | ⬜ |
| CAP-214 | 46 | `platform.observability.alerts` | `/observability/alerts` | page | view, configure | ⬜ |
| CAP-215 | 46 | `platform.observability.apm` | `/observability/apm` | page | view | ⬜ |
| CAP-216 | 46 | `platform.observability.logs-aggregation` | `/observability/logs` | page | view, search, export | ⬜ |
| CAP-217 | 46 | `platform.observability.metrics` | `/observability/metrics` | page | view, export | ⬜ |
| CAP-218 | 46 | `platform.observability.traces` | `/observability/traces` | page | view, search | ⬜ |
| CAP-219 | 47 | `platform.disaster-recovery.dr-drills` | `/disaster-recovery/drills` | page | view, schedule, execute | ⬜ |
| CAP-220 | 47 | `platform.disaster-recovery.dr-runbooks` | `/disaster-recovery/runbooks` | page | view, create, execute | ⬜ |
| CAP-221 | 47 | `platform.disaster-recovery.failover` | `/disaster-recovery/failover` | page | view, initiate, failback | ⬜ |
| CAP-222 | 47 | `platform.disaster-recovery.rto-rpo` | `/disaster-recovery/rto-rpo` | page | view, configure | ⬜ |
| CAP-223 | 48 | `platform.notifications.digest` | `/notification-center/digest` | page | configure | ⬜ |
| CAP-224 | 48 | `platform.notifications.escalation-routing` | `/notification-center/escalation` | page | view, manage | ⬜ |
| CAP-225 | 48 | `platform.notifications.staff-preferences` | `/notification-center/preferences` | page | view, update | ⬜ |
| CAP-226 | 49 | `platform.enterprise-scim.scim-endpoints` | `/enterprise-scim/endpoints` | page | view, configure, rotate-token | ⬜ |
| CAP-227 | 49 | `platform.enterprise-scim.scim-logs` | `/enterprise-scim/logs` | page | view | ⬜ |
| CAP-228 | 50 | `platform.contract-management.contract-versions` | `/contracts/versions` | page | view | ⬜ |
| CAP-229 | 50 | `platform.contract-management.msa` | `/contracts/msa` | page | view, create, sign, amend | ⬜ |
| CAP-230 | 50 | `platform.contract-management.order-forms` | `/contracts/order-forms` | page | view, create, sign | ⬜ |
| CAP-231 | 50 | `platform.contract-management.rate-cards` | `/contracts/rate-cards` | page | view, manage | ⬜ |
| CAP-232 | 51 | `platform.app-marketplace.app-catalog` | `/app-marketplace` | page | view, install, uninstall | ⬜ |
| CAP-233 | 51 | `platform.app-marketplace.app-revenue` | `/app-marketplace/revenue` | page | view, configure | ⬜ |
| CAP-234 | 51 | `platform.app-marketplace.app-reviews` | `/app-marketplace/reviews` | page | view, moderate | ⬜ |
| CAP-235 | 51 | `platform.app-marketplace.developer-apps` | `/app-marketplace/developers` | page | view, approve, reject | ⬜ |
| CAP-236 | 52 | `platform.license-management.activations` | `/licenses/activations` | page | view, deactivate | ⬜ |
| CAP-237 | 52 | `platform.license-management.license-keys` | `/licenses/keys` | page | view, generate, revoke, extend | ⬜ |
| CAP-238 | 52 | `platform.license-management.license-settings` | `/licenses/settings` | page | view, configure | ⬜ |
| CAP-239 | 52 | `platform.release-management.changelog` | `/releases/changelog` | page | view, create, publish | ⬜ |
| CAP-240 | 52 | `platform.release-management.deployment-tracking` | `/releases/deployments` | page | view | ⬜ |
| CAP-241 | 52 | `platform.release-management.releases` | `/releases` | page | view, create, deploy, rollback | ⬜ |
| CAP-242 | 53 | `platform.access-logs.access-log-list` | `/access-logs` | page | view, export | ⬜ |
| CAP-243 | 53 | `platform.access-logs.pii-access` | `/access-logs/pii` | page | view, export | ⬜ |
| CAP-244 | 53 | `platform.migration-imports.connectors` | `/migrations/connectors` | page | view, configure | ⬜ |
| CAP-245 | 53 | `platform.migration-imports.field-mapping` | `/migrations/mapping` | page | view, manage | ⬜ |
| CAP-246 | 53 | `platform.migration-imports.import-jobs` | `/migrations/imports` | page | view, create, monitor, rollback | ⬜ |
| CAP-247 | 54 | `platform.product-analytics.adoption-metrics` | `/product-analytics/adoption` | page | view | ⬜ |
| CAP-248 | 54 | `platform.product-analytics.cohort-analysis` | `/product-analytics/cohorts` | page | view, export | ⬜ |
| CAP-249 | 54 | `platform.product-analytics.feature-usage` | `/product-analytics/features` | page | view, export | ⬜ |
| CAP-250 | 54 | `platform.product-analytics.funnel-analysis` | `/product-analytics/funnels` | page | view, manage | ⬜ |
