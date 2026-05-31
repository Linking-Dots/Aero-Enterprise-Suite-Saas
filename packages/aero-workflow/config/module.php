<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Engine Module Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-cutting workflow engine for approval workflows, business rules,
    | automation triggers, SLA monitoring, and process orchestration.
    |
    | Tenancy concern:
    *   - All workflow definitions are tenant-scoped
    *   - Workflow instances are tenant-scoped
    *   - Cross-module workflow triggers (e.g., aero-compliance → aero-finance)
    *   - No cross-tenant workflow data access
    */

    'code' => 'workflow',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Workflow Engine',
    'description' => 'Cross-cutting workflow engine: approval workflows, business rules, automation triggers, SLA monitoring, and process orchestration for all modules.',
    'icon' => 'ArrowsRightLeftIcon',
    'route_prefix' => '/workflow',
    'category' => 'infrastructure',
    'priority' => 5,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('WORKFLOW_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'submodules' => [
        [
            'code' => 'workflows',
            'name' => 'Workflow Designer',
            'description' => 'Create and manage workflow definitions',
            'icon' => 'ArrowPathRoundedSquareIcon',
            'route' => '/workflows',
            'components' => [
                [
                    'code' => 'definitions',
                    'name' => 'Workflow Definitions',
                    'type' => 'page',
                    'route' => '/workflows',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Workflows'],
                        ['code' => 'create', 'name' => 'Create Workflow'],
                        ['code' => 'update', 'name' => 'Update Workflow'],
                        ['code' => 'delete', 'name' => 'Delete Workflow'],
                        ['code' => 'activate', 'name' => 'Activate Workflow'],
                        ['code' => 'deactivate', 'name' => 'Deactivate Workflow'],
                    ],
                ],
                [
                    'code' => 'templates',
                    'name' => 'Workflow Templates',
                    'type' => 'page',
                    'route' => '/workflow-templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'update', 'name' => 'Update Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                    ],
                ],
                [
                    'code' => 'instances',
                    'name' => 'Workflow Instances',
                    'type' => 'page',
                    'route' => '/workflow-instances',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Instances'],
                        ['code' => 'retry', 'name' => 'Retry Instance'],
                        ['code' => 'cancel', 'name' => 'Cancel Instance'],
                    ],
                ],
                [
                    'code' => 'approvals',
                    'name' => 'My Approvals',
                    'type' => 'page',
                    'route' => '/workflow-instances/approvals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Approvals'],
                        ['code' => 'approve', 'name' => 'Approve'],
                        ['code' => 'reject', 'name' => 'Reject'],
                        // Plan 12 — 'escalate' deferred to roadmap until the SLA
                        // monitor job (WorkflowSlaMonitorJob) ships. Declared-
                        // but-unimplemented HRMAC actions silently allow access
                        // when granted (no route exercises them).
                    ],
                ],
            ],
        ],
    ],

    /*
    | Deferred items.
    */
    'roadmap' => [
        'escalate_action' => 'Manual + automatic SLA escalation. Requires WorkflowSlaMonitorJob scheduled every minute to scan in-flight instances and bump escalation_target when sla_due_at passes.',
        'sla_monitor_job' => 'Scheduled job that escalates breached SLAs. Needs sla_due_at column on workflow_instances + WorkflowEscalationService.',
        'workflowable_morph' => 'COMPLETED Plan 12 T1 (migration 2026_05_29_000300). WorkflowInstance now has workflowable_id/_type. Legacy leaves.workflow_instance_id remains as transitional bridge until HRM drops it.',
    ],
];
