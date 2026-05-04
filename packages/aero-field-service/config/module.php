<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Field Service Management (FSM) Module
    |--------------------------------------------------------------------------
    | Scope: tenant
    |
    | Distinct from aero-eam: EAM owns the asset register and work order
    | lifecycle for internal maintenance. FSM owns customer-facing field
    | service: dispatching external technicians to customer sites,
    | service agreements, SLA tracking, and mobile field app.
    |
    | Cross-package integrations:
    |   - aero-eam       → internal assets that also receive field service
    |   - aero-crm       → customer/site records, service contracts
    |   - aero-hrm       → technician profiles, skills, certifications
    |   - aero-ims       → parts used on job, van stock
    |   - aero-finance   → customer invoicing for field jobs
    |   - aero-quality   → post-job quality inspection, job sign-off
    |   - aero-iot       → remote diagnostics triggering a field dispatch
    |
    | Tenancy: all jobs, technicians, customers, and SLAs are tenant-scoped.
    | Mobile app authenticates via tenant subdomain SSO token.
    */

    'code'         => 'field-service',
    'scope'        => 'tenant',
    'name'         => 'Field Service Management',
    'description'  => 'End-to-end FSM: work order dispatch, technician scheduling, mobile field app, service agreements, SLA tracking, and customer sign-off.',
    'icon'         => 'WrenchScrewdriverIcon',
    'route_prefix' => '/field-service',
    'category'     => 'operations',
    'priority'     => 25,
    'is_core'      => false,
    'is_active'    => true,
    'enabled'      => env('FIELD_SERVICE_MODULE_ENABLED', true),
    'version'      => '1.0.0',
    'min_plan'     => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core', 'crm'],
    'release_date' => '2024-01-01',

    'features' => [
        'work_order_management'      => true,  // create, assign, track field jobs
        'dispatch_board'             => true,  // drag-and-drop calendar scheduler
        'technician_management'      => true,  // profiles, skills, certifications, availability
        'route_optimization'         => true,  // GPS-based daily route planning
        'mobile_app'                 => true,  // PWA/native app for field technicians
        'offline_mode'               => true,  // offline job forms, sync on reconnect
        'service_agreements'         => true,  // SLA-backed customer contracts
        'sla_tracking'               => true,  // response, resolution time SLAs
        'parts_and_materials'        => true,  // field parts usage, van stock (→ aero-ims)
        'customer_portal'            => true,  // customer self-service: job status, history
        'digital_signature'          => true,  // job completion sign-off
        'photo_documentation'        => true,  // field photos attached to job
        'checklists_forms'           => true,  // configurable job checklists
        'invoicing'                  => true,  // trigger invoice generation (→ aero-finance)
        'timesheet_integration'      => true,  // technician hours → aero-hrm payroll
        'iot_triggered_dispatch'     => true,  // IoT alert → auto field dispatch (→ aero-iot)
        'reporting_analytics'        => true,  // FSM KPIs → aero-analytics
    ],

    'submodules' => [

        [
            'code' => 'dispatch', 'name' => 'Dispatch & Scheduling', 'icon' => 'CalendarDaysIcon',
            'route' => '/field-service/dispatch', 'priority' => 1,
            'components' => [
                ['code' => 'dispatch-board',      'name' => 'Dispatch Board',       'route' => '/field-service/dispatch/board'],
                ['code' => 'work-orders',         'name' => 'Work Orders',          'route' => '/field-service/dispatch/work-orders'],
                ['code' => 'route-optimization',  'name' => 'Route Optimization',   'route' => '/field-service/dispatch/routes'],
            ],
        ],

        [
            'code' => 'technicians', 'name' => 'Technicians', 'icon' => 'UserGroupIcon',
            'route' => '/field-service/technicians', 'priority' => 2,
            'components' => [
                ['code' => 'technician-list',    'name' => 'Technician List',     'route' => '/field-service/technicians/list'],
                ['code' => 'skills-certs',       'name' => 'Skills & Certs',      'route' => '/field-service/technicians/skills'],
                ['code' => 'availability',       'name' => 'Availability',        'route' => '/field-service/technicians/availability'],
            ],
        ],

        [
            'code' => 'agreements', 'name' => 'Service Agreements', 'icon' => 'DocumentTextIcon',
            'route' => '/field-service/agreements', 'priority' => 3,
            'components' => [
                ['code' => 'contracts',    'name' => 'Contracts',    'route' => '/field-service/agreements/contracts'],
                ['code' => 'sla-tracker', 'name' => 'SLA Tracker',  'route' => '/field-service/agreements/sla'],
            ],
        ],

        [
            'code' => 'customer-portal', 'name' => 'Customer Portal', 'icon' => 'GlobeAltIcon',
            'route' => '/field-service/portal', 'priority' => 4,
            'components' => [
                ['code' => 'job-status',    'name' => 'Job Status',      'route' => '/field-service/portal/jobs'],
                ['code' => 'history',       'name' => 'Service History', 'route' => '/field-service/portal/history'],
            ],
        ],

    ],

    'permissions' => [
        'field-service.view'              => 'View field service module',
        'field-service.work-orders.create'=> 'Create work orders',
        'field-service.work-orders.manage'=> 'Manage & dispatch work orders',
        'field-service.technicians.manage'=> 'Manage technicians',
        'field-service.agreements.manage' => 'Manage service agreements',
        'field-service.portal.manage'     => 'Manage customer portal',
        'field-service.reports.view'      => 'View FSM analytics',
    ],

    'tenancy' => [
        'tenant_aware'       => true,
        'uses_tenant_db'     => true,
        'central_tables'     => [],
        'cached_per_tenant'  => true,
        'mobile_auth'        => 'tenant_sanctum_token',  // mobile app uses Sanctum per tenant
        'customer_portal'    => 'tenant_subdomain',       // portal scoped to tenant subdomain
    ],
];
