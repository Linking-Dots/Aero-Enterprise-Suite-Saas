# Aero Finance Module

**Financial Management System** for the aeos365 SaaS platform.

## Features

### Core Modules
- **Chart of Accounts** - Hierarchical account structure management
- **General Ledger** - Complete general ledger with transaction tracking
- **Journal Entries** - Manual and automated journal entry management
- **Accounts Payable** - Vendor payment and payable tracking
- **Accounts Receivable** - Customer payment and receivable management
- **Financial Reporting** - Comprehensive financial reports

### Key Capabilities
✅ Multi-tenant architecture support  
✅ Role-based access control (RBAC)  
✅ Multi-currency support  
✅ Automated journal entry posting  
✅ AP/AR tracking  
✅ Real-time financial reporting  

---

## Installation

### As a Laravel Package

**Step 1: Install via Composer**

```bash
composer require aero/finance
```

**Step 2: Run Migrations**

```bash
php artisan migrate
```

**Step 3: Access Finance Routes**

All routes are prefixed with `/finance`:

- Dashboard: `http://your-app.test/finance/dashboard`
- Chart of Accounts: `http://your-app.test/finance/chart-of-accounts`
- General Ledger: `http://your-app.test/finance/general-ledger`
- Journal Entries: `http://your-app.test/finance/journal-entries`

---

## Configuration

### Core Configuration (`config/finance.php`)

```php
return [
    'enabled' => env('FINANCE_MODULE_ENABLED', true),
    
    'accounting' => [
        'default_currency' => env('FINANCE_DEFAULT_CURRENCY', 'USD'),
        'fiscal_year_start' => env('FINANCE_FISCAL_YEAR_START', '01-01'),
        'decimal_places' => env('FINANCE_DECIMAL_PLACES', 2),
    ],
];
```

---

## Module Details

- **Code:** `finance`
- **Category:** business
- **Priority:** 12
- **Min Plan:** professional
- **Dependencies:** core
- **Version:** 1.0.0

---

## Integration with Main Platform

The Finance module integrates seamlessly with the aeos365:

### Multi-Tenancy Support
All routes automatically scoped to current tenant.

### Module Access Control
Routes protected by module permissions via `module:finance` middleware.

---

## Version History

### v1.0.0 (2025-12-08)
- Initial release
- Complete finance functionality extracted from monolith
- Support for both standalone and package modes
- Multi-tenant support
- 6 controllers, 3 models

---

## License

Proprietary - aeos365

---

**Made with ❤️ for the aeos365 Platform**
