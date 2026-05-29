<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the models used by HRMAC. You can override these with your own
    | implementations if needed.
    |
    */

    'models' => [
        'role' => \Aero\HRMAC\Models\Role::class,
        'user' => \Aero\Core\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin Roles (Plan 04 Task 2 — guard-scoped)
    |--------------------------------------------------------------------------
    |
    | Users with these roles bypass all module access checks.
    | Phase 1 audit flagged the previous flat string array as brittle:
    | a tenant role literally named "Super Administrator" could in principle
    | satisfy the same config list as a landlord role. Guard scoping makes
    | the bypass surface explicit per authentication context.
    |
    | Keys MUST match auth guards configured in config/auth.php.
    |
    */

    'super_admin_roles' => [
        // Landlord/Platform guard — central DB users only
        'landlord' => [
            'Platform Super Administrator',
            'platform-super-admin',
        ],
        // Tenant/web guard — per-tenant DB users only
        'web' => [
            'Tenant Super Administrator',
            'tenant_super_administrator',
            'Super Administrator',  // legacy — kept for backwards compat during rollout
            'super-admin',          // legacy
        ],
        // API guard (token auth, if applicable)
        'api' => [
            // none by default — API tokens should be scoped to specific permissions
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Caching configuration for role module access checks.
    | Role access is cached per-role, user access is cached per-user.
    |
    */

    'cache' => [
        // Enable or disable caching (recommended: true in production)
        'enabled' => env('HRMAC_CACHE_ENABLED', true),

        // Cache TTL in seconds (default: 1 hour)
        'ttl' => env('HRMAC_CACHE_TTL', 3600),

        // Cache key prefix
        'prefix' => 'hrmac',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enforcement Toggles (Plan 04 Task 3)
    |--------------------------------------------------------------------------
    |
    | enforce_is_active — when true (default), RoleModuleAccessService filters
    | Module / SubModule / Component / Action lookups by is_active=true.
    | A row with is_active=false denies access even to users who hold the
    | role grant. Set false ONLY for admin debugging or emergency unbypass.
    |
    | The actual is_active filtering lives in RoleModuleAccessService — see
    | userCanAccessModule(), userCanAccessSubModule(), userCanAccessAction().
    |
    */

    'enforce_is_active' => env('HRMAC_ENFORCE_IS_ACTIVE', true),

    /*
    |--------------------------------------------------------------------------
    | Default Landing Routes
    |--------------------------------------------------------------------------
    |
    | These routes are used for smart landing redirects when a user logs in
    | or accesses the root URL. The first accessible route is used.
    |
    | The 'module' and 'sub_module' define what access is required.
    | The 'route' defines where to redirect if access is granted.
    |
    */

    'landing_routes' => [
        // Primary: Dashboard (Core module, Dashboard sub-module)
        [
            'module' => 'core',
            'sub_module' => 'dashboard',
            'route' => 'tenant.dashboard',
            'priority' => 1,
        ],

        // Fallback to HRM Employee Dashboard (if HRM access)
        [
            'module' => 'hrm',
            'sub_module' => null, // Any HRM access
            'route' => 'tenant.hrm.index',
            'priority' => 2,
        ],

        // Generic module landing pages
        // These are checked dynamically based on sub-modules table
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Inheritance
    |--------------------------------------------------------------------------
    |
    | Define how access cascades down the hierarchy.
    |
    | When enabled:
    | - Module access grants access to all sub-modules (unless explicitly denied)
    | - Sub-module access grants access to all components within it
    | - Component access grants access to all actions within it
    |
    */

    'inheritance' => [
        // If a role has module access, does it automatically get sub-module access?
        'module_grants_sub_modules' => true,

        // If a role has sub-module access, does it automatically get component access?
        'sub_module_grants_components' => true,

        // If a role has component access, does it automatically get action access?
        'component_grants_actions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure access denial logging for security auditing.
    |
    */

    'logging' => [
        // Log when access is denied
        'log_denials' => env('HRMAC_LOG_DENIALS', true),

        // Log channel to use (null = default)
        'channel' => env('HRMAC_LOG_CHANNEL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | Define middleware aliases registered by the package.
    |
    */

    'middleware' => [
        'role.access' => \Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess::class,
        'smart.landing' => \Aero\HRMAC\Http\Middleware\SmartLandingRedirect::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configure paths for module discovery. The ModuleDiscoveryService
    | scans these paths for config/module.php files.
    |
    */

    'discovery' => [
        // Paths to scan for module.php config files
        // {path} is relative to application base path
        'paths' => [
            'vendor/aero/*/config/module.php',
            'modules/*/config/module.php',
        ],

        // Whether to validate module configs during discovery
        'validate' => true,

        // Required fields in module.php config
        'required_fields' => ['module_key', 'label', 'scope'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | The database table names used by HRMAC models.
    |
    */

    'tables' => [
        'modules' => 'modules',
        'sub_modules' => 'sub_modules',
        'components' => 'module_components',
        'actions' => 'module_component_actions',
        'role_module_access' => 'role_module_access',
    ],
];
