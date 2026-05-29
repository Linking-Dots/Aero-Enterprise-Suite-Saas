<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auth Package — Infrastructure Module Config
    |--------------------------------------------------------------------------
    | Scope: BOTH platform + tenant (infrastructure layer, not a UI module)
    |
    | aero-auth is NOT a user-facing module. It is the shared authentication
    | infrastructure used by aero-core (tenant auth) and aero-platform
    | (landlord auth). It does not appear in the module marketplace.
    |
    | Tenancy concern:
    |   - Guards are split: 'web' (tenant users) vs 'landlord' (platform admins)
    |   - Session drivers are tenant-isolated (subdomain cookie domain)
    |   - SSO/OAuth tokens stored in tenant DB; platform admin tokens in central DB
    |   - Impersonation tokens are platform-scope only, never persisted in tenant DB
    |   - Password reset tokens: tenant users → tenant DB, landlords → central DB
    */

    'code' => 'auth',
    'schema_version' => '2.0',
    'scope' => 'infrastructure',   // not a marketplace module
    'name' => 'Authentication Infrastructure',
    'description' => 'Shared auth layer: guards, providers, SSO adapters, MFA, impersonation, and session isolation for both tenant and platform contexts.',
    'icon' => 'LockClosedIcon',
    'route_prefix' => null,               // routes registered by core & platform
    'category' => 'infrastructure',
    'priority' => 0,
    'is_core' => true,
    'is_active' => true,
    'enabled' => true,
    'version' => '1.0.0',
    'min_plan' => null,
    'license_type' => 'platform',
    'dependencies' => [],
    'release_date' => '2024-01-01',
    'marketplace_visible' => false,       // never shown in module marketplace

    'guards' => [
        'tenant' => 'web',       // default Laravel web guard for tenant users
        'landlord' => 'landlord',  // custom guard for platform admins (central DB)
    ],

    'tenancy' => [
        'tenant_aware' => true,
        'uses_tenant_db' => true,   // tenant auth data in tenant_{id} DB
        'central_tables' => [       // platform admins only in central DB
            'landlord_users',
            'landlord_password_reset_tokens',
        ],
        'session_cookie_domain' => 'subdomain',  // e.g. acme.aerosuite.com
        'impersonation_scope' => 'platform',   // only landlord guard can impersonate
    ],

    /*
    |--------------------------------------------------------------------------
    | SSO & Identity Submodule (Plan 05 T6)
    |--------------------------------------------------------------------------
    |
    | Moved here from aero-core/config/module.php. The CONTROLLERS for these
    | components have always lived in aero-auth (SamlConfigController,
    | OidcConfigController, OAuthProviderController, PasskeyConfigController,
    | MagicLinkConfigController, ScimConfigController, MfaPolicyController,
    | SessionPolicyController, SocialLoginConfigController, VerificationConfigController,
    | LoginActivityController, AccountRecoveryController). The declarations
    | belong with the implementation — splitting them across packages caused
    | ownership confusion called out in Phase 1.
    |
    | HRMAC permission paths change: core.sso_identity.* → auth.sso_identity.*
    | Route middleware strings updated in routes/identity.php and routes/admin.php.
    */
    'submodules' => [
        [
            'code' => 'sso_identity',
            'name' => 'SSO & Identity',
            'description' => 'SAML, OIDC, OAuth, SCIM provisioning, social login, passkeys, magic links',
            'icon' => 'KeyIcon',
            'route' => '/identity',
            'priority' => 11,
            'components' => [
                [
                    'code' => 'sso_saml', 'name' => 'SAML 2.0', 'type' => 'page', 'route' => '/identity/saml',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SAML'],
                        ['code' => 'configure', 'name' => 'Configure SAML SP/IdP'],
                        ['code' => 'test', 'name' => 'Test SAML Login'],
                        ['code' => 'metadata', 'name' => 'Download Metadata'],
                    ],
                ],
                [
                    'code' => 'sso_oidc', 'name' => 'OIDC / OAuth 2.0', 'type' => 'page', 'route' => '/identity/oidc',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View OIDC'],
                        ['code' => 'configure', 'name' => 'Configure OIDC Provider'],
                        ['code' => 'test', 'name' => 'Test OIDC Login'],
                    ],
                ],
                [
                    'code' => 'oauth_provider', 'name' => 'OAuth Provider (Be an OAuth IdP)', 'type' => 'page', 'route' => '/identity/oauth-provider',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View OAuth Apps'],
                        ['code' => 'create', 'name' => 'Create OAuth App'],
                        ['code' => 'revoke', 'name' => 'Revoke OAuth App'],
                    ],
                ],
                [
                    'code' => 'scim_provisioning', 'name' => 'SCIM 2.0 Provisioning', 'type' => 'page', 'route' => '/identity/scim',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SCIM'],
                        ['code' => 'configure', 'name' => 'Configure SCIM Endpoint'],
                        ['code' => 'logs', 'name' => 'View SCIM Logs'],
                    ],
                ],
                [
                    'code' => 'social_login', 'name' => 'Social Login', 'type' => 'page', 'route' => '/identity/social',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Providers'],
                        ['code' => 'configure', 'name' => 'Configure Provider (Google/Microsoft/Apple/GitHub)'],
                    ],
                ],
                [
                    'code' => 'magic_link', 'name' => 'Magic Link Login', 'type' => 'page', 'route' => '/identity/magic-link',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Magic Link Settings'],
                        ['code' => 'configure', 'name' => 'Configure Magic Link'],
                    ],
                ],
                [
                    'code' => 'passkeys', 'name' => 'Passkeys / WebAuthn', 'type' => 'page', 'route' => '/identity/passkeys',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Passkeys'],
                        ['code' => 'register', 'name' => 'Register Passkey'],
                        ['code' => 'remove', 'name' => 'Remove Passkey'],
                    ],
                ],
                [
                    'code' => 'mfa_policies', 'name' => 'MFA Enforcement Policies', 'type' => 'page', 'route' => '/identity/mfa-policies',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View MFA Policies'],
                        ['code' => 'manage', 'name' => 'Manage MFA Policies'],
                    ],
                ],
                [
                    'code' => 'session_policies', 'name' => 'Session Policies', 'type' => 'page', 'route' => '/identity/session-policies',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Session Policies'],
                        ['code' => 'manage', 'name' => 'Manage Session Policies'],
                    ],
                ],
                [
                    'code' => 'login_activity', 'name' => 'Login Activity & Geo', 'type' => 'page', 'route' => '/identity/login-activity',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Login Activity'],
                        ['code' => 'export', 'name' => 'Export Login Activity'],
                    ],
                ],
                [
                    'code' => 'verification', 'name' => 'Email & Phone Verification', 'type' => 'page', 'route' => '/identity/verification',
                    'actions' => [
                        ['code' => 'configure', 'name' => 'Configure Verification'],
                        ['code' => 'send', 'name' => 'Send Verification Code'],
                    ],
                ],
                [
                    'code' => 'account_recovery', 'name' => 'Account Recovery', 'type' => 'page', 'route' => '/identity/account-recovery',
                    'actions' => [
                        ['code' => 'configure', 'name' => 'Configure Recovery'],
                    ],
                ],
            ],
        ],
    ],
];
