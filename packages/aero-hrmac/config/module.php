<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | HRMAC Package — Infrastructure Module Config
    |--------------------------------------------------------------------------
    | Scope: BOTH platform + tenant (infrastructure layer, not a UI module)
    |
    | aero-hrmac is the shared access-control foundation used by both the
    | tenant app (aero-core) and the platform admin (aero-platform). Roles,
    | permission assignment, and module-access configuration are declared
    | here ONCE so both contexts share a single HRMAC-discoverable source
    | of truth instead of duplicating the submodule in each consumer's
    | config/module.php (Phase 1, Task 1 of the shared access-control
    | consolidation plan).
    */

    'code' => 'hrmac',
    'schema_version' => '2.0',
    'scope' => 'infrastructure',   // not a marketplace module
    'name' => 'Access Control',
    'description' => 'Shared access control: roles, permissions, and module-access — for both tenant and platform contexts.',
    'icon' => 'ShieldCheckIcon',
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

    /*
    |--------------------------------------------------------------------------
    | Roles & Access Submodule
    |--------------------------------------------------------------------------
    |
    | Ported verbatim from packages/aero-core/config/module.php's
    | 'roles_permissions' submodule (component/action shapes preserved).
    | The duplicate declarations in aero-core and aero-platform are removed
    | in a later task of the consolidation plan.
    */
    'submodules' => [
        [
            'code' => 'roles_permissions',
            'name' => 'Roles & Access',
            'description' => 'Role-based access control and module permissions',
            'icon' => 'ShieldCheckIcon',
            'route' => '/roles',
            'priority' => 5,

            'components' => [
                [
                    'code' => 'roles',
                    'name' => 'Roles',
                    'type' => 'page',
                    'route' => '/roles',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Roles'],
                        ['code' => 'create', 'name' => 'Create Role'],
                        ['code' => 'edit', 'name' => 'Edit Role'],
                        ['code' => 'delete', 'name' => 'Delete Role'],
                        ['code' => 'assign', 'name' => 'Assign Role to Users'],
                        ['code' => 'permissions', 'name' => 'Manage Permissions'],
                    ],
                ],
                [
                    'code' => 'module_access',
                    'name' => 'Module Access',
                    'type' => 'page',
                    'route' => '/modules',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Modules'],
                        ['code' => 'configure', 'name' => 'Configure Module Access'],
                        ['code' => 'toggle', 'name' => 'Enable/Disable Module'],
                    ],
                ],
            ],
        ],
    ],
];
