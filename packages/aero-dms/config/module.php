<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Management System Module
    |--------------------------------------------------------------------------
    |
    | Complete document management system with version control, approval workflows,
    | and secure document sharing.
    |
    */

    'code' => 'dms',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Document Management',
    'description' => 'Enterprise DMS: repository, versioning, approvals, e-sign, OCR, retention, controlled docs, asset manuals (EAM), and collaboration.',
    'version' => '2.0.0',
    'category' => 'business',
    'icon' => 'DocumentIcon',
    'priority' => 20,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('DMS_MODULE_ENABLED', true),
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => '/dms',

    'features' => [
        'dashboard' => true,
        'repository' => true,
        'folders_taxonomy' => true,
        'version_control' => true,
        'check_in_out' => true,
        'approvals_workflow' => true,
        'sharing' => true,
        'external_sharing' => true,
        'e_signature' => true,
        'ocr_search' => true,
        'full_text_search' => true,
        'tagging_metadata' => true,
        'retention_policies' => true,
        'records_management' => true,
        'controlled_documents' => true, // SOPs, ISO
        'asset_manuals' => true, // EAM: asset docs/manuals/drawings
        'cad_drawing_viewer' => true, // EAM: drawings for assets
        'templates' => true,
        'categories' => true,
        'collaboration' => true,
        'annotations_redaction' => true,
        'watermarking' => true,
        'rights_management' => true,
        'audit_trail' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [

        // ==================== 0. DASHBOARD ====================
        [
            'code' => 'dashboard', 'name' => 'DMS Dashboard',
            'description' => 'Recent docs, pending approvals, storage overview',
            'icon' => 'HomeIcon', 'route' => '/dms/dashboard', 'priority' => 0,
            'is_active' => true,
            'components' => [
                ['code' => 'dms-dashboard', 'name' => 'DMS Dashboard', 'type' => 'page', 'route' => '/dms/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
            ],
        ],

        // ==================== 1. DOCUMENT REPOSITORY ====================
        [
            'code' => 'documents',
            'name' => 'Document Repository',
            'description' => 'Central document storage with organization and categorization.',
            'icon' => 'FolderIcon',
            'route' => '/dms/documents',
            'priority' => 1,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'document-list',
                    'name' => 'Document Browser',
                    'description' => 'Browse and search documents.',
                    'route' => '/dms/documents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Documents', 'is_default' => true],
                        ['code' => 'upload', 'name' => 'Upload Document', 'is_default' => false],
                        ['code' => 'download', 'name' => 'Download Document', 'is_default' => true],
                        ['code' => 'delete', 'name' => 'Delete Document', 'is_default' => false],
                    ],
                ],
                [
                    'code' => 'document-detail',
                    'name' => 'Document Details',
                    'description' => 'View and edit document details.',
                    'route' => '/dms/documents/{id}',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Details', 'is_default' => true],
                        ['code' => 'edit', 'name' => 'Edit Metadata', 'is_default' => false],
                        ['code' => 'share', 'name' => 'Share Document', 'is_default' => false],
                    ],
                ],
            ],
        ],

        // ==================== 2. VERSION CONTROL ====================
        [
            'code' => 'versions',
            'name' => 'Version Control',
            'description' => 'Document versioning and history tracking.',
            'icon' => 'ClockIcon',
            'route' => '/dms/versions',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'version-history',
                    'name' => 'Version History',
                    'description' => 'View document version history.',
                    'route' => '/dms/documents/{id}/versions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Versions', 'is_default' => true],
                        ['code' => 'restore', 'name' => 'Restore Version', 'is_default' => false],
                        ['code' => 'compare', 'name' => 'Compare Versions', 'is_default' => false],
                    ],
                ],
            ],
        ],

        // ==================== 3. APPROVAL WORKFLOWS ====================
        [
            'code' => 'approvals',
            'name' => 'Approval Workflows',
            'description' => 'Document approval and review workflows.',
            'icon' => 'CheckCircleIcon',
            'route' => '/dms/approvals',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'pending-approvals',
                    'name' => 'Pending Approvals',
                    'description' => 'Documents awaiting approval.',
                    'route' => '/dms/approvals/pending',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pending', 'is_default' => true],
                        ['code' => 'approve', 'name' => 'Approve Document', 'is_default' => false],
                        ['code' => 'reject', 'name' => 'Reject Document', 'is_default' => false],
                    ],
                ],
                [
                    'code' => 'workflow-settings',
                    'name' => 'Workflow Configuration',
                    'description' => 'Configure approval workflows.',
                    'route' => '/dms/approvals/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Workflows', 'is_default' => true],
                        ['code' => 'create', 'name' => 'Create Workflow', 'is_default' => false],
                        ['code' => 'edit', 'name' => 'Edit Workflow', 'is_default' => false],
                    ],
                ],
            ],
        ],

        // ==================== 4. DOCUMENT SHARING ====================
        [
            'code' => 'sharing',
            'name' => 'Document Sharing',
            'description' => 'Share documents internally and externally.',
            'icon' => 'ShareIcon',
            'route' => '/dms/sharing',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'shared-with-me',
                    'name' => 'Shared With Me',
                    'description' => 'Documents shared with you.',
                    'route' => '/dms/sharing/received',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Shared Documents', 'is_default' => true],
                    ],
                ],
                [
                    'code' => 'my-shares',
                    'name' => 'My Shares',
                    'description' => 'Documents you have shared.',
                    'route' => '/dms/sharing/sent',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View My Shares', 'is_default' => true],
                        ['code' => 'revoke', 'name' => 'Revoke Access', 'is_default' => false],
                    ],
                ],
            ],
        ],

        // ==================== 5. CATEGORIES & TEMPLATES ====================
        [
            'code' => 'settings',
            'name' => 'DMS Settings',
            'description' => 'Document categories, templates, and settings.',
            'icon' => 'CogIcon',
            'route' => '/dms/settings',
            'priority' => 5,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'categories',
                    'name' => 'Document Categories',
                    'description' => 'Manage document categories.',
                    'route' => '/dms/settings/categories',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Categories', 'is_default' => true],
                        ['code' => 'create', 'name' => 'Create Category', 'is_default' => false],
                        ['code' => 'edit', 'name' => 'Edit Category', 'is_default' => false],
                        ['code' => 'delete', 'name' => 'Delete Category', 'is_default' => false],
                    ],
                ],
                [
                    'code' => 'templates',
                    'name' => 'Document Templates',
                    'description' => 'Manage document templates.',
                    'route' => '/dms/settings/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates', 'is_default' => true],
                        ['code' => 'create', 'name' => 'Create Template', 'is_default' => false],
                        ['code' => 'edit', 'name' => 'Edit Template', 'is_default' => false],
                        ['code' => 'delete', 'name' => 'Delete Template', 'is_default' => false],
                    ],
                ],
                [
                    'code' => 'retention-policies', 'name' => 'Retention Policies', 'route' => '/dms/settings/retention',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Retention Policies']],
                ],
                [
                    'code' => 'numbering', 'name' => 'Document Numbering', 'route' => '/dms/settings/numbering',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Numbering']],
                ],
                [
                    'code' => 'general', 'name' => 'General Settings', 'route' => '/dms/settings/general',
                    'actions' => [['code' => 'view', 'name' => 'View Settings'], ['code' => 'update', 'name' => 'Update Settings']],
                ],
            ],
        ],

        // ==================== 6. E-SIGNATURE ====================
        [
            'code' => 'e-signature',
            'name' => 'E-Signature',
            'description' => 'Digital signing workflow, signer order, audit trail.',
            'icon' => 'PencilSquareIcon',
            'route' => '/dms/e-signature',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                ['code' => 'signing-envelopes', 'name' => 'Signing Envelopes', 'route' => '/dms/e-signature/envelopes',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Envelopes'],
                        ['code' => 'create', 'name' => 'Create Envelope'],
                        ['code' => 'send', 'name' => 'Send for Signing'],
                        ['code' => 'void', 'name' => 'Void Envelope'],
                        ['code' => 'remind', 'name' => 'Send Reminder'],
                    ]],
                ['code' => 'signed-documents', 'name' => 'Signed Documents', 'route' => '/dms/e-signature/signed',
                    'actions' => [['code' => 'view', 'name' => 'View Signed Docs'], ['code' => 'download', 'name' => 'Download with Audit Trail']]],
                ['code' => 'signature-templates', 'name' => 'Signature Templates', 'route' => '/dms/e-signature/templates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates']]],
            ],
        ],

        // ==================== 7. OCR & SEARCH ====================
        [
            'code' => 'ocr-search',
            'name' => 'OCR & Search',
            'description' => 'OCR indexing, full-text search, semantic search.',
            'icon' => 'MagnifyingGlassIcon',
            'route' => '/dms/ocr-search',
            'priority' => 7,
            'is_active' => true,
            'components' => [
                ['code' => 'ocr-queue', 'name' => 'OCR Queue', 'route' => '/dms/ocr/queue',
                    'actions' => [['code' => 'view', 'name' => 'View OCR Queue'], ['code' => 'reprocess', 'name' => 'Reprocess Document']]],
                ['code' => 'search', 'name' => 'Full-Text / Semantic Search', 'route' => '/dms/search',
                    'actions' => [['code' => 'view', 'name' => 'Use Search']]],
                ['code' => 'saved-searches', 'name' => 'Saved Searches', 'route' => '/dms/search/saved',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Saved Searches']]],
            ],
        ],

        // ==================== 8. RETENTION & RECORDS ====================
        [
            'code' => 'retention-records',
            'name' => 'Retention & Records Management',
            'description' => 'Legal holds, retention policies, disposition.',
            'icon' => 'ArchiveBoxIcon',
            'route' => '/dms/retention',
            'priority' => 8,
            'is_active' => true,
            'components' => [
                ['code' => 'legal-holds', 'name' => 'Legal Holds', 'route' => '/dms/retention/legal-holds',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Legal Holds'],
                        ['code' => 'place', 'name' => 'Place Legal Hold'],
                        ['code' => 'release', 'name' => 'Release Legal Hold'],
                    ]],
                ['code' => 'retention-schedule', 'name' => 'Retention Schedule', 'route' => '/dms/retention/schedule',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Schedule']]],
                ['code' => 'disposition', 'name' => 'Disposition (Purge / Archive)', 'route' => '/dms/retention/disposition',
                    'actions' => [['code' => 'archive', 'name' => 'Archive Document'], ['code' => 'purge', 'name' => 'Purge Document']]],
            ],
        ],

        // ==================== 9. CONTROLLED DOCUMENTS (SOP / ISO) ====================
        [
            'code' => 'controlled-docs',
            'name' => 'Controlled Documents',
            'description' => 'SOPs, work instructions, controlled distribution (ISO 9001).',
            'icon' => 'DocumentCheckIcon',
            'route' => '/dms/controlled',
            'priority' => 9,
            'is_active' => true,
            'components' => [
                ['code' => 'controlled-list', 'name' => 'Controlled Documents', 'route' => '/dms/controlled',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Controlled Docs'],
                        ['code' => 'create', 'name' => 'Create Controlled Doc'],
                        ['code' => 'revise', 'name' => 'Revise Doc'],
                        ['code' => 'approve', 'name' => 'Approve Revision'],
                        ['code' => 'distribute', 'name' => 'Distribute Doc'],
                        ['code' => 'acknowledge', 'name' => 'Capture Acknowledgment'],
                    ]],
                ['code' => 'training-linkage', 'name' => 'Training Linkage', 'route' => '/dms/controlled/training',
                    'actions' => [['code' => 'link', 'name' => 'Link Training Requirement']]],
            ],
        ],

        // ==================== 10. ASSET DOCUMENTS (EAM) ====================
        [
            'code' => 'asset-documents',
            'name' => 'Asset Documentation (EAM)',
            'description' => 'Asset manuals, O&M docs, drawings, warranties, certificates.',
            'icon' => 'CubeIcon',
            'route' => '/dms/asset-docs',
            'priority' => 10,
            'is_active' => true,
            'components' => [
                ['code' => 'asset-manuals', 'name' => 'Asset Manuals', 'route' => '/dms/asset-docs/manuals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Manuals'],
                        ['code' => 'upload', 'name' => 'Upload Manual'],
                        ['code' => 'link-asset', 'name' => 'Link to EAM Asset'],
                    ]],
                ['code' => 'drawings-cad', 'name' => 'Drawings & CAD', 'route' => '/dms/asset-docs/drawings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Drawings'],
                        ['code' => 'upload', 'name' => 'Upload Drawing'],
                        ['code' => 'version', 'name' => 'Version Drawing'],
                        ['code' => 'markup', 'name' => 'Markup Drawing'],
                    ]],
                ['code' => 'as-built', 'name' => 'As-Built Documents', 'route' => '/dms/asset-docs/as-built',
                    'actions' => [['code' => 'manage', 'name' => 'Manage As-Built Docs']]],
                ['code' => 'certificates', 'name' => 'Asset Certificates & Warranties', 'route' => '/dms/asset-docs/certificates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Certificates'],
                        ['code' => 'upload', 'name' => 'Upload Certificate'],
                        ['code' => 'remind', 'name' => 'Send Expiry Reminder'],
                    ]],
                ['code' => 'om-documents', 'name' => 'O&M Manuals', 'route' => '/dms/asset-docs/om',
                    'actions' => [['code' => 'manage', 'name' => 'Manage O&M Docs']]],
            ],
        ],

        // ==================== 11. COLLABORATION ====================
        [
            'code' => 'collaboration',
            'name' => 'Collaboration',
            'description' => 'Co-editing, comments, annotations, redaction, watermarks.',
            'icon' => 'ChatBubbleLeftRightIcon',
            'route' => '/dms/collaboration',
            'priority' => 11,
            'is_active' => true,
            'components' => [
                ['code' => 'co-editing', 'name' => 'Co-Editing', 'route' => '/dms/collaboration/co-editing',
                    'actions' => [['code' => 'edit', 'name' => 'Edit Document']]],
                ['code' => 'annotations', 'name' => 'Annotations & Comments', 'route' => '/dms/collaboration/annotations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Annotations'],
                        ['code' => 'annotate', 'name' => 'Add Annotation'],
                        ['code' => 'comment', 'name' => 'Add Comment'],
                    ]],
                ['code' => 'redaction', 'name' => 'Redaction', 'route' => '/dms/collaboration/redaction',
                    'actions' => [['code' => 'redact', 'name' => 'Redact Document']]],
                ['code' => 'watermarking', 'name' => 'Watermarking', 'route' => '/dms/collaboration/watermark',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Watermark']]],
            ],
        ],

        // ==================== 12. AUDIT TRAIL ====================
        [
            'code' => 'audit-trail',
            'name' => 'Audit Trail',
            'description' => 'Document access, modification, and download log.',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/dms/audit',
            'priority' => 12,
            'is_active' => true,
            'components' => [
                ['code' => 'audit-log', 'name' => 'Audit Log', 'route' => '/dms/audit',
                    'actions' => [['code' => 'view', 'name' => 'View Audit Log'], ['code' => 'export', 'name' => 'Export Audit Log']]],
            ],
        ],

        // ==================== 13. INTEGRATIONS ====================
        [
            'code' => 'integrations',
            'name' => 'Integrations',
            'description' => 'Cloud storage (S3, GCS, Azure), Office/Google, e-sign providers',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => '/dms/integrations',
            'priority' => 13,
            'is_active' => true,
            'components' => [
                ['code' => 'cloud-storage', 'name' => 'Cloud Storage', 'route' => '/dms/integrations/cloud',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Storage']]],
                ['code' => 'office-suite', 'name' => 'Office / Google Workspace', 'route' => '/dms/integrations/office',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Office']]],
                ['code' => 'esign-providers', 'name' => 'E-Sign Providers (DocuSign, Adobe Sign)', 'route' => '/dms/integrations/esign',
                    'actions' => [['code' => 'configure', 'name' => 'Configure E-Sign']]],
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
            'dms.asset_manuals' => 'asset-documents.asset-manuals',
            'dms.drawings_cad' => 'asset-documents.drawings-cad',
            'dms.as_built' => 'asset-documents.as-built',
            'dms.asset_certificates' => 'asset-documents.certificates',
            'dms.om_docs' => 'asset-documents.om-documents',
            'dms.controlled_docs' => 'controlled-docs.controlled-list',
            'dms.e_signature' => 'e-signature.signing-envelopes',
            'dms.retention' => 'retention-records.retention-schedule',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'eam.work_order_docs' => 'aero-eam',
            'quality.iso_documents' => 'aero-quality',
            'project.project_files' => 'aero-project',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'dms_admin_role' => 'dms-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'dms-access'],
    ],
];
