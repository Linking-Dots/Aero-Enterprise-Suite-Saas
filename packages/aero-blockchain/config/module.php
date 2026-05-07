<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blockchain Module
    |--------------------------------------------------------------------------
    |
    | Manages blockchain networks, wallets, transactions, smart contracts,
    | tokens, consensus nodes, and analytics.
    |
    */

    'code' => 'blockchain',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Blockchain Management',
    'description' => 'Enterprise blockchain: networks, wallets, contracts, tokens/NFTs, DeFi, bridges, asset provenance (EAM), supply chain traceability, audit chain, identity.',
    'version' => '2.0.0',
    'category' => 'business',
    'icon' => 'CubeTransparentIcon',
    'priority' => 25,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('BLOCKCHAIN_MODULE_ENABLED', true),
    'min_plan' => 'enterprise',
    'minimum_plan' => 'enterprise',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => 'blockchain',

    'features' => [
        'dashboard' => true,
        'networks' => true,
        'wallets' => true,
        'transactions' => true,
        'smart_contracts' => true,
        'tokens' => true,
        'nfts' => true,
        'defi' => true,
        'consensus' => true,
        'block_explorer' => true,
        'analytics' => true,
        'asset_provenance' => true, // EAM
        'supply_chain_trace' => true, // EAM
        'audit_chain' => true, // EAM / compliance
        'did_identity' => true,
        'oracles' => true,
        'bridges' => true,
        'gas_fee_management' => true,
        'custody' => true,
        'compliance_kyc' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [

        // ==================== BLOCKCHAIN DASHBOARD ====================
        [
            'code' => 'blockchain-dashboard',
            'name' => 'Blockchain Dashboard',
            'description' => 'Overview of blockchain networks, wallets, and transaction metrics.',
            'icon' => 'ChartPieIcon',
            'route' => 'blockchain.dashboard',
            'priority' => 1,
            'is_active' => true,
            'components' => [],
        ],

        // ==================== NETWORKS ====================
        [
            'code' => 'networks',
            'name' => 'Blockchain Networks',
            'description' => 'Manage blockchain network connections and configurations.',
            'icon' => 'GlobeAltIcon',
            'route' => 'blockchain.networks.index',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'network-list',
                    'name' => 'Network List',
                    'description' => 'View and manage blockchain networks.',
                    'route_name' => 'blockchain.networks.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== WALLETS ====================
        [
            'code' => 'wallets',
            'name' => 'Wallets',
            'description' => 'Manage blockchain wallets, balances, and transfers.',
            'icon' => 'WalletIcon',
            'route' => 'blockchain.wallets.index',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'wallet-list',
                    'name' => 'Wallet List',
                    'description' => 'View and manage wallets.',
                    'route_name' => 'blockchain.wallets.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'transfer', 'name' => 'Transfer', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== TRANSACTIONS ====================
        [
            'code' => 'transactions',
            'name' => 'Transactions',
            'description' => 'View and manage blockchain transactions.',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => 'blockchain.transactions.index',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'transaction-list',
                    'name' => 'Transaction List',
                    'description' => 'View and create blockchain transactions.',
                    'route_name' => 'blockchain.transactions.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== SMART CONTRACTS ====================
        [
            'code' => 'smart-contracts',
            'name' => 'Smart Contracts',
            'description' => 'Deploy, manage, and interact with smart contracts.',
            'icon' => 'CodeBracketIcon',
            'route' => 'blockchain.contracts.index',
            'priority' => 5,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'contract-list',
                    'name' => 'Contract List',
                    'description' => 'View and manage smart contracts.',
                    'route_name' => 'blockchain.contracts.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'deploy', 'name' => 'Deploy', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== TOKENS ====================
        [
            'code' => 'tokens',
            'name' => 'Tokens',
            'description' => 'Manage blockchain tokens and track holders/transfers.',
            'icon' => 'CircleStackIcon',
            'route' => 'blockchain.tokens.index',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'token-list',
                    'name' => 'Token List',
                    'description' => 'View and manage tokens.',
                    'route_name' => 'blockchain.tokens.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== CONSENSUS ====================
        [
            'code' => 'consensus',
            'name' => 'Consensus Nodes',
            'description' => 'Manage validator/consensus nodes and staking operations.',
            'icon' => 'ServerStackIcon',
            'route' => 'blockchain.consensus.index',
            'priority' => 7,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'node-list',
                    'name' => 'Node List',
                    'description' => 'View and manage consensus nodes.',
                    'route_name' => 'blockchain.consensus.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'stake', 'name' => 'Stake', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ANALYTICS ====================
        [
            'code' => 'analytics',
            'name' => 'Blockchain Analytics',
            'description' => 'Network analytics, transaction metrics, DeFi, and NFT insights.',
            'icon' => 'ChartBarIcon',
            'route' => 'blockchain.analytics.index',
            'priority' => 8,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'analytics-dashboard',
                    'name' => 'Analytics Dashboard',
                    'description' => 'Blockchain analytics and reporting.',
                    'route_name' => 'blockchain.analytics.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== BLOCK EXPLORER ====================
        [
            'code' => 'explorer',
            'name' => 'Block Explorer',
            'description' => 'Browse blocks, transactions, addresses, and contracts.',
            'icon' => 'MagnifyingGlassIcon',
            'route' => 'blockchain.explorer.index',
            'priority' => 9,
            'is_active' => true,
            'components' => [],
        ],

        // ==================== NFTs ====================
        [
            'code' => 'nfts', 'name' => 'NFTs', 'description' => 'Non-fungible token minting and management.',
            'icon' => 'PhotoIcon', 'route' => 'blockchain.nfts.index', 'priority' => 10, 'is_active' => true,
            'components' => [
                ['code' => 'nft-collections', 'name' => 'NFT Collections', 'route_name' => 'blockchain.nfts.collections', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Collection', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ]],
                ['code' => 'nft-mint', 'name' => 'Mint NFT', 'route_name' => 'blockchain.nfts.mint', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'mint', 'name' => 'Mint', 'is_active' => true], ['code' => 'burn', 'name' => 'Burn', 'is_active' => true]]],
                ['code' => 'nft-marketplace', 'name' => 'NFT Marketplace', 'route_name' => 'blockchain.nfts.marketplace', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'list', 'name' => 'List NFT', 'is_active' => true], ['code' => 'buy', 'name' => 'Buy NFT', 'is_active' => true]]],
            ],
        ],

        // ==================== DEFI ====================
        [
            'code' => 'defi', 'name' => 'DeFi Operations', 'description' => 'Staking, lending, liquidity, yield farming.',
            'icon' => 'CurrencyDollarIcon', 'route' => 'blockchain.defi.index', 'priority' => 11, 'is_active' => true,
            'components' => [
                ['code' => 'staking', 'name' => 'Staking', 'route_name' => 'blockchain.defi.staking', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'stake', 'name' => 'Stake', 'is_active' => true], ['code' => 'unstake', 'name' => 'Unstake', 'is_active' => true]]],
                ['code' => 'liquidity', 'name' => 'Liquidity Pools', 'route_name' => 'blockchain.defi.liquidity', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'provide', 'name' => 'Provide Liquidity', 'is_active' => true], ['code' => 'withdraw', 'name' => 'Withdraw Liquidity', 'is_active' => true]]],
                ['code' => 'yield-farming', 'name' => 'Yield Farming', 'route_name' => 'blockchain.defi.yield', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Yield Farming', 'is_active' => true]]],
            ],
        ],

        // ==================== ASSET PROVENANCE (EAM) ====================
        [
            'code' => 'asset-provenance', 'name' => 'Asset Provenance (EAM)',
            'description' => 'Immutable provenance records for EAM assets: origin, ownership, chain-of-custody.',
            'icon' => 'ShieldCheckIcon', 'route' => 'blockchain.asset-provenance.index', 'priority' => 12, 'is_active' => true,
            'components' => [
                ['code' => 'provenance-records', 'name' => 'Provenance Records', 'route_name' => 'blockchain.asset-provenance.records', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Provenance', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Provenance Record', 'is_active' => true],
                        ['code' => 'link-asset', 'name' => 'Link to EAM Asset', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify On-Chain', 'is_active' => true],
                    ]],
                ['code' => 'chain-of-custody', 'name' => 'Chain of Custody', 'route_name' => 'blockchain.asset-provenance.custody', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Custody Trail', 'is_active' => true],
                        ['code' => 'transfer', 'name' => 'Transfer Custody', 'is_active' => true],
                    ]],
                ['code' => 'asset-tokenization', 'name' => 'Asset Tokenization', 'route_name' => 'blockchain.asset-provenance.tokenize', 'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'tokenize', 'name' => 'Tokenize Asset', 'is_active' => true],
                        ['code' => 'fractionalize', 'name' => 'Fractionalize Asset', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== SUPPLY CHAIN TRACE ====================
        [
            'code' => 'supply-chain-trace', 'name' => 'Supply Chain Traceability',
            'description' => 'End-to-end supply chain trace on-chain (GS1-compatible).',
            'icon' => 'QrCodeIcon', 'route' => 'blockchain.supply-chain.index', 'priority' => 13, 'is_active' => true,
            'components' => [
                ['code' => 'trace-records', 'name' => 'Trace Records', 'route_name' => 'blockchain.supply-chain.records', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Trace', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify Trace', 'is_active' => true],
                    ]],
                ['code' => 'qr-verification', 'name' => 'QR / Consumer Verification', 'route_name' => 'blockchain.supply-chain.qr', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'generate-qr', 'name' => 'Generate QR', 'is_active' => true], ['code' => 'verify-qr', 'name' => 'Verify QR', 'is_active' => true]]],
            ],
        ],

        // ==================== AUDIT CHAIN ====================
        [
            'code' => 'audit-chain', 'name' => 'Audit Chain',
            'description' => 'Immutable audit log on-chain for critical events.',
            'icon' => 'ClipboardDocumentListIcon', 'route' => 'blockchain.audit-chain.index', 'priority' => 14, 'is_active' => true,
            'components' => [
                ['code' => 'audit-log', 'name' => 'On-Chain Audit Log', 'route_name' => 'blockchain.audit-chain.log', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Audit Log', 'is_active' => true],
                        ['code' => 'anchor', 'name' => 'Anchor Event', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify Event', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== DID / IDENTITY ====================
        [
            'code' => 'did-identity', 'name' => 'DID & Identity',
            'description' => 'Decentralized Identifiers, Verifiable Credentials.',
            'icon' => 'IdentificationIcon', 'route' => 'blockchain.identity.index', 'priority' => 15, 'is_active' => true,
            'components' => [
                ['code' => 'dids', 'name' => 'DIDs', 'route_name' => 'blockchain.identity.dids', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create DID', 'is_active' => true],
                        ['code' => 'resolve', 'name' => 'Resolve DID', 'is_active' => true],
                    ]],
                ['code' => 'credentials', 'name' => 'Verifiable Credentials', 'route_name' => 'blockchain.identity.credentials', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'issue', 'name' => 'Issue Credential', 'is_active' => true],
                        ['code' => 'revoke', 'name' => 'Revoke Credential', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify Credential', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== ORACLES ====================
        [
            'code' => 'oracles', 'name' => 'Oracles',
            'description' => 'Off-chain data feeds into smart contracts.',
            'icon' => 'SignalIcon', 'route' => 'blockchain.oracles.index', 'priority' => 16, 'is_active' => true,
            'components' => [
                ['code' => 'oracle-list', 'name' => 'Oracle List', 'route_name' => 'blockchain.oracles.list', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Oracle', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== BRIDGES ====================
        [
            'code' => 'bridges', 'name' => 'Cross-Chain Bridges',
            'description' => 'Transfer assets across blockchains.',
            'icon' => 'ArrowsRightLeftIcon', 'route' => 'blockchain.bridges.index', 'priority' => 17, 'is_active' => true,
            'components' => [
                ['code' => 'bridge-list', 'name' => 'Bridges', 'route_name' => 'blockchain.bridges.list', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Bridge', 'is_active' => true],
                        ['code' => 'transfer', 'name' => 'Cross-Chain Transfer', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== GAS & CUSTODY ====================
        [
            'code' => 'gas-custody', 'name' => 'Gas & Custody',
            'description' => 'Gas fee management, enterprise custody (MPC / HSM).',
            'icon' => 'BanknotesIcon', 'route' => 'blockchain.gas-custody.index', 'priority' => 18, 'is_active' => true,
            'components' => [
                ['code' => 'gas-fees', 'name' => 'Gas Fee Management', 'route_name' => 'blockchain.gas-custody.gas', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true], ['code' => 'configure', 'name' => 'Configure Gas Strategy', 'is_active' => true]]],
                ['code' => 'custody', 'name' => 'Enterprise Custody (MPC/HSM)', 'route_name' => 'blockchain.gas-custody.custody', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Custody', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Custody', 'is_active' => true],
                        ['code' => 'approve', 'name' => 'Approve Transaction', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== COMPLIANCE / KYC ====================
        [
            'code' => 'compliance-kyc', 'name' => 'Compliance & KYC',
            'description' => 'KYC/AML, travel rule, sanctions screening.',
            'icon' => 'ShieldCheckIcon', 'route' => 'blockchain.compliance.index', 'priority' => 19, 'is_active' => true,
            'components' => [
                ['code' => 'kyc-aml', 'name' => 'KYC / AML', 'route_name' => 'blockchain.compliance.kyc', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View KYC', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify KYC', 'is_active' => true],
                    ]],
                ['code' => 'sanctions-screening', 'name' => 'Sanctions Screening', 'route_name' => 'blockchain.compliance.sanctions', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'screen', 'name' => 'Run Screening', 'is_active' => true]]],
                ['code' => 'travel-rule', 'name' => 'Travel Rule Compliance', 'route_name' => 'blockchain.compliance.travel-rule', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Travel Rule', 'is_active' => true]]],
            ],
        ],

        // ==================== SETTINGS ====================
        [
            'code' => 'settings',
            'name' => 'Blockchain Settings',
            'description' => 'Configure blockchain module settings.',
            'icon' => 'CogIcon',
            'route' => 'blockchain.settings.index',
            'priority' => 99,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'settings-config',
                    'name' => 'Settings',
                    'description' => 'Module configuration.',
                    'route_name' => 'blockchain.settings.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                    ],
                ],
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
            'blockchain.asset_provenance' => 'asset-provenance.provenance-records',
            'blockchain.chain_of_custody' => 'asset-provenance.chain-of-custody',
            'blockchain.asset_tokenization' => 'asset-provenance.asset-tokenization',
            'blockchain.supply_chain_trace' => 'supply-chain-trace.trace-records',
            'blockchain.audit_chain' => 'audit-chain.audit-log',
            'blockchain.did_identity' => 'did-identity.dids',
            'blockchain.credentials' => 'did-identity.credentials',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'eam.work_order_events' => 'aero-eam',
            'scm.goods_receipt' => 'aero-scm',
            'ims.serial_tracking' => 'aero-ims',
            'compliance.audit_events' => 'aero-compliance',
            'quality.certifications' => 'aero-quality',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'blockchain_admin_role' => 'blockchain-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'blockchain-access'],
    ],
];
