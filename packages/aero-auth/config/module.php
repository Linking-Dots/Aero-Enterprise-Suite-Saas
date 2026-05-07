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

    'features' => [
        // Tenant-side
        'tenant_login' => true,  // standard email+password, subdomain-scoped
        'tenant_registration' => true,  // public registration (if platform enabled)
        'tenant_password_reset' => true,  // tokens stored in tenant DB
        'tenant_email_verification' => true,
        'tenant_remember_me' => true,
        'tenant_mfa_totp' => true,  // TOTP via authenticator app
        'tenant_mfa_sms' => true,
        'tenant_sso_saml' => true,  // SAML 2.0 IdP per tenant
        'tenant_sso_oidc' => true,  // OpenID Connect per tenant
        'tenant_social_login' => true,  // Google, Microsoft, GitHub (per tenant config)
        'tenant_api_token_auth' => true,  // Sanctum tokens in tenant DB

        // Platform-side (landlord)
        'landlord_login' => true,  // separate guard, central DB
        'landlord_mfa' => true,
        'landlord_api_token' => true,

        // Shared infrastructure
        'impersonation' => true,  // platform admins impersonate tenant users
        'session_isolation' => true,  // cookie scoped to tenant subdomain
        'rate_limiting' => true,  // per-IP + per-tenant brute-force protection
        'device_trust' => true,  // known device cookies
        'audit_auth_events' => true,  // login/logout/failed → audit log
    ],

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
];
