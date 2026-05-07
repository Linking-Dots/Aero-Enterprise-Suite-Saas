<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commerce Module — E-Commerce & Multi-Vendor Marketplace
    |--------------------------------------------------------------------------
    | Scope: tenant
    |
    | Tenancy concern:
    |   - Each tenant operates its own isolated storefront (products, orders,
    |     customers, inventory). No cross-tenant product/order data is accessible.
    |   - Payment gateway credentials are stored per-tenant (encrypted).
    |   - Vendor sub-accounts are child records within the tenant DB.
    |   - aero-platform billing (SaaS subscriptions) is entirely separate from
    |     this module's order/payment flows.
    |
    | Cross-package integrations:
    |   - aero-ims       → stock deduction on order placement
    |   - aero-finance   → revenue recognition, AR posting on order completion
    |   - aero-crm       → customer profile, order history on contact record
    |   - aero-scm       → purchase orders for dropship / replenishment
    |   - aero-hrm       → staff commission tracking
    |   - aero-analytics → storefront KPIs, conversion, revenue dashboards
    */

    'code' => 'commerce',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Commerce & E-Commerce',
    'description' => 'Full e-commerce platform: product catalog, shopping cart, orders, multi-vendor marketplace, payment gateways, and storefront management.',
    'icon' => 'ShoppingBagIcon',
    'route_prefix' => '/commerce',
    'category' => 'business',
    'priority' => 20,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('COMMERCE_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core', 'crm'],
    'release_date' => '2024-01-01',

    'features' => [
        'product_catalog' => true,  // products, variants, attributes, media
        'pricing_rules' => true,  // tiered, customer-group, volume pricing
        'shopping_cart' => true,
        'checkout_flow' => true,  // single-page checkout
        'order_management' => true,  // OMS: fulfillment, returns, refunds
        'payment_gateway' => true,  // Stripe, PayPal, Razorpay, per-tenant config
        'multi_currency' => true,
        'tax_calculation' => true,  // rule-based + Avalara/TaxJar integration
        'shipping_management' => true,  // carriers, rates, labels
        'coupon_vouchers' => true,
        'loyalty_points' => true,
        'storefront_builder' => true,  // page/theme builder for public store
        'multi_vendor' => true,  // marketplace: vendor onboarding, commissions
        'vendor_portal' => true,  // vendor self-service sub-portal
        'digital_products' => true,  // downloads, licenses
        'subscription_products' => true,  // recurring billing for products (≠ SaaS plans)
        'b2b_portal' => true,  // bulk orders, credit terms, RFQ
        'abandoned_cart_recovery' => true,
        'product_reviews' => true,
        'analytics_storefront' => true,  // delegates to aero-analytics
    ],

    'submodules' => [

        [
            'code' => 'catalog',  'name' => 'Product Catalog',  'icon' => 'TagIcon',
            'route' => '/commerce/catalog', 'priority' => 1,
            'components' => [
                ['code' => 'products',    'name' => 'Products',    'route' => '/commerce/catalog/products'],
                ['code' => 'categories',  'name' => 'Categories',  'route' => '/commerce/catalog/categories'],
                ['code' => 'attributes',  'name' => 'Attributes',  'route' => '/commerce/catalog/attributes'],
                ['code' => 'brands',      'name' => 'Brands',      'route' => '/commerce/catalog/brands'],
                ['code' => 'pricing',     'name' => 'Pricing Rules', 'route' => '/commerce/catalog/pricing'],
            ],
        ],

        [
            'code' => 'orders', 'name' => 'Orders', 'icon' => 'ClipboardDocumentListIcon',
            'route' => '/commerce/orders', 'priority' => 2,
            'components' => [
                ['code' => 'order-list',    'name' => 'All Orders',      'route' => '/commerce/orders/list'],
                ['code' => 'fulfillment',   'name' => 'Fulfillment',     'route' => '/commerce/orders/fulfillment'],
                ['code' => 'returns',       'name' => 'Returns & Refunds', 'route' => '/commerce/orders/returns'],
                ['code' => 'invoices',      'name' => 'Invoices',        'route' => '/commerce/orders/invoices'],
            ],
        ],

        [
            'code' => 'marketplace', 'name' => 'Marketplace', 'icon' => 'BuildingStorefrontIcon',
            'route' => '/commerce/marketplace', 'priority' => 3,
            'min_plan' => 'enterprise',
            'components' => [
                ['code' => 'vendors',         'name' => 'Vendors',           'route' => '/commerce/marketplace/vendors'],
                ['code' => 'commissions',     'name' => 'Commissions',       'route' => '/commerce/marketplace/commissions'],
                ['code' => 'vendor-payouts',  'name' => 'Vendor Payouts',    'route' => '/commerce/marketplace/payouts'],
            ],
        ],

        [
            'code' => 'storefront', 'name' => 'Storefront', 'icon' => 'GlobeAltIcon',
            'route' => '/commerce/storefront', 'priority' => 4,
            'components' => [
                ['code' => 'pages',     'name' => 'Pages & Themes', 'route' => '/commerce/storefront/pages'],
                ['code' => 'domains',   'name' => 'Custom Domain',  'route' => '/commerce/storefront/domains'],
                ['code' => 'seo',       'name' => 'SEO Settings',   'route' => '/commerce/storefront/seo'],
            ],
        ],

        [
            'code' => 'settings', 'name' => 'Commerce Settings', 'icon' => 'Cog6ToothIcon',
            'route' => '/commerce/settings', 'priority' => 10,
            'components' => [
                ['code' => 'payment-gateways', 'name' => 'Payment Gateways', 'route' => '/commerce/settings/payment'],
                ['code' => 'tax-settings',     'name' => 'Tax Settings',     'route' => '/commerce/settings/tax'],
                ['code' => 'shipping',         'name' => 'Shipping',         'route' => '/commerce/settings/shipping'],
                ['code' => 'currencies',       'name' => 'Currencies',       'route' => '/commerce/settings/currencies'],
            ],
        ],

    ],

    'permissions' => [
        'commerce.view' => 'View commerce module',
        'commerce.catalog.manage' => 'Manage product catalog',
        'commerce.orders.view' => 'View orders',
        'commerce.orders.manage' => 'Process & fulfill orders',
        'commerce.refunds.process' => 'Process refunds',
        'commerce.marketplace.manage' => 'Manage marketplace vendors',
        'commerce.storefront.manage' => 'Manage storefront & themes',
        'commerce.settings.manage' => 'Configure payment & tax settings',
        'commerce.reports.view' => 'View commerce analytics',
    ],

    'tenancy' => [
        'tenant_aware' => true,
        'uses_tenant_db' => true,
        'central_tables' => [],
        'cached_per_tenant' => true,
        'payment_credentials' => 'tenant_encrypted',  // per-tenant gateway keys
        'vendor_scope' => 'tenant',             // vendors are child of tenant
        'storefront_domain' => 'per_tenant',         // custom domain per tenant store
    ],
];
