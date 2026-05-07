<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Metadata
    |--------------------------------------------------------------------------
    */

    'code' => 'assistant',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'AI Assistant',
    'description' => 'AI assistant with chat, RAG, agents, tool-use, multi-model, prompt library, EAM-aware workflows, and observability.',
    'version' => '2.0.0',
    'icon' => 'SparklesIcon',
    'category' => 'productivity',
    'priority' => 100,
    'is_core' => false,
    'is_active' => true,
    'enabled' => true,
    'route_prefix' => 'assistant',
    'min_plan' => null,
    'minimum_plan' => null,
    'license_type' => 'standard',
    'release_date' => '2024-01-01',

    'dependencies' => [
        'core',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Hierarchy
    |--------------------------------------------------------------------------
    | Defines sub-modules, components, and actions for RBAC.
    */

    'submodules' => [
        [
            'code' => 'chat',
            'name' => 'Chat Interface',
            'description' => 'Interactive chat with AI assistant',
            'icon' => 'ChatBubbleLeftRightIcon',
            'route' => 'assistant.index',
            'priority' => 1,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'chat_interface',
                    'name' => 'Chat Interface',
                    'description' => 'Send messages to AI assistant',
                    'route_name' => 'assistant.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'send', 'name' => 'Send Messages', 'is_active' => true],
                        ['code' => 'view_history', 'name' => 'View History', 'is_active' => true],
                    ],
                ],
            ],
        ],
        [
            'code' => 'conversations',
            'name' => 'Conversations',
            'description' => 'Manage conversation history',
            'icon' => 'FolderIcon',
            'route' => null,
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'conversation_management',
                    'name' => 'Conversation Management',
                    'description' => 'View and manage conversations',
                    'route_name' => null,
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Conversations', 'is_active' => true],
                        ['code' => 'archive', 'name' => 'Archive Conversations', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Conversations', 'is_active' => true],
                    ],
                ],
            ],
        ],
        [
            'code' => 'actions',
            'name' => 'Automated Actions',
            'description' => 'Perform actions through AI assistant',
            'icon' => 'BoltIcon',
            'route' => null,
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'action_execution',
                    'name' => 'Action Execution',
                    'description' => 'Execute automated tasks',
                    'route_name' => null,
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'read', 'name' => 'Read Data', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Records', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Records', 'is_active' => true],
                    ],
                ],
            ],
        ],
        [
            'code' => 'admin',
            'name' => 'Administration',
            'description' => 'Manage knowledge base and settings',
            'icon' => 'Cog6ToothIcon',
            'route' => 'assistant.admin',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'knowledge_base',
                    'name' => 'Knowledge Base Management',
                    'description' => 'Manage knowledge base indexing',
                    'route_name' => 'assistant.admin.kb',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view_stats', 'name' => 'View Statistics', 'is_active' => true],
                        ['code' => 'reindex', 'name' => 'Re-index Content', 'is_active' => true],
                        ['code' => 'manage', 'name' => 'Manage Settings', 'is_active' => true],
                        ['code' => 'upload', 'name' => 'Upload Documents', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete from KB', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'sources', 'name' => 'Data Sources', 'route_name' => 'assistant.admin.sources', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sources', 'is_active' => true],
                        ['code' => 'connect', 'name' => 'Connect Source', 'is_active' => true],
                        ['code' => 'sync', 'name' => 'Sync Source', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== AGENTS ====================
        [
            'code' => 'agents', 'name' => 'Agents & Workflows',
            'description' => 'Autonomous agents with tool-use for multi-step tasks.',
            'icon' => 'CpuChipIcon', 'route' => 'assistant.agents.index', 'priority' => 5, 'is_active' => true,
            'components' => [
                ['code' => 'agent-list', 'name' => 'Agent List', 'route_name' => 'assistant.agents.index', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Agent', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Agent', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Agent', 'is_active' => true],
                        ['code' => 'test', 'name' => 'Test Agent', 'is_active' => true],
                        ['code' => 'deploy', 'name' => 'Deploy Agent', 'is_active' => true],
                    ]],
                ['code' => 'workflows', 'name' => 'Workflows', 'route_name' => 'assistant.agents.workflows', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Workflows', 'is_active' => true]]],
                ['code' => 'eam-agent', 'name' => 'EAM Agent (Work Orders / Asset Q&A)', 'route_name' => 'assistant.agents.eam', 'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View EAM Agent', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure EAM Agent', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== TOOLS ====================
        [
            'code' => 'tools', 'name' => 'Tools & Functions',
            'description' => 'Tool registry for agents (API calls, DB queries, integrations).',
            'icon' => 'WrenchScrewdriverIcon', 'route' => 'assistant.tools.index', 'priority' => 6, 'is_active' => true,
            'components' => [
                ['code' => 'tool-registry', 'name' => 'Tool Registry', 'route_name' => 'assistant.tools.index', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Tool', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Tool', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Tool', 'is_active' => true],
                        ['code' => 'test', 'name' => 'Test Tool', 'is_active' => true],
                    ]],
                ['code' => 'permissions', 'name' => 'Tool Permissions', 'route_name' => 'assistant.tools.permissions', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Permissions', 'is_active' => true]]],
            ],
        ],

        // ==================== PROMPT LIBRARY ====================
        [
            'code' => 'prompt-library', 'name' => 'Prompt Library',
            'description' => 'Reusable prompts and templates.',
            'icon' => 'DocumentTextIcon', 'route' => 'assistant.prompts.index', 'priority' => 7, 'is_active' => true,
            'components' => [
                ['code' => 'prompts', 'name' => 'Prompts', 'route_name' => 'assistant.prompts.index', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Prompt', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'share', 'name' => 'Share Prompt', 'is_active' => true],
                    ]],
                ['code' => 'templates', 'name' => 'Prompt Templates', 'route_name' => 'assistant.prompts.templates', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates', 'is_active' => true]]],
            ],
        ],

        // ==================== MODELS & ROUTING ====================
        [
            'code' => 'models-routing', 'name' => 'Models & Routing',
            'description' => 'Multi-model configuration, routing rules, fallback.',
            'icon' => 'AdjustmentsHorizontalIcon', 'route' => 'assistant.models.index', 'priority' => 8, 'is_active' => true,
            'components' => [
                ['code' => 'models', 'name' => 'Models', 'route_name' => 'assistant.models.index', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Models', 'is_active' => true],
                        ['code' => 'enable', 'name' => 'Enable Model', 'is_active' => true],
                        ['code' => 'disable', 'name' => 'Disable Model', 'is_active' => true],
                    ]],
                ['code' => 'routing-rules', 'name' => 'Routing Rules', 'route_name' => 'assistant.models.routing', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Routing', 'is_active' => true]]],
                ['code' => 'providers', 'name' => 'Model Providers (API Keys)', 'route_name' => 'assistant.models.providers', 'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Providers', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Provider', 'is_active' => true],
                        ['code' => 'rotate-key', 'name' => 'Rotate API Key', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== GUARDRAILS & SAFETY ====================
        [
            'code' => 'guardrails', 'name' => 'Guardrails & Safety',
            'description' => 'Content moderation, PII redaction, jailbreak defense.',
            'icon' => 'ShieldCheckIcon', 'route' => 'assistant.guardrails.index', 'priority' => 9, 'is_active' => true,
            'components' => [
                ['code' => 'content-policies', 'name' => 'Content Policies', 'route_name' => 'assistant.guardrails.policies', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Policies', 'is_active' => true]]],
                ['code' => 'pii-redaction', 'name' => 'PII Redaction', 'route_name' => 'assistant.guardrails.pii', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure PII Redaction', 'is_active' => true]]],
                ['code' => 'moderation-log', 'name' => 'Moderation Log', 'route_name' => 'assistant.guardrails.log', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Moderation Log', 'is_active' => true]]],
            ],
        ],

        // ==================== OBSERVABILITY & COST ====================
        [
            'code' => 'observability', 'name' => 'Observability & Cost',
            'description' => 'Usage, latency, cost analytics, evaluation scores.',
            'icon' => 'ChartBarIcon', 'route' => 'assistant.observability.index', 'priority' => 10, 'is_active' => true,
            'components' => [
                ['code' => 'usage', 'name' => 'Usage Dashboard', 'route_name' => 'assistant.observability.usage', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Usage', 'is_active' => true], ['code' => 'export', 'name' => 'Export', 'is_active' => true]]],
                ['code' => 'cost', 'name' => 'Cost Analytics', 'route_name' => 'assistant.observability.cost', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Cost', 'is_active' => true]]],
                ['code' => 'traces', 'name' => 'Traces & Spans', 'route_name' => 'assistant.observability.traces', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Traces', 'is_active' => true]]],
                ['code' => 'evaluations', 'name' => 'Evaluations & Feedback', 'route_name' => 'assistant.observability.evaluations', 'priority' => 4, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Evaluations', 'is_active' => true],
                        ['code' => 'run', 'name' => 'Run Evaluation', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== SETTINGS ====================
        [
            'code' => 'settings', 'name' => 'Assistant Settings',
            'description' => 'Defaults, limits, access control.',
            'icon' => 'CogIcon', 'route' => 'assistant.settings.index', 'priority' => 99, 'is_active' => true,
            'components' => [
                ['code' => 'defaults', 'name' => 'Default Model / Temperature', 'route_name' => 'assistant.settings.defaults', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Defaults', 'is_active' => true]]],
                ['code' => 'limits', 'name' => 'Rate Limits & Quotas', 'route_name' => 'assistant.settings.limits', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Limits', 'is_active' => true]]],
                ['code' => 'general', 'name' => 'General Settings', 'route_name' => 'assistant.settings.general', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Settings', 'is_active' => true], ['code' => 'update', 'name' => 'Update Settings', 'is_active' => true]]],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EAM Integration Map
    |--------------------------------------------------------------------------
    */
    'eam_integration' => [
        'provides' => [
            'assistant.eam_agent' => 'agents.eam-agent',
            'assistant.tools' => 'tools.tool-registry',
            'assistant.knowledge_base' => 'admin.knowledge_base',
        ],
        'consumes' => [
            'eam.work_orders' => 'aero-eam',
            'eam.asset_registry' => 'aero-eam',
            'dms.asset_manuals' => 'aero-dms',
            'iot.asset_telemetry' => 'aero-iot',
            'quality.standards' => 'aero-quality',
            'compliance.policies' => 'aero-compliance',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'assistant_admin_role' => 'assistant-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'assistant-access'],
    ],
];
