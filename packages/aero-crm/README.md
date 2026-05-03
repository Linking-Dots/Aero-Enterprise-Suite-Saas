# Aero CRM Module

Customer Relationship Management module for the aeos365.

## Features

- **Customer Management** - Comprehensive customer database with contact information
- **Deal Management** - Track sales deals through customizable pipelines
- **Opportunity Management** - Manage sales opportunities and leads
- **Pipeline Management** - Create and customize sales pipelines with stages
- **Activity Tracking** - Log and track customer interactions
- **Competitor Analysis** - Track competitors and competitive information
- **Custom Fields** - Define custom fields for deals and customers

## Installation

This module is automatically discovered by Laravel when installed via Composer.

```bash
composer require aero/crm
```

## Configuration

The module configuration is automatically merged from `config/module.php`. You can publish the configuration:

```bash
php artisan vendor:publish --tag=crm-config
```

## Database

Run migrations to create the CRM tables:

```bash
php artisan migrate
```

## Usage

### Registering with Module Registry

The CRM module automatically registers itself with the ModuleRegistry when the application boots.

```php
use Aero\Core\Facades\ModuleRegistry;

// Check if CRM is enabled
if (ModuleRegistry::isEnabled('crm')) {
    // CRM is available
}

// Get CRM metadata
$metadata = ModuleRegistry::getMetadata('crm');
```

### Navigation

The module provides navigation items that are automatically merged into the application's navigation:

- Customers
- Deals
- Opportunities
- Pipelines

### Routes

Routes are automatically loaded from the `routes/` directory:
- `routes/tenant.php` - Tenant user routes
- `routes/admin.php` - Admin routes
- `routes/api.php` - API routes
- `routes/web.php` - Public routes

### Services

The module provides the following services:

- `Aero\Crm\Services\CRMService` - Core CRM functionality
- `Aero\Crm\Services\PipelineService` - Pipeline management

## Models

- **Customer** - Customer information
- **Deal** - Sales deals
- **DealActivity** - Deal activity log
- **DealAttachment** - Deal attachments
- **DealContact** - Deal contacts
- **DealCustomFieldDefinition** - Custom field definitions
- **DealLostReason** - Deal lost reasons
- **DealProduct** - Deal products
- **DealStageHistory** - Deal stage history
- **Lead** - Sales leads
- **LeadSource** - Lead sources
- **Opportunity** - Sales opportunities
- **Pipeline** - Sales pipelines
- **PipelineAutomation** - Pipeline automation rules
- **PipelineStage** - Pipeline stages
- **SalesStage** - Sales stages
- **Competitor** - Competitor information

## Dependencies

- `aero/core` - Core tenant functionality

## Minimum Plan

Professional plan or higher required.

## Version

1.0.0

## License

MIT
