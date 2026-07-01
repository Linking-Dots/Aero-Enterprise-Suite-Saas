<?php

return [
    'code' => 'hrm',
    'product_code' => 'hrm',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Human Resources',
    'description' => 'Complete HR management including employees, attendance, leave, payroll, recruitment, performance, training, and analytics',
    'icon' => 'UserGroupIcon',
    'route_prefix' => '/hrm',
    'category' => 'human_resources',
    'priority' => 10,
    'is_core' => false,
    'is_active' => true,
    'version' => '1.0.0',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    /*
    |--------------------------------------------------------------------------
    | Self-Service Navigation Items
    |--------------------------------------------------------------------------
    |
    | Employee-facing "My *" pages that appear under the unified "My Workspace"
    | menu. These are automatically aggregated by NavigationRegistry.
    |
    */
    'self_service' => [
        [
            'code' => 'my-dashboard',
            'name' => 'My Dashboard',
            'icon' => 'HomeIcon',
            'route' => '/hrm/employee/dashboard',
            'priority' => 1,
        ],
        [
            'code' => 'my-attendance',
            'name' => 'My Attendance',
            'icon' => 'ClockIcon',
            'route' => '/hrm/attendance-employee',
            'priority' => 2,
        ],
        [
            'code' => 'my-leaves',
            'name' => 'My Leaves',
            'icon' => 'CalendarIcon',
            'route' => '/hrm/leaves-employee',
            'priority' => 3,
        ],
        [
            'code' => 'my-time-off',
            'name' => 'My Time-Off',
            'icon' => 'ArrowRightOnRectangleIcon',
            'route' => '/hrm/self-service/time-off',
            'priority' => 4,
        ],
        [
            'code' => 'my-payslips',
            'name' => 'My Payslips',
            'icon' => 'BanknotesIcon',
            'route' => '/hrm/self-service/payslips',
            'priority' => 5,
        ],
        [
            'code' => 'my-expenses',
            'name' => 'My Expenses',
            'icon' => 'ReceiptPercentIcon',
            'route' => '/hrm/my-expenses',
            'priority' => 6,
        ],
        [
            'code' => 'my-documents',
            'name' => 'My Documents',
            'icon' => 'DocumentTextIcon',
            'route' => '/hrm/self-service/documents',
            'priority' => 7,
        ],
        [
            'code' => 'my-benefits',
            'name' => 'My Benefits',
            'icon' => 'GiftIcon',
            'route' => '/hrm/self-service/benefits',
            'priority' => 8,
        ],
        [
            'code' => 'my-trainings',
            'name' => 'My Trainings',
            'icon' => 'AcademicCapIcon',
            'route' => '/hrm/self-service/trainings',
            'priority' => 9,
        ],
        [
            'code' => 'my-performance',
            'name' => 'My Performance',
            'icon' => 'ChartBarSquareIcon',
            'route' => '/hrm/self-service/performance',
            'priority' => 10,
        ],
        [
            'code' => 'my-goals',
            'name' => 'My Goals',
            'icon' => 'FlagIcon',
            'route' => '/hrm/goals',
            'priority' => 11,
        ],
        [
            'code' => 'my-career-path',
            'name' => 'My Career Path',
            'icon' => 'ArrowTrendingUpIcon',
            'route' => '/hrm/self-service/career-path',
            'priority' => 12,
        ],
        [
            'code' => 'my-feedback',
            'name' => 'My 360° Feedback',
            'icon' => 'ArrowPathIcon',
            'route' => '/hrm/feedback-360',
            'priority' => 13,
        ],
    ],

    'submodules' => [
        // Self Service (My Workspace items for HRM)
        [
            'code' => 'employee-self-service',
            'name' => 'Self Service',
            'description' => 'Employee self-service features (My Workspace HRM items)',
            'icon' => 'UserCircleIcon',
            'route' => '/hrm/employee/dashboard',
            'priority' => 0,
            'show_in_nav' => false, // Handled by NavigationRegistry::getSelfServiceNavigation() → My Workspace
            'components' => [
                [
                    'code' => 'my-dashboard',
                    'name' => 'My Dashboard',
                    'type' => 'page',
                    'route' => '/hrm/employee/dashboard',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Dashboard'],
                    ],
                ],
                [
                    'code' => 'my-attendance',
                    'name' => 'My Attendance',
                    'type' => 'page',
                    'route' => '/hrm/attendance-employee',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Attendance'],
                        ['code' => 'clock-in-out', 'name' => 'Clock In/Out'],
                    ],
                ],
                [
                    'code' => 'my-leaves',
                    'name' => 'My Leaves',
                    'type' => 'page',
                    'route' => '/hrm/leaves-employee',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Leaves'],
                        ['code' => 'apply', 'name' => 'Apply Leave'],
                    ],
                ],
                [
                    'code' => 'my-time-off',
                    'name' => 'My Time-Off',
                    'type' => 'page',
                    'route' => '/hrm/self-service/time-off',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Time-Off'],
                        ['code' => 'request', 'name' => 'Request Time-Off'],
                    ],
                ],
                [
                    'code' => 'my-payslips',
                    'name' => 'My Payslips',
                    'type' => 'page',
                    'route' => '/hrm/self-service/payslips',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payslips'],
                        ['code' => 'download', 'name' => 'Download Payslip'],
                    ],
                ],
                [
                    'code' => 'my-expenses',
                    'name' => 'My Expenses',
                    'type' => 'page',
                    'route' => '/hrm/my-expenses',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Expenses'],
                        ['code' => 'submit', 'name' => 'Submit Expense'],
                    ],
                ],
                [
                    'code' => 'my-documents',
                    'name' => 'My Documents',
                    'type' => 'page',
                    'route' => '/hrm/self-service/documents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Documents'],
                        ['code' => 'upload', 'name' => 'Upload Document'],
                    ],
                ],
                [
                    'code' => 'my-benefits',
                    'name' => 'My Benefits',
                    'type' => 'page',
                    'route' => '/hrm/self-service/benefits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Benefits'],
                    ],
                ],
                [
                    'code' => 'my-trainings',
                    'name' => 'My Trainings',
                    'type' => 'page',
                    'route' => '/hrm/self-service/trainings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Trainings'],
                        ['code' => 'enroll', 'name' => 'Enroll Training'],
                    ],
                ],
                [
                    'code' => 'my-performance',
                    'name' => 'My Performance',
                    'type' => 'page',
                    'route' => '/hrm/self-service/performance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Performance'],
                    ],
                ],
                [
                    'code' => 'my-goals',
                    'name' => 'My Goals',
                    'type' => 'page',
                    'route' => '/hrm/goals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Goals'],
                        ['code' => 'update', 'name' => 'Update Goals'],
                    ],
                ],
                [
                    'code' => 'my-career-path',
                    'name' => 'My Career Path',
                    'type' => 'page',
                    'route' => '/hrm/self-service/career-path',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Career Path'],
                    ],
                ],
                [
                    'code' => 'my-feedback',
                    'name' => 'My 360° Feedback',
                    'type' => 'page',
                    'route' => '/hrm/feedback-360',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Feedback'],
                        ['code' => 'submit', 'name' => 'Submit Feedback'],
                    ],
                ],
            ],
        ],
        // 2.1 Employees (Original + Org Chart)
        [
            'code' => 'employees',
            'name' => 'Employees',
            'description' => 'Employee directory, profiles, departments, designations, and lifecycle management',
            'icon' => 'UsersIcon',
            'route' => '/hrm/employees',
            'priority' => 1,
            'components' => [
                // HRMAC-scoped components for granular permission enforcement
                [
                    'code' => 'list',
                    'name' => 'Employee List',
                    'type' => 'page',
                    'route' => '/hrm/employees',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Create/Edit'],
                    ],
                ],
                [
                    'code' => 'detail',
                    'name' => 'Employee Profile',
                    'type' => 'page',
                    'route' => '/hrm/employees/{id}',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Edit'],
                    ],
                ],
                [
                    'code' => 'bank-details',
                    'name' => 'Bank Details',
                    'type' => 'feature',
                    'route' => '/hrm/employees/{id}',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Edit'],
                    ],
                ],
                [
                    'code' => 'documents',
                    'name' => 'Documents',
                    'type' => 'feature',
                    'route' => '/hrm/employees/{id}/documents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Upload'],
                    ],
                ],
                [
                    'code' => 'employee-directory',
                    'name' => 'Employee Directory',
                    'type' => 'page',
                    'route' => '/hrm/employees',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Employees'],
                        ['code' => 'create', 'name' => 'Create Employee'],
                        ['code' => 'update', 'name' => 'Update Employee'],
                        ['code' => 'delete', 'name' => 'Delete Employee'],
                        ['code' => 'export', 'name' => 'Export Employees'],
                        ['code' => 'change-status', 'name' => 'Change Employee Status'],
                    ],
                ],
                [
                    'code' => 'org-chart', // Added
                    'name' => 'Organization Chart',
                    'type' => 'page',
                    'route' => '/hrm/org-chart',
                    'actions' => [['code' => 'view', 'name' => 'View Org Chart']],
                ],
                [
                    'code' => 'employee-profile',
                    'name' => 'Employee Profile',
                    'type' => 'page',
                    'route' => '/hrm/employees/{id}',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Profile'],
                        ['code' => 'update', 'name' => 'Update Profile'],
                    ],
                ],
                [
                    'code' => 'employee-documents',
                    'name' => 'Employee Documents',
                    'type' => 'page',
                    'route' => '/hrm/employees/{id}/documents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Documents'],
                        ['code' => 'manage', 'name' => 'Manage Documents'],
                    ],
                ],
                [
                    'code' => 'onboarding-wizard',
                    'name' => 'Employee Onboarding Wizard',
                    'type' => 'page',
                    'route' => '/hrm/onboarding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Onboarding'],
                        ['code' => 'onboard', 'name' => 'Onboard Employee'],
                    ],
                ],
                [
                    'code' => 'exit-termination',
                    'name' => 'Employee Exit/Termination',
                    'type' => 'page',
                    'route' => '/hrm/offboarding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Offboarding'],
                        ['code' => 'offboard', 'name' => 'Offboard Employee'],
                    ],
                ],
                [
                    'code' => 'custom-fields',
                    'name' => 'Custom Fields',
                    'type' => 'page',
                    'route' => '/hrm/employees',
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Custom Fields'],
                    ],
                ],
            ],
        ],

        // 2.2 Attendance (Original)
        [
            'code' => 'attendance',
            'name' => 'Attendance',
            'description' => 'Daily attendance, shifts, overtime, and attendance adjustments',
            'icon' => 'ClockIcon',
            'route' => '/hrm/attendance',
            'priority' => 2,
            'components' => [
                [
                    'code' => 'daily-attendance',
                    'name' => 'Daily Attendance',
                    'type' => 'page',
                    'route' => '/hrm/attendance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Attendance'],
                        ['code' => 'mark', 'name' => 'Mark Attendance'],
                        ['code' => 'update', 'name' => 'Update Attendance'],
                        ['code' => 'delete', 'name' => 'Delete Attendance'],
                        ['code' => 'export', 'name' => 'Export Attendance'],
                    ],
                ],
                [
                    'code' => 'monthly-calendar',
                    'name' => 'Monthly Attendance Calendar',
                    'type' => 'page',
                    'route' => '/hrm/attendance',
                    'actions' => [['code' => 'view', 'name' => 'View Calendar']],
                ],
                [
                    'code' => 'attendance-logs',
                    'name' => 'Attendance Logs',
                    'type' => 'page',
                    'route' => '/hrm/attendance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Attendance Logs'],
                        ['code' => 'update', 'name' => 'Update Attendance Log'],
                    ],
                ],
                [
                    'code' => 'shift-scheduling',
                    'name' => 'Shift Scheduling',
                    'type' => 'page',
                    'route' => '/hrm/shifts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Shifts'],
                        ['code' => 'create', 'name' => 'Create Shift'],
                        ['code' => 'update', 'name' => 'Update Shift'],
                        ['code' => 'delete', 'name' => 'Delete Shift'],
                    ],
                ],
                [
                    'code' => 'adjustment-requests',
                    'name' => 'Attendance Adjustment Requests',
                    'type' => 'page',
                    'route' => '/hrm/attendance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Adjustment Requests'],
                        ['code' => 'approve', 'name' => 'Approve Adjustment'],
                        ['code' => 'reject', 'name' => 'Reject Adjustment'],
                    ],
                ],
                [
                    'code' => 'device-rules',
                    'name' => 'Attendance Device/IP/Geo Rules',
                    'type' => 'page',
                    'route' => '/hrm/attendance',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Attendance Rules']],
                ],
                [
                    'code' => 'overtime-rules',
                    'name' => 'Overtime Rules',
                    'type' => 'page',
                    'route' => '/hrm/overtime',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Overtime Rules']],
                ],
                [
                    'code' => 'my-attendance',
                    'name' => 'My Attendance',
                    'type' => 'page',
                    'route' => '/hrm/my-attendance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Own Attendance'],
                        ['code' => 'punch', 'name' => 'Punch In/Out'],
                    ],
                ],
                [
                    'code' => 'shift-marketplace',
                    'name' => 'Shift Marketplace',
                    'type' => 'page',
                    'route' => '/hrm/attendance/shift-marketplace',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Marketplace'],
                        ['code' => 'create', 'name' => 'Create Swap Request'],
                        ['code' => 'approve', 'name' => 'Approve Swap'],
                        ['code' => 'reject', 'name' => 'Reject Swap'],
                    ],
                ],
            ],
        ],

        // 2.3 Leave Management (Original + Conflict Checker)
        [
            'code' => 'leaves',
            'name' => 'Leaves',
            'description' => 'Leave types, requests, balances, holidays, and policies',
            'icon' => 'CalendarIcon',
            'route' => '/hrm/leave/applications',
            'priority' => 3,
            'components' => [
                [
                    'code' => 'leave-types',
                    'name' => 'Leave Types',
                    'type' => 'page',
                    'route' => '/hrm/leave/types',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Leave Types'],
                        ['code' => 'create', 'name' => 'Create Leave Type'],
                        ['code' => 'update', 'name' => 'Update Leave Type'],
                        ['code' => 'delete', 'name' => 'Delete Leave Type'],
                    ],
                ],
                [
                    'code' => 'leave-balances',
                    'name' => 'Leave Balances',
                    'type' => 'page',
                    'route' => '/hrm/leave/balance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Leave Balances'],
                        ['code' => 'update', 'name' => 'Update Leave Balance'],
                    ],
                ],
                [
                    'code' => 'leave-requests',
                    'name' => 'Leave Requests',
                    'type' => 'page',
                    'route' => '/hrm/leave/applications',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Leave Requests'],
                        ['code' => 'create', 'name' => 'Create Leave Request'],
                        ['code' => 'update', 'name' => 'Update Leave Request'],
                        ['code' => 'delete', 'name' => 'Delete Leave Request'],
                        ['code' => 'approve', 'name' => 'Approve Leave Request'],
                        ['code' => 'reject', 'name' => 'Reject Leave Request'],
                        ['code' => 'export', 'name' => 'Export Leave Requests'],
                    ],
                ],
                [
                    'code' => 'conflict-checker', // Added
                    'name' => 'Conflict Checker',
                    'type' => 'feature',
                    'route' => null,
                    'actions' => [['code' => 'view', 'name' => 'Check Conflicts']],
                ],
                [
                    'code' => 'holiday-calendar',
                    'name' => 'Holiday Calendar',
                    'type' => 'page',
                    'route' => '/hrm/leave/calendar',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Holidays'],
                        ['code' => 'create', 'name' => 'Create Holiday'],
                        ['code' => 'update', 'name' => 'Update Holiday'],
                        ['code' => 'delete', 'name' => 'Delete Holiday'],
                    ],
                ],
                [
                    'code' => 'leave-policies',
                    'name' => 'Leave Policies',
                    'type' => 'page',
                    'route' => '/hrm/leave/applications',
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Leave Policies'],
                        ['code' => 'view', 'name' => 'View Leave Policies'],
                        ['code' => 'edit', 'name' => 'Edit Leave Policies'],
                    ],
                ],
                [
                    'code' => 'leave-accrual',
                    'name' => 'Leave Accrual Engine',
                    'type' => 'page',
                    'route' => '/hrm/leaves/accrual',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Accrual Rules'],
                        ['code' => 'create', 'name' => 'Create Accrual Rule'],
                        ['code' => 'update', 'name' => 'Update Accrual Rule'],
                        ['code' => 'delete', 'name' => 'Delete Accrual Rule'],
                        ['code' => 'run', 'name' => 'Run Leave Accrual'],
                    ],
                ],
            ],
        ],

        // 2.4 Payroll (Original + Tax Declarations)
        [
            'code' => 'payroll',
            'name' => 'Payroll',
            'description' => 'Salary structures, payroll processing, payslips, tax, and loans',
            'icon' => 'CurrencyDollarIcon',
            'route' => '/hrm/payroll/runs',
            'priority' => 4,
            'components' => [
                [
                    'code' => 'salary-structures',
                    'name' => 'Salary Structures',
                    'type' => 'page',
                    'route' => '/hrm/payroll/structures',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Salary Structures'],
                        ['code' => 'create', 'name' => 'Create Salary Structure'],
                        ['code' => 'update', 'name' => 'Update Salary Structure'],
                        ['code' => 'delete', 'name' => 'Delete Salary Structure'],
                    ],
                ],
                [
                    'code' => 'salary-components',
                    'name' => 'Salary Components',
                    'type' => 'page',
                    'route' => '/hrm/payroll/components',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Salary Components'],
                        ['code' => 'create', 'name' => 'Create Salary Component'],
                        ['code' => 'update', 'name' => 'Update Salary Component'],
                        ['code' => 'delete', 'name' => 'Delete Salary Component'],
                    ],
                ],
                [
                    'code' => 'payroll-run',
                    'name' => 'Payroll Run',
                    'type' => 'page',
                    'route' => '/hrm/payroll/runs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payroll Runs'],
                        ['code' => 'execute', 'name' => 'Execute Payroll Run'],
                        ['code' => 'lock', 'name' => 'Lock Payroll Run'],
                        ['code' => 'rollback', 'name' => 'Rollback Payroll Run'],
                        ['code' => 'export', 'name' => 'Export Payroll Run'],
                    ],
                ],
                [
                    'code' => 'payslips',
                    'name' => 'Payslips',
                    'type' => 'page',
                    'route' => '/hrm/payroll/runs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payslips'],
                        ['code' => 'download', 'name' => 'Download Payslip'],
                        ['code' => 'send', 'name' => 'Send Payslip'],
                    ],
                ],
                [
                    'code' => 'tax-setup',
                    'name' => 'Tax Setup',
                    'type' => 'page',
                    'route' => '/hrm/payroll/settings/tax',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Tax Rules']],
                ],
                [
                    'code' => 'tax-declarations', // Added
                    'name' => 'IT/Tax Declarations',
                    'type' => 'page',
                    'route' => '/hrm/payroll/runs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Declarations'],
                        ['code' => 'verify', 'name' => 'Verify Proofs'],
                    ],
                ],
                [
                    'code' => 'loans',
                    'name' => 'Loan & Advance Management',
                    'type' => 'page',
                    'route' => '/hrm/payroll/runs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Loans'],
                        ['code' => 'create', 'name' => 'Create Loan'],
                        ['code' => 'update', 'name' => 'Update Loan'],
                        ['code' => 'delete', 'name' => 'Delete Loan'],
                    ],
                ],
                [
                    'code' => 'bank-file',
                    'name' => 'Bank File Generator',
                    'type' => 'page',
                    'route' => '/hrm/payroll/runs',
                    'actions' => [['code' => 'generate', 'name' => 'Generate Bank File']],
                ],
            ],
        ],

        // NEW: 2.5 Expenses & Claims
        [
            'code' => 'expenses',
            'name' => 'Expenses & Claims',
            'description' => 'Employee expense claims with approval workflow',
            'icon' => 'ReceiptPercentIcon',
            'route' => '/hrm/expenses',
            'priority' => 5,
            'components' => [
                [
                    'code' => 'expense-claims',
                    'name' => 'Expense Claims',
                    'type' => 'page',
                    'route' => '/hrm/expenses',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Claims'],
                        ['code' => 'create', 'name' => 'Create Claim'],
                        ['code' => 'update', 'name' => 'Update Claim'],
                        ['code' => 'delete', 'name' => 'Delete Claim'],
                        ['code' => 'approve', 'name' => 'Approve Claims'],
                        ['code' => 'reject', 'name' => 'Reject Claims'],
                    ],
                ],
                [
                    'code' => 'my-expense-claims',
                    'name' => 'My Expense Claims',
                    'type' => 'page',
                    'route' => '/hrm/my-expenses',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View My Claims'],
                        ['code' => 'create', 'name' => 'Submit Claim'],
                    ],
                ],
                [
                    'code' => 'expense-categories',
                    'name' => 'Expense Categories',
                    'type' => 'page',
                    'route' => '/hrm/expenses',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Categories'],
                        ['code' => 'manage', 'name' => 'Manage Categories'],
                    ],
                ],
            ],
        ],

        // NEW: 2.6 Assets Management
        [
            'code' => 'assets',
            'name' => 'Assets Management',
            'description' => 'Track and allocate company assets to employees',
            'icon' => 'ComputerDesktopIcon',
            'route' => '/hrm/assets',
            'priority' => 6,
            'components' => [
                [
                    'code' => 'asset-inventory',
                    'name' => 'Asset Inventory',
                    'type' => 'page',
                    'route' => '/hrm/assets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Assets'],
                        ['code' => 'create', 'name' => 'Add Asset'],
                        ['code' => 'update', 'name' => 'Update Asset'],
                        ['code' => 'delete', 'name' => 'Delete Asset'],
                        ['code' => 'allocate', 'name' => 'Allocate Asset'],
                        ['code' => 'return', 'name' => 'Return Asset'],
                    ],
                ],
                [
                    'code' => 'asset-allocations',
                    'name' => 'Asset Allocations',
                    'type' => 'page',
                    'route' => '/hrm/assets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Allocations'],
                        ['code' => 'assign', 'name' => 'Assign Asset'],
                        ['code' => 'return', 'name' => 'Return Asset'],
                    ],
                ],
                [
                    'code' => 'asset-categories',
                    'name' => 'Asset Categories',
                    'type' => 'page',
                    'route' => '/hrm/assets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Categories'],
                        ['code' => 'manage', 'name' => 'Manage Categories'],
                    ],
                ],
            ],
        ],

        // NEW: 2.7 Disciplinary
        [
            'code' => 'disciplinary',
            'name' => 'Disciplinary',
            'description' => 'Manage disciplinary cases, investigations, and warnings',
            'icon' => 'ExclamationTriangleIcon',
            'route' => '/hrm/disciplinary',
            'priority' => 7,
            'components' => [
                [
                    'code' => 'disciplinary-cases',
                    'name' => 'Disciplinary Cases',
                    'type' => 'page',
                    'route' => '/hrm/disciplinary',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cases'],
                        ['code' => 'create', 'name' => 'Create Case'],
                        ['code' => 'update', 'name' => 'Update Case'],
                        ['code' => 'delete', 'name' => 'Delete Case'],
                        ['code' => 'investigate', 'name' => 'Start Investigation'],
                        ['code' => 'take-action', 'name' => 'Take Action'],
                        ['code' => 'close', 'name' => 'Close Case'],
                        ['code' => 'appeal', 'name' => 'File Appeal'],
                    ],
                ],
                [
                    'code' => 'warnings',
                    'name' => 'Warnings',
                    'type' => 'page',
                    'route' => '/hrm/disciplinary',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Warnings'],
                        ['code' => 'issue', 'name' => 'Issue Warning'],
                    ],
                ],
                [
                    'code' => 'action-types',
                    'name' => 'Action Types',
                    'type' => 'page',
                    'route' => '/hrm/disciplinary',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Action Types'],
                        ['code' => 'manage', 'name' => 'Manage Action Types'],
                    ],
                ],
            ],
        ],

        // 2.8 Recruitment (Original Full Detail)
        [
            'code' => 'recruitment',
            'name' => 'Recruitment',
            'description' => 'Job openings, applicants, interviews, evaluations, and offer letters',
            'icon' => 'BriefcaseIcon',
            'route' => '/hrm/recruitment',
            'priority' => 8,
            'components' => [
                [
                    'code' => 'job-openings',
                    'name' => 'Job Openings',
                    'type' => 'page',
                    'route' => '/hrm/recruitment',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Job Openings'],
                        ['code' => 'create', 'name' => 'Create Job Opening'],
                        ['code' => 'update', 'name' => 'Update Job Opening'],
                        ['code' => 'delete', 'name' => 'Delete Job Opening'],
                    ],
                ],
                [
                    'code' => 'applicants',
                    'name' => 'Applicants',
                    'type' => 'page',
                    'route' => '/hrm/recruitment',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Applicants'],
                        ['code' => 'create', 'name' => 'Create Applicant'],
                        ['code' => 'update', 'name' => 'Update Applicant'],
                        ['code' => 'delete', 'name' => 'Delete Applicant'],
                        ['code' => 'move-stage', 'name' => 'Move Pipeline Stage'],
                        ['code' => 'export', 'name' => 'Export Applicants'],
                        ['code' => 'send-email', 'name' => 'Send Email to Applicant'],
                    ],
                ],
                [
                    'code' => 'candidate-pipeline',
                    'name' => 'Candidate Pipelines',
                    'type' => 'page',
                    'route' => '/hrm/recruitment',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pipeline'],
                        ['code' => 'configure', 'name' => 'Configure Pipeline Stages'],
                    ],
                ],
                [
                    'code' => 'interview-scheduling',
                    'name' => 'Interview Scheduling',
                    'type' => 'page',
                    'route' => '/hrm/recruitment',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Interview Schedules'],
                        ['code' => 'create', 'name' => 'Create Interview Schedule'],
                        ['code' => 'update', 'name' => 'Update Interview Schedule'],
                        ['code' => 'delete', 'name' => 'Delete Interview Schedule'],
                    ],
                ],
                [
                    'code' => 'evaluation-scores',
                    'name' => 'Evaluation Scores',
                    'type' => 'page',
                    'route' => '/hrm/recruitment',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Evaluation Scores']],
                ],
                [
                    'code' => 'offer-letters',
                    'name' => 'Offer Letters',
                    'type' => 'page',
                    'route' => '/hrm/recruitment',
                    'actions' => [
                        ['code' => 'create', 'name' => 'Create Offer Letter'],
                        ['code' => 'send', 'name' => 'Send Offer Letter'],
                    ],
                ],
                [
                    'code' => 'portal-settings',
                    'name' => 'Public Job Portal Settings',
                    'type' => 'page',
                    'route' => '/hrm/recruitment',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Job Portal']],
                ],
            ],
        ],

        // 2.9 Performance (Original)
        [
            'code' => 'performance',
            'name' => 'Performance',
            'description' => 'KPIs, appraisals, 360° reviews, and performance tracking',
            'icon' => 'ChartBarSquareIcon',
            'route' => '/hrm/performance',
            'priority' => 9,
            'components' => [
                [
                    'code' => 'kpi-setup',
                    'name' => 'KPI Setup',
                    'type' => 'page',
                    'route' => '/hrm/performance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View KPIs'],
                        ['code' => 'create', 'name' => 'Create KPI'],
                        ['code' => 'update', 'name' => 'Update KPI'],
                        ['code' => 'delete', 'name' => 'Delete KPI'],
                    ],
                ],
                [
                    'code' => 'appraisal-cycles',
                    'name' => 'Appraisal Cycles',
                    'type' => 'page',
                    'route' => '/hrm/performance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Appraisal Cycles'],
                        ['code' => 'create', 'name' => 'Create Appraisal Cycle'],
                        ['code' => 'update', 'name' => 'Update Appraisal Cycle'],
                        ['code' => 'delete', 'name' => 'Delete Appraisal Cycle'],
                    ],
                ],
                [
                    'code' => 'reviews-360',
                    'name' => '360° Reviews',
                    'type' => 'page',
                    'route' => '/hrm/performance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View 360 Reviews'],
                        ['code' => 'submit', 'name' => 'Submit Review'],
                        ['code' => 'approve', 'name' => 'Approve Review'],
                        ['code' => 'reject', 'name' => 'Reject Review'],
                    ],
                ],
                [
                    'code' => 'score-aggregation',
                    'name' => 'Score Aggregation',
                    'type' => 'page',
                    'route' => '/hrm/performance',
                    'actions' => [['code' => 'view', 'name' => 'View Aggregated Scores']],
                ],
                [
                    'code' => 'promotion-recommendations',
                    'name' => 'Promotion Recommendations',
                    'type' => 'page',
                    'route' => '/hrm/performance',
                    'actions' => [['code' => 'state-change', 'name' => 'Change Promotion State']],
                ],
                [
                    'code' => 'performance-reports',
                    'name' => 'Performance Reports',
                    'type' => 'page',
                    'route' => '/hrm/performance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Performance Reports'],
                        ['code' => 'export', 'name' => 'Export Performance Reports'],
                    ],
                ],
                [
                    'code' => 'calibration',
                    'name' => 'Performance Calibration',
                    'type' => 'page',
                    'route' => '/hrm/performance/calibration',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Calibration'],
                        ['code' => 'manage', 'name' => 'Manage Calibration Sessions'],
                    ],
                ],
                [
                    'code' => 'improvement-plans',
                    'name' => 'Performance Improvement Plans',
                    'type' => 'page',
                    'route' => '/hrm/performance/improvement-plans',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Improvement Plans'],
                        ['code' => 'create', 'name' => 'Create Improvement Plan'],
                        ['code' => 'update', 'name' => 'Update Improvement Plan'],
                        ['code' => 'delete', 'name' => 'Delete Improvement Plan'],
                    ],
                ],
                [
                    'code' => 'goals',
                    'name' => 'Goals',
                    'type' => 'page',
                    'route' => '/hrm/performance/goals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Goals'],
                        ['code' => 'edit', 'name' => 'Create/Edit/Delete Goals'],
                    ],
                ],
                [
                    'code' => 'skill-matrix',
                    'name' => 'Skill Matrix',
                    'type' => 'page',
                    'route' => '/hrm/performance/skills',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Skill Matrix'],
                    ],
                ],
            ],
        ],

        // 2.10 Training (Original)
        [
            'code' => 'training',
            'name' => 'Training',
            'description' => 'Training programs, sessions, trainers, and certifications',
            'icon' => 'AcademicCapIcon',
            'route' => '/hrm/training',
            'priority' => 10,
            'components' => [
                [
                    'code' => 'training-programs',
                    'name' => 'Training Programs',
                    'type' => 'page',
                    'route' => '/hrm/training',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Training Programs'],
                        ['code' => 'create', 'name' => 'Create Training Program'],
                        ['code' => 'update', 'name' => 'Update Training Program'],
                        ['code' => 'delete', 'name' => 'Delete Training Program'],
                    ],
                ],
                [
                    'code' => 'training-sessions',
                    'name' => 'Training Sessions',
                    'type' => 'page',
                    'route' => '/hrm/training',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Training Sessions'],
                        ['code' => 'create', 'name' => 'Create Training Session'],
                        ['code' => 'update', 'name' => 'Update Training Session'],
                        ['code' => 'delete', 'name' => 'Delete Training Session'],
                    ],
                ],
                [
                    'code' => 'trainers',
                    'name' => 'Trainers',
                    'type' => 'page',
                    'route' => '/hrm/training',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Trainers'],
                        ['code' => 'create', 'name' => 'Create Trainer'],
                        ['code' => 'update', 'name' => 'Update Trainer'],
                        ['code' => 'delete', 'name' => 'Delete Trainer'],
                    ],
                ],
                [
                    'code' => 'enrollment',
                    'name' => 'Enrollment',
                    'type' => 'page',
                    'route' => '/hrm/training',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Training Enrollment']],
                ],
                [
                    'code' => 'training-attendance',
                    'name' => 'Attendance Tracking (Training)',
                    'type' => 'page',
                    'route' => '/hrm/training',
                    'actions' => [['code' => 'mark', 'name' => 'Mark Training Attendance']],
                ],
                [
                    'code' => 'certifications',
                    'name' => 'Certification Issuance',
                    'type' => 'page',
                    'route' => '/hrm/training',
                    'actions' => [
                        ['code' => 'generate', 'name' => 'Generate Certificate'],
                        ['code' => 'download', 'name' => 'Download Certificate'],
                    ],
                ],
                [
                    'code' => 'training-feedback',
                    'name' => 'Training Feedback',
                    'type' => 'page',
                    'route' => '/hrm/training',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Feedback'],
                        ['code' => 'submit', 'name' => 'Submit Feedback'],
                    ],
                ],
            ],
        ],

        // 2.11 HR Analytics (Original)
        [
            'code' => 'hr-analytics',
            'name' => 'HR Analytics',
            'description' => 'Workforce analytics, turnover, attendance insights, and reports',
            'icon' => 'ChartPieIcon',
            'route' => '/hrm/analytics',
            'priority' => 11,
            'components' => [
                [
                    'code' => 'workforce-overview',
                    'name' => 'Workforce Overview',
                    'type' => 'page',
                    'route' => '/hrm/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Workforce Overview'],
                        ['code' => 'export', 'name' => 'Export Workforce Data'],
                    ],
                ],
                [
                    'code' => 'turnover-analytics',
                    'name' => 'Turnover Analytics',
                    'type' => 'page',
                    'route' => '/hrm/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Turnover Analytics'],
                        ['code' => 'export', 'name' => 'Export Turnover Data'],
                    ],
                ],
                [
                    'code' => 'attendance-insights',
                    'name' => 'Attendance Insights',
                    'type' => 'page',
                    'route' => '/hrm/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Attendance Insights'],
                        ['code' => 'export', 'name' => 'Export Attendance Data'],
                    ],
                ],
                [
                    'code' => 'payroll-cost-analysis',
                    'name' => 'Payroll Cost Analysis',
                    'type' => 'page',
                    'route' => '/hrm/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payroll Analysis'],
                        ['code' => 'export', 'name' => 'Export Payroll Data'],
                    ],
                ],
                [
                    'code' => 'recruitment-funnel',
                    'name' => 'Recruitment Funnel Analytics',
                    'type' => 'page',
                    'route' => '/hrm/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Recruitment Funnel'],
                        ['code' => 'export', 'name' => 'Export Recruitment Data'],
                    ],
                ],
                [
                    'code' => 'performance-insights',
                    'name' => 'Performance Insights',
                    'type' => 'page',
                    'route' => '/hrm/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Performance Insights'],
                        ['code' => 'export', 'name' => 'Export Performance Data'],
                    ],
                ],
                [
                    'code' => 'hr-reports',
                    'name' => 'HR Reports',
                    'type' => 'page',
                    'route' => '/hrm/hr-reports',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View HR Reports'],
                        ['code' => 'export', 'name' => 'Export Reports'],
                    ],
                ],
            ],
        ],

        // 2.12 Succession Planning
        [
            'code' => 'succession-planning',
            'name' => 'Succession Planning',
            'description' => 'Identify and develop future leaders for critical positions',
            'icon' => 'UserPlusIcon',
            'route' => '/hrm/succession-planning',
            'priority' => 12,
            'components' => [
                [
                    'code' => 'succession-plans',
                    'name' => 'Succession Plans',
                    'type' => 'page',
                    'route' => '/hrm/succession-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Succession Plans'],
                        ['code' => 'create', 'name' => 'Create Succession Plan'],
                        ['code' => 'update', 'name' => 'Update Succession Plan'],
                        ['code' => 'delete', 'name' => 'Delete Succession Plan'],
                    ],
                ],
                [
                    'code' => 'succession-candidates',
                    'name' => 'Succession Candidates',
                    'type' => 'page',
                    'route' => '/hrm/succession-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Candidates'],
                        ['code' => 'manage', 'name' => 'Manage Candidates'],
                    ],
                ],
                [
                    'code' => 'talent-pool',
                    'name' => 'Talent Pool',
                    'type' => 'page',
                    'route' => '/hrm/succession-planning/talent-pool',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Talent Pool'],
                        ['code' => 'manage', 'name' => 'Manage Talent Pool'],
                    ],
                ],
            ],
        ],

        // 2.13 Career Pathing
        [
            'code' => 'career-pathing',
            'name' => 'Career Pathing',
            'description' => 'Define career progression paths and track employee development',
            'icon' => 'ArrowTrendingUpIcon',
            'route' => '/hrm/career-paths',
            'priority' => 13,
            'components' => [
                [
                    'code' => 'career-paths',
                    'name' => 'Career Paths',
                    'type' => 'page',
                    'route' => '/hrm/career-paths',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Career Paths'],
                        ['code' => 'create', 'name' => 'Create Career Path'],
                        ['code' => 'update', 'name' => 'Update Career Path'],
                        ['code' => 'delete', 'name' => 'Delete Career Path'],
                    ],
                ],
                [
                    'code' => 'career-milestones',
                    'name' => 'Career Milestones',
                    'type' => 'page',
                    'route' => '/hrm/career-paths',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Milestones'],
                        ['code' => 'manage', 'name' => 'Manage Milestones'],
                    ],
                ],
                [
                    'code' => 'employee-progressions',
                    'name' => 'Employee Progressions',
                    'type' => 'page',
                    'route' => '/hrm/career-paths',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Progressions'],
                        ['code' => 'assign', 'name' => 'Assign Career Path'],
                        ['code' => 'update', 'name' => 'Update Progression'],
                    ],
                ],
            ],
        ],

        // 2.14 360° Feedback
        [
            'code' => 'feedback-360',
            'name' => '360° Feedback',
            'description' => 'Multi-rater feedback for comprehensive performance insights',
            'icon' => 'ArrowPathIcon',
            'route' => '/hrm/feedback-360',
            'priority' => 14,
            'components' => [
                [
                    'code' => 'feedback-reviews',
                    'name' => '360° Feedback Reviews',
                    'type' => 'page',
                    'route' => '/hrm/feedback-360',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View 360° Reviews'],
                        ['code' => 'create', 'name' => 'Create 360° Review'],
                        ['code' => 'update', 'name' => 'Update 360° Review'],
                        ['code' => 'delete', 'name' => 'Delete 360° Review'],
                        ['code' => 'launch', 'name' => 'Launch 360° Review'],
                    ],
                ],
                [
                    'code' => 'feedback-responses',
                    'name' => 'Feedback Responses',
                    'type' => 'page',
                    'route' => '/hrm/feedback-360',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Responses'],
                        ['code' => 'submit', 'name' => 'Submit Feedback'],
                    ],
                ],
                [
                    'code' => 'my-pending-feedback',
                    'name' => 'My Pending Feedback',
                    'type' => 'page',
                    'route' => '/hrm/feedback-360',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pending Feedback'],
                        ['code' => 'submit', 'name' => 'Submit Feedback'],
                    ],
                ],
            ],
        ],

        // 2.15 Compensation Planning
        [
            'code' => 'compensation-planning',
            'name' => 'Compensation Planning',
            'description' => 'Salary reviews, adjustments, and compensation budget management',
            'icon' => 'BanknotesIcon',
            'route' => '/hrm/compensation-planning',
            'priority' => 15,
            'components' => [
                [
                    'code' => 'compensation-reviews',
                    'name' => 'Compensation Review Cycles',
                    'type' => 'page',
                    'route' => '/hrm/compensation-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Compensation Reviews'],
                        ['code' => 'create', 'name' => 'Create Compensation Review'],
                        ['code' => 'update', 'name' => 'Update Compensation Review'],
                        ['code' => 'delete', 'name' => 'Delete Compensation Review'],
                    ],
                ],
                [
                    'code' => 'compensation-adjustments',
                    'name' => 'Salary Adjustments',
                    'type' => 'page',
                    'route' => '/hrm/compensation-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Adjustments'],
                        ['code' => 'create', 'name' => 'Create Adjustment'],
                        ['code' => 'approve', 'name' => 'Approve Adjustment'],
                        ['code' => 'reject', 'name' => 'Reject Adjustment'],
                    ],
                ],
                [
                    'code' => 'compensation-analytics',
                    'name' => 'Compensation Analytics',
                    'type' => 'page',
                    'route' => '/hrm/compensation-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Compensation Analytics'],
                        ['code' => 'export', 'name' => 'Export Compensation Data'],
                    ],
                ],
            ],
        ],

        // 2.16 Workforce Planning
        [
            'code' => 'workforce-planning',
            'name' => 'Workforce Planning',
            'description' => 'Strategic headcount planning and workforce forecasting',
            'icon' => 'ChartBarIcon',
            'route' => '/hrm/workforce-planning',
            'priority' => 16,
            'components' => [
                [
                    'code' => 'workforce-plans',
                    'name' => 'Workforce Plans',
                    'type' => 'page',
                    'route' => '/hrm/workforce-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Workforce Plans'],
                        ['code' => 'create', 'name' => 'Create Workforce Plan'],
                        ['code' => 'update', 'name' => 'Update Workforce Plan'],
                        ['code' => 'delete', 'name' => 'Delete Workforce Plan'],
                        ['code' => 'approve', 'name' => 'Approve Workforce Plan'],
                    ],
                ],
                [
                    'code' => 'planned-positions',
                    'name' => 'Planned Positions',
                    'type' => 'page',
                    'route' => '/hrm/workforce-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Planned Positions'],
                        ['code' => 'manage', 'name' => 'Manage Positions'],
                    ],
                ],
                [
                    'code' => 'workforce-forecast',
                    'name' => 'Workforce Forecast',
                    'type' => 'page',
                    'route' => '/hrm/workforce-planning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Workforce Forecast'],
                        ['code' => 'generate', 'name' => 'Generate Forecast'],
                    ],
                ],
                [
                    'code' => 'talent-marketplace',
                    'name' => 'Internal Talent Marketplace',
                    'type' => 'page',
                    'route' => '/hrm/talent-marketplace',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Marketplace'],
                        ['code' => 'apply', 'name' => 'Apply to Opportunity'],
                        ['code' => 'manage', 'name' => 'Manage Opportunities'],
                    ],
                ],
                [
                    'code' => 'dei-analytics',
                    'name' => 'DEI Analytics',
                    'type' => 'page',
                    'route' => '/hrm/dei-analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View DEI Analytics'],
                        ['code' => 'manage', 'name' => 'Manage DEI Analytics'],
                    ],
                ],
            ],
        ],

        // 2.17 Overtime Management
        [
            'code' => 'overtime',
            'name' => 'Overtime Management',
            'description' => 'Track and manage overtime requests and records',
            'icon' => 'ClockIcon',
            'route' => '/hrm/overtime',
            'priority' => 17,
            'components' => [
                [
                    'code' => 'overtime-records',
                    'name' => 'Overtime Records',
                    'type' => 'page',
                    'route' => '/hrm/overtime',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Overtime Records'],
                        ['code' => 'create', 'name' => 'Create Overtime Record'],
                        ['code' => 'update', 'name' => 'Update Overtime Record'],
                        ['code' => 'delete', 'name' => 'Delete Overtime Record'],
                        ['code' => 'approve', 'name' => 'Approve Overtime'],
                        ['code' => 'reject', 'name' => 'Reject Overtime'],
                    ],
                ],
            ],
        ],

        // 2.18 Grievances
        [
            'code' => 'grievances',
            'name' => 'Grievances & Complaints',
            'description' => 'Employee grievance and complaint management system',
            'icon' => 'ExclamationTriangleIcon',
            'route' => '/hrm/grievances',
            'priority' => 18,
            'components' => [
                [
                    'code' => 'grievance-list',
                    'name' => 'Grievances List',
                    'type' => 'page',
                    'route' => '/hrm/grievances',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Grievances'],
                        ['code' => 'create', 'name' => 'Submit Grievance'],
                        ['code' => 'update', 'name' => 'Update Grievance'],
                        ['code' => 'delete', 'name' => 'Delete Grievance'],
                        ['code' => 'investigate', 'name' => 'Investigate Grievance'],
                        ['code' => 'resolve', 'name' => 'Resolve Grievance'],
                    ],
                ],
            ],
        ],

        // 2.19 Exit Interviews
        [
            'code' => 'exit-interviews',
            'name' => 'Exit Interviews',
            'description' => 'Conduct and analyze employee exit interviews',
            'icon' => 'ArrowRightOnRectangleIcon',
            'route' => '/hrm/exit-interviews',
            'priority' => 19,
            'components' => [
                [
                    'code' => 'exit-interview-list',
                    'name' => 'Exit Interviews',
                    'type' => 'page',
                    'route' => '/hrm/exit-interviews',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Exit Interviews'],
                        ['code' => 'create', 'name' => 'Schedule Exit Interview'],
                        ['code' => 'update', 'name' => 'Update Exit Interview'],
                        ['code' => 'delete', 'name' => 'Delete Exit Interview'],
                        ['code' => 'analyze', 'name' => 'Analyze Trends'],
                    ],
                ],
            ],
        ],

        // 2.20 Pulse Surveys
        [
            'code' => 'pulse-surveys',
            'name' => 'Pulse Surveys',
            'description' => 'Quick employee engagement and sentiment surveys',
            'icon' => 'ChartBarSquareIcon',
            'route' => '/hrm/pulse-surveys',
            'priority' => 20,
            'components' => [
                [
                    'code' => 'survey-list',
                    'name' => 'Pulse Surveys',
                    'type' => 'page',
                    'route' => '/hrm/pulse-surveys',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Surveys'],
                        ['code' => 'create', 'name' => 'Create Survey'],
                        ['code' => 'update', 'name' => 'Update Survey'],
                        ['code' => 'delete', 'name' => 'Delete Survey'],
                        ['code' => 'publish', 'name' => 'Publish Survey'],
                        ['code' => 'analyze', 'name' => 'Analyze Results'],
                    ],
                ],
            ],
        ],

        // 2.21 Dashboard
        [
            'code' => 'dashboard',
            'name' => 'HR Dashboard',
            'description' => 'HR management dashboard with key metrics and insights',
            'icon' => 'HomeIcon',
            'route' => '/hrm/dashboard',
            'priority' => 0,
            'show_in_nav' => false,
            'components' => [
                [
                    'code' => 'hr-dashboard',
                    'name' => 'HR Dashboard',
                    'type' => 'page',
                    'route' => '/hrm/dashboard',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View HR Dashboard'],
                    ],
                ],
            ],
        ],

        // 2.22 Onboarding & Offboarding
        [
            'code' => 'onboarding',
            'name' => 'Onboarding & Offboarding',
            'description' => 'Employee onboarding wizards, checklists, and offboarding processes',
            'icon' => 'UserPlusIcon',
            'route' => '/hrm/onboarding',
            'priority' => 21,
            'components' => [
                [
                    'code' => 'onboarding-list',
                    'name' => 'Onboarding',
                    'type' => 'page',
                    'route' => '/hrm/onboarding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Onboarding'],
                        ['code' => 'create', 'name' => 'Create Onboarding'],
                        ['code' => 'update', 'name' => 'Update Onboarding'],
                        ['code' => 'delete', 'name' => 'Delete Onboarding'],
                    ],
                ],
                [
                    'code' => 'offboarding-list',
                    'name' => 'Offboarding',
                    'type' => 'page',
                    'route' => '/hrm/offboarding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Offboarding'],
                        ['code' => 'create', 'name' => 'Create Offboarding'],
                        ['code' => 'update', 'name' => 'Update Offboarding'],
                        ['code' => 'delete', 'name' => 'Delete Offboarding'],
                    ],
                ],
                [
                    'code' => 'checklists',
                    'name' => 'Onboarding Checklists',
                    'type' => 'page',
                    'route' => '/hrm/checklists',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Checklists'],
                        ['code' => 'manage', 'name' => 'Manage Checklists'],
                    ],
                ],
            ],
        ],

        // 2.23 Workplace Safety
        [
            'code' => 'safety',
            'name' => 'Workplace Safety',
            'description' => 'Workplace health & safety incidents, inspections, and training',
            'icon' => 'ShieldCheckIcon',
            'route' => '/hrm/safety',
            'priority' => 22,
            'components' => [
                [
                    'code' => 'safety-incidents',
                    'name' => 'Safety Incidents',
                    'type' => 'page',
                    'route' => '/hrm/safety/incidents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Incidents'],
                        ['code' => 'create', 'name' => 'Report Incident'],
                        ['code' => 'update', 'name' => 'Update Incident'],
                        ['code' => 'delete', 'name' => 'Delete Incident'],
                        ['code' => 'resolve', 'name' => 'Resolve Incident'],
                    ],
                ],
                [
                    'code' => 'safety-inspections',
                    'name' => 'Safety Inspections',
                    'type' => 'page',
                    'route' => '/hrm/safety/inspections',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Inspections'],
                        ['code' => 'create', 'name' => 'Create Inspection'],
                        ['code' => 'update', 'name' => 'Update Inspection'],
                        ['code' => 'delete', 'name' => 'Delete Inspection'],
                    ],
                ],
                [
                    'code' => 'safety-training',
                    'name' => 'Safety Training',
                    'type' => 'page',
                    'route' => '/hrm/safety/training',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Safety Training'],
                        ['code' => 'create', 'name' => 'Create Safety Training'],
                        ['code' => 'update', 'name' => 'Update Safety Training'],
                    ],
                ],
            ],
        ],

        // 2.24 HR Documents
        [
            'code' => 'documents',
            'name' => 'HR Documents',
            'description' => 'Company-wide HR document management and employee document tracking',
            'icon' => 'DocumentTextIcon',
            'route' => '/hrm/documents',
            'priority' => 23,
            'components' => [
                [
                    'code' => 'document-list',
                    'name' => 'HR Documents',
                    'type' => 'page',
                    'route' => '/hrm/documents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Documents'],
                        ['code' => 'create', 'name' => 'Create Document'],
                        ['code' => 'update', 'name' => 'Update Document'],
                        ['code' => 'delete', 'name' => 'Delete Document'],
                        ['code' => 'verify', 'name' => 'Verify Document'],
                    ],
                ],
                [
                    'code' => 'document-categories',
                    'name' => 'Document Categories',
                    'type' => 'page',
                    'route' => '/hrm/document-categories',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Categories'],
                        ['code' => 'manage', 'name' => 'Manage Categories'],
                    ],
                ],
            ],
        ],

        // 2.27 Org Structure
        [
            'code' => 'org-structure',
            'name' => 'Org Structure',
            'description' => 'Departments, designations, grades, and work locations',
            'icon' => 'BuildingOffice2Icon',
            'route' => '/hrm/org-structure/departments',
            'priority' => 25,
            'components' => [
                [
                    'code' => 'departments',
                    'name' => 'Departments',
                    'type' => 'page',
                    'route' => '/hrm/org-structure/departments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Departments'],
                        ['code' => 'edit', 'name' => 'Create/Edit/Delete Departments'],
                    ],
                ],
                [
                    'code' => 'designations',
                    'name' => 'Designations',
                    'type' => 'page',
                    'route' => '/hrm/org-structure/designations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Designations'],
                        ['code' => 'edit', 'name' => 'Create/Edit/Delete Designations'],
                    ],
                ],
                [
                    'code' => 'grades',
                    'name' => 'Grades',
                    'type' => 'page',
                    'route' => '/hrm/org-structure/grades',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Grades'],
                        ['code' => 'edit', 'name' => 'Create/Edit/Delete Grades'],
                    ],
                ],
                [
                    'code' => 'work-locations',
                    'name' => 'Work Locations',
                    'type' => 'page',
                    'route' => '/hrm/org-structure/work-locations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Work Locations'],
                        ['code' => 'edit', 'name' => 'Create/Edit/Delete Work Locations'],
                    ],
                ],
            ],
        ],

        // 2.25 HR Settings
        [
            'code' => 'settings',
            'name' => 'HR Settings',
            'description' => 'Configuration for HR module features and policies',
            'icon' => 'CogIcon',
            'route' => '/hrm/settings',
            'priority' => 99,
            'components' => [
                [
                    'code' => 'onboarding-settings',
                    'name' => 'Onboarding Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/hr/onboarding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'update', 'name' => 'Update Settings'],
                    ],
                ],
                [
                    'code' => 'skills-settings',
                    'name' => 'Skills Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/hr/skills',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'update', 'name' => 'Update Settings'],
                    ],
                ],
                [
                    'code' => 'benefits-settings',
                    'name' => 'Benefits Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/hr/benefits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'update', 'name' => 'Update Settings'],
                    ],
                ],
                [
                    'code' => 'safety-settings',
                    'name' => 'Safety Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/hr/safety',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'update', 'name' => 'Update Settings'],
                    ],
                ],
                [
                    'code' => 'documents-settings',
                    'name' => 'Documents Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/hr/documents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'update', 'name' => 'Update Settings'],
                    ],
                ],
                [
                    'code' => 'general-settings',
                    'name' => 'General Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/general',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View General Settings'],
                        ['code' => 'update', 'name' => 'Update General Settings'],
                    ],
                ],
                [
                    'code' => 'leave-settings',
                    'name' => 'Leave Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/leave',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Leave Settings'],
                        ['code' => 'update', 'name' => 'Update Leave Settings'],
                    ],
                ],
                [
                    'code' => 'attendance-settings',
                    'name' => 'Attendance Settings',
                    'type' => 'page',
                    'route' => '/hrm/settings/attendance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Attendance Settings'],
                        ['code' => 'update', 'name' => 'Update Attendance Settings'],
                    ],
                ],
                [
                    'code' => 'task-templates',
                    'name' => 'Task Templates',
                    'type' => 'page',
                    'route' => '/hrm/settings/task-templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Task Templates'],
                        ['code' => 'manage', 'name' => 'Manage Task Templates'],
                    ],
                ],
                [
                    'code' => 'holidays',
                    'name' => 'Public Holidays',
                    'type' => 'page',
                    'route' => '/hrm/settings/holidays',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Holidays'],
                        ['code' => 'manage', 'name' => 'Manage Holidays'],
                    ],
                ],
            ],
        ],

        // 2.26 AI Analytics
        [
            'code' => 'ai-analytics',
            'name' => 'AI Analytics',
            'description' => 'AI-powered predictive HR analytics including attrition, burnout, and talent insights',
            'icon' => 'SparklesIcon',
            'route' => '/hrm/ai-analytics',
            'priority' => 24,
            'components' => [
                [
                    'code' => 'ai-dashboard',
                    'name' => 'AI Analytics Dashboard',
                    'type' => 'page',
                    'route' => '/hrm/ai-analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View AI Dashboard'],
                    ],
                ],
                [
                    'code' => 'attrition-predictions',
                    'name' => 'Attrition Predictions',
                    'type' => 'page',
                    'route' => '/hrm/ai-analytics/attrition',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Attrition Predictions'],
                        ['code' => 'run', 'name' => 'Run Predictions'],
                    ],
                ],
                [
                    'code' => 'burnout-analysis',
                    'name' => 'Burnout Risk Analysis',
                    'type' => 'page',
                    'route' => '/hrm/ai-analytics/burnout',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Burnout Risks'],
                    ],
                ],
                [
                    'code' => 'wellbeing-monitor',
                    'name' => 'Wellbeing & Burnout Monitor',
                    'type' => 'page',
                    'route' => '/hrm/wellbeing',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Wellbeing Dashboard'],
                        ['code' => 'manage', 'name' => 'Manage Interventions'],
                    ],
                ],
                [
                    'code' => 'talent-mobility',
                    'name' => 'Talent Mobility',
                    'type' => 'page',
                    'route' => '/hrm/ai-analytics/talent-mobility',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Talent Mobility'],
                    ],
                ],
                [
                    'code' => 'enps-dashboard',
                    'name' => 'eNPS Dashboard',
                    'type' => 'page',
                    'route' => '/hrm/ai-analytics/enps',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View eNPS Dashboard'],
                    ],
                ],
            ],
        ],

        // 2.28 Benefits
        [
            'code' => 'benefits',
            'name' => 'Benefits',
            'description' => 'Benefits catalog, enrollment periods, open enrollment, and employee benefits',
            'icon' => 'GiftIcon',
            'route' => '/hrm/benefits',
            'priority' => 26,
            'components' => [
                [
                    'code' => 'benefit-catalog',
                    'name' => 'Benefits Catalog',
                    'type' => 'page',
                    'route' => '/hrm/benefits/catalog',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Create/Edit/Delete'],
                    ],
                ],
                [
                    'code' => 'enrollment-periods',
                    'name' => 'Enrollment Periods',
                    'type' => 'page',
                    'route' => '/hrm/benefits/enrollment-periods',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Create/Edit'],
                        ['code' => 'activate', 'name' => 'Activate Period'],
                    ],
                ],
                [
                    'code' => 'open-enrollment',
                    'name' => 'Open Enrollment',
                    'type' => 'page',
                    'route' => '/hrm/benefits/open-enrollment',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Enroll'],
                    ],
                ],
                [
                    'code' => 'enrollments',
                    'name' => 'Employee Enrollments',
                    'type' => 'page',
                    'route' => '/hrm/benefits/enrollments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View All Enrollments'],
                    ],
                ],
            ],
        ],

        // 2.29 Events & Announcements
        [
            'code' => 'events',
            'name' => 'Events & Announcements',
            'description' => 'Company events, registrations, and announcements',
            'icon' => 'CalendarDaysIcon',
            'route' => '/hrm/events',
            'priority' => 27,
            'components' => [
                [
                    'code' => 'events-list',
                    'name' => 'Events',
                    'type' => 'page',
                    'route' => '/hrm/events',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Create/Edit/Delete'],
                        ['code' => 'publish', 'name' => 'Publish'],
                    ],
                ],
                [
                    'code' => 'registrations',
                    'name' => 'Event Registrations',
                    'type' => 'page',
                    'route' => '/hrm/events/{id}/registrations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Manage Registrations'],
                    ],
                ],
                [
                    'code' => 'announcements',
                    'name' => 'Announcements',
                    'type' => 'page',
                    'route' => '/hrm/announcements',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'edit', 'name' => 'Create/Edit'],
                    ],
                ],
            ],
        ],
    ],

    // Per-submodule licensing was removed per Audit D23: entitlement is module
    // (product) level only. Subscribing to the HRM product grants access to
    // ALL HRM submodules. The per-tenant module catalog (Audit D15) is the
    // single source of truth — see Tenant::subscribed_product_modules.
];
