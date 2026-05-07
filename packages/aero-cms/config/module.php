<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CMS Module Configuration (HRMAC Integration)
    |--------------------------------------------------------------------------
    |
    | This file defines the CMS module structure for HRMAC.
    | The CMS module provides page building capabilities for platform admins.
    |
    | Hierarchy: Module → SubModule → Component → Action
    |
    | Scope: 'infrastructure' - Infrastructure layer, not a marketplace module
    | CMS is a platform feature for marketing/help center, not a tenant module.
    |
    */

    'code' => 'cms',
    'schema_version' => '2.0',
    'scope' => 'infrastructure',
    'name' => 'Content Management Infrastructure',
    'description' => 'Visual page builder with HeroUI blocks for public pages, marketing, and help center. Platform infrastructure, not a tenant-purchasable module.',
    'icon' => 'DocumentTextIcon',
    'route_prefix' => '/admin/cms',
    'category' => 'infrastructure',
    'priority' => 50,
    'is_core' => false,
    'is_active' => true,
    'version' => '1.0.0',
    'min_plan' => null,
    'license_type' => 'platform',
    'dependencies' => ['platform'],
    'release_date' => '2026-01-16',
    'enabled' => true,
    'marketplace_visible' => false,

    'features' => [
        'dashboard' => true,
        'page_builder' => true,
        'block_library' => true,
        'media_library' => true,
        'page_templates' => true,
        'seo_settings' => true,
        'page_versioning' => true,
        'blog_posts' => true,
        'custom_post_types' => true,
        'taxonomy_categories' => true,
        'navigation_menus' => true,
        'forms_builder' => true,
        'redirects' => true,
        'ab_testing' => true,
        'multi_language' => true,
        'comments_reviews' => true,
        'scheduled_publish' => true,
        'approval_workflows' => true,
        'cdn_optimization' => true,
        'sitemap' => true,
        'robots_txt' => true,
        'headless_api' => true,
        'analytics' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [
        /*
        |--------------------------------------------------------------------------
        | 1. Pages Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'pages',
            'name' => 'Pages',
            'description' => 'Create and manage CMS pages',
            'icon' => 'DocumentDuplicateIcon',
            'route' => '/admin/cms/pages',
            'priority' => 1,

            'components' => [
                [
                    'code' => 'page_list',
                    'name' => 'All Pages',
                    'route' => '/admin/cms/pages',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pages'],
                        ['code' => 'create', 'name' => 'Create Page'],
                        ['code' => 'edit', 'name' => 'Edit Page'],
                        ['code' => 'delete', 'name' => 'Delete Page'],
                        ['code' => 'publish', 'name' => 'Publish Page'],
                    ],
                ],
                [
                    'code' => 'page_editor',
                    'name' => 'Page Editor',
                    'route' => '/admin/cms/pages/:id/edit',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Editor'],
                        ['code' => 'edit_blocks', 'name' => 'Edit Blocks'],
                        ['code' => 'reorder_blocks', 'name' => 'Reorder Blocks'],
                        ['code' => 'edit_settings', 'name' => 'Edit Page Settings'],
                        ['code' => 'preview', 'name' => 'Preview Page'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2. Block Library
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'blocks',
            'name' => 'Blocks',
            'description' => 'Manage block library and templates',
            'icon' => 'CubeIcon',
            'route' => '/admin/cms/blocks',
            'priority' => 2,

            'components' => [
                [
                    'code' => 'block_templates',
                    'name' => 'Block Templates',
                    'route' => '/admin/cms/blocks/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'edit', 'name' => 'Edit Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                    ],
                ],
                [
                    'code' => 'global_blocks',
                    'name' => 'Global Blocks',
                    'route' => '/admin/cms/blocks/global',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Global Blocks'],
                        ['code' => 'create', 'name' => 'Create Global Block'],
                        ['code' => 'edit', 'name' => 'Edit Global Block'],
                        ['code' => 'delete', 'name' => 'Delete Global Block'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 3. Media Library
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'media',
            'name' => 'Media',
            'description' => 'Manage images and files',
            'icon' => 'PhotoIcon',
            'route' => '/admin/cms/media',
            'priority' => 3,

            'components' => [
                [
                    'code' => 'media_library',
                    'name' => 'Media Library',
                    'route' => '/admin/cms/media',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Media'],
                        ['code' => 'upload', 'name' => 'Upload Media'],
                        ['code' => 'edit', 'name' => 'Edit Media'],
                        ['code' => 'delete', 'name' => 'Delete Media'],
                        ['code' => 'organize', 'name' => 'Organize Folders'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 4. Page Templates
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'templates',
            'name' => 'Templates',
            'description' => 'Pre-built page templates',
            'icon' => 'RectangleStackIcon',
            'route' => '/admin/cms/templates',
            'priority' => 4,

            'components' => [
                [
                    'code' => 'page_templates',
                    'name' => 'Page Templates',
                    'route' => '/admin/cms/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'edit', 'name' => 'Edit Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                        ['code' => 'apply', 'name' => 'Apply Template'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 5. SEO & Settings
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'settings',
            'name' => 'Settings',
            'description' => 'CMS configuration and SEO',
            'icon' => 'Cog6ToothIcon',
            'route' => '/admin/cms/settings',
            'priority' => 5,

            'components' => [
                [
                    'code' => 'cms_settings',
                    'name' => 'General Settings',
                    'route' => '/admin/cms/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'edit', 'name' => 'Edit Settings'],
                    ],
                ],
                [
                    'code' => 'seo_defaults',
                    'name' => 'SEO Defaults',
                    'route' => '/admin/cms/settings/seo',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SEO Settings'],
                        ['code' => 'edit', 'name' => 'Edit SEO Settings'],
                    ],
                ],
            ],
        ],

        // ==================== 6. BLOG & POSTS ====================
        [
            'code' => 'blog',
            'name' => 'Blog & Posts',
            'description' => 'Blog posts with categories, tags, authors, and scheduling',
            'icon' => 'NewspaperIcon',
            'route' => '/admin/cms/blog',
            'priority' => 6,
            'components' => [
                [
                    'code' => 'posts', 'name' => 'Posts', 'route' => '/admin/cms/blog/posts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Posts'],
                        ['code' => 'create', 'name' => 'Create Post'],
                        ['code' => 'edit', 'name' => 'Edit Post'],
                        ['code' => 'delete', 'name' => 'Delete Post'],
                        ['code' => 'publish', 'name' => 'Publish Post'],
                        ['code' => 'schedule', 'name' => 'Schedule Post'],
                        ['code' => 'feature', 'name' => 'Feature Post'],
                    ],
                ],
                [
                    'code' => 'categories', 'name' => 'Categories', 'route' => '/admin/cms/blog/categories',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Categories']],
                ],
                [
                    'code' => 'tags', 'name' => 'Tags', 'route' => '/admin/cms/blog/tags',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Tags']],
                ],
                [
                    'code' => 'authors', 'name' => 'Authors', 'route' => '/admin/cms/blog/authors',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Authors']],
                ],
            ],
        ],

        // ==================== 7. NAVIGATION MENUS ====================
        [
            'code' => 'navigation',
            'name' => 'Navigation Menus',
            'description' => 'Header, footer, and custom menus',
            'icon' => 'Bars3Icon',
            'route' => '/admin/cms/navigation',
            'priority' => 7,
            'components' => [
                [
                    'code' => 'menus', 'name' => 'Menus', 'route' => '/admin/cms/navigation/menus',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Menus'],
                        ['code' => 'create', 'name' => 'Create Menu'],
                        ['code' => 'edit', 'name' => 'Edit Menu'],
                        ['code' => 'delete', 'name' => 'Delete Menu'],
                        ['code' => 'reorder', 'name' => 'Reorder Items'],
                    ],
                ],
            ],
        ],

        // ==================== 8. FORMS BUILDER ====================
        [
            'code' => 'forms',
            'name' => 'Forms',
            'description' => 'Drag-drop forms, submissions, spam filtering',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/admin/cms/forms',
            'priority' => 8,
            'components' => [
                [
                    'code' => 'form_builder', 'name' => 'Form Builder', 'route' => '/admin/cms/forms',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Forms'],
                        ['code' => 'create', 'name' => 'Create Form'],
                        ['code' => 'edit', 'name' => 'Edit Form'],
                        ['code' => 'delete', 'name' => 'Delete Form'],
                        ['code' => 'publish', 'name' => 'Publish Form'],
                    ],
                ],
                [
                    'code' => 'submissions', 'name' => 'Form Submissions', 'route' => '/admin/cms/forms/submissions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Submissions'],
                        ['code' => 'export', 'name' => 'Export Submissions'],
                        ['code' => 'delete', 'name' => 'Delete Submission'],
                    ],
                ],
            ],
        ],

        // ==================== 9. REDIRECTS ====================
        [
            'code' => 'redirects',
            'name' => 'Redirects',
            'description' => '301/302 redirects, bulk import, broken link monitor',
            'icon' => 'ArrowUturnRightIcon',
            'route' => '/admin/cms/redirects',
            'priority' => 9,
            'components' => [
                [
                    'code' => 'redirect_list', 'name' => 'Redirect Rules', 'route' => '/admin/cms/redirects',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Redirects'],
                        ['code' => 'create', 'name' => 'Create Redirect'],
                        ['code' => 'edit', 'name' => 'Edit Redirect'],
                        ['code' => 'delete', 'name' => 'Delete Redirect'],
                        ['code' => 'import', 'name' => 'Bulk Import'],
                    ],
                ],
                [
                    'code' => 'broken_links', 'name' => 'Broken Link Monitor', 'route' => '/admin/cms/redirects/broken',
                    'actions' => [['code' => 'view', 'name' => 'View Broken Links'], ['code' => 'scan', 'name' => 'Run Scan']],
                ],
            ],
        ],

        // ==================== 10. A/B TESTING ====================
        [
            'code' => 'ab_testing',
            'name' => 'A/B Testing',
            'description' => 'Experiments and variants for pages/blocks',
            'icon' => 'BeakerIcon',
            'route' => '/admin/cms/ab-testing',
            'priority' => 10,
            'components' => [
                [
                    'code' => 'experiments', 'name' => 'Experiments', 'route' => '/admin/cms/ab-testing',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Experiments'],
                        ['code' => 'create', 'name' => 'Create Experiment'],
                        ['code' => 'start', 'name' => 'Start Experiment'],
                        ['code' => 'stop', 'name' => 'Stop Experiment'],
                        ['code' => 'declare-winner', 'name' => 'Declare Winner'],
                    ],
                ],
            ],
        ],

        // ==================== 11. MULTI-LANGUAGE ====================
        [
            'code' => 'multi_language',
            'name' => 'Multi-Language',
            'description' => 'Language versions, translations, hreflang',
            'icon' => 'LanguageIcon',
            'route' => '/admin/cms/i18n',
            'priority' => 11,
            'components' => [
                [
                    'code' => 'languages', 'name' => 'Languages', 'route' => '/admin/cms/i18n/languages',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Languages']],
                ],
                [
                    'code' => 'translations', 'name' => 'Translations', 'route' => '/admin/cms/i18n/translations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Translations'],
                        ['code' => 'edit', 'name' => 'Edit Translation'],
                        ['code' => 'auto-translate', 'name' => 'Auto-Translate (AI)'],
                    ],
                ],
            ],
        ],

        // ==================== 12. COMMENTS & REVIEWS ====================
        [
            'code' => 'comments',
            'name' => 'Comments & Reviews',
            'description' => 'Moderate comments, spam filter, reviews',
            'icon' => 'ChatBubbleOvalLeftIcon',
            'route' => '/admin/cms/comments',
            'priority' => 12,
            'components' => [
                [
                    'code' => 'comment_list', 'name' => 'All Comments', 'route' => '/admin/cms/comments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Comments'],
                        ['code' => 'approve', 'name' => 'Approve Comment'],
                        ['code' => 'reject', 'name' => 'Reject Comment'],
                        ['code' => 'delete', 'name' => 'Delete Comment'],
                        ['code' => 'spam', 'name' => 'Mark as Spam'],
                    ],
                ],
            ],
        ],

        // ==================== 13. APPROVAL WORKFLOWS ====================
        [
            'code' => 'approval_workflows',
            'name' => 'Approval Workflows',
            'description' => 'Editorial workflows for content approval',
            'icon' => 'CheckCircleIcon',
            'route' => '/admin/cms/approvals',
            'priority' => 13,
            'components' => [
                [
                    'code' => 'approval_list', 'name' => 'Pending Approvals', 'route' => '/admin/cms/approvals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pending'],
                        ['code' => 'approve', 'name' => 'Approve Content'],
                        ['code' => 'reject', 'name' => 'Reject Content'],
                    ],
                ],
                [
                    'code' => 'workflows_config', 'name' => 'Workflow Configuration', 'route' => '/admin/cms/approvals/config',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Workflows']],
                ],
            ],
        ],

        // ==================== 14. SITEMAP & ROBOTS ====================
        [
            'code' => 'sitemap_robots',
            'name' => 'Sitemap & Robots',
            'description' => 'XML sitemap, robots.txt, search indexing',
            'icon' => 'MapIcon',
            'route' => '/admin/cms/sitemap',
            'priority' => 14,
            'components' => [
                [
                    'code' => 'sitemap', 'name' => 'Sitemap', 'route' => '/admin/cms/sitemap',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sitemap'],
                        ['code' => 'regenerate', 'name' => 'Regenerate Sitemap'],
                        ['code' => 'submit-gsc', 'name' => 'Submit to Google Search Console'],
                    ],
                ],
                [
                    'code' => 'robots', 'name' => 'Robots.txt', 'route' => '/admin/cms/robots',
                    'actions' => [['code' => 'view', 'name' => 'View Robots.txt'], ['code' => 'edit', 'name' => 'Edit Robots.txt']],
                ],
            ],
        ],

        // ==================== 15. HEADLESS API ====================
        [
            'code' => 'headless_api',
            'name' => 'Headless API',
            'description' => 'GraphQL / REST endpoints for headless delivery',
            'icon' => 'CommandLineIcon',
            'route' => '/admin/cms/api',
            'priority' => 15,
            'components' => [
                [
                    'code' => 'api_endpoints', 'name' => 'API Endpoints', 'route' => '/admin/cms/api/endpoints',
                    'actions' => [['code' => 'view', 'name' => 'View Endpoints']],
                ],
                [
                    'code' => 'api_keys', 'name' => 'API Keys', 'route' => '/admin/cms/api/keys',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Keys'],
                        ['code' => 'create', 'name' => 'Create Key'],
                        ['code' => 'revoke', 'name' => 'Revoke Key'],
                    ],
                ],
                [
                    'code' => 'webhooks', 'name' => 'Webhooks', 'route' => '/admin/cms/api/webhooks',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Webhooks']],
                ],
            ],
        ],

        // ==================== 16. ANALYTICS ====================
        [
            'code' => 'analytics',
            'name' => 'CMS Analytics',
            'description' => 'Page views, conversions, bounce, top content',
            'icon' => 'ChartBarIcon',
            'route' => '/admin/cms/analytics',
            'priority' => 16,
            'components' => [
                [
                    'code' => 'page_analytics', 'name' => 'Page Analytics', 'route' => '/admin/cms/analytics',
                    'actions' => [['code' => 'view', 'name' => 'View Analytics'], ['code' => 'export', 'name' => 'Export']],
                ],
                [
                    'code' => 'seo_analytics', 'name' => 'SEO Analytics', 'route' => '/admin/cms/analytics/seo',
                    'actions' => [['code' => 'view', 'name' => 'View SEO Analytics']],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EAM Integration Map (CMS has no direct EAM tie — provided empty for consistency)
    |--------------------------------------------------------------------------
    */
    'eam_integration' => [
        'provides' => [],
        'consumes' => [],
    ],

    'access_control' => [
        'super_admin_role' => 'platform-super-admin',
        'cms_admin_role' => 'cms-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['platform-module-access', 'platform-role-access', 'cms-access'],
    ],
];
