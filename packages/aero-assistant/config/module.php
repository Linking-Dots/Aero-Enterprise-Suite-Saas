<?php

return [
    'code' => 'aeon',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Aeon',
    'description' => 'AEOS365 built-in AI assistant.',
    'version' => '1.0.0',
    'icon' => 'SparklesIcon',
    'category' => 'productivity',
    'priority' => 100,
    'is_core' => false,
    'is_active' => true,
    'enabled' => true,
    'route_prefix' => 'aeon',
    'min_plan' => null,
    'dependencies' => ['core'],
    'submodules' => [
        [
            'code' => 'chat',
            'name' => 'Aeon',
            'description' => 'Chat with the Aeon assistant',
            'icon' => 'SparklesIcon',
            'route' => 'aeon.index',
            'priority' => 1,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'chat_interface',
                    'name' => 'Aeon Chat',
                    'route_name' => 'aeon.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'use', 'name' => 'Use Aeon', 'is_active' => true],
                        ['code' => 'view_history', 'name' => 'View History', 'is_active' => true],
                    ],
                ],
            ],
        ],
    ],
];
