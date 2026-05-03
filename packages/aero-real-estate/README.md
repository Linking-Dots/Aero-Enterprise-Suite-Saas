# Aero Real Estate Management System

A comprehensive real estate management system for property management, MLS integration, lease management, and maintenance tracking.

## Features

### Property Management
- **Property Portfolio Management**: Complete property inventory with detailed information
- **Property Listings**: MLS integration and listing management
- **Property Photos**: Multi-photo upload with categorization
- **Property Valuations**: Automated and manual property valuations
- **Property Inspections**: Inspection scheduling and reporting

### Tenant & Lease Management
- **Tenant Profiles**: Comprehensive tenant information and screening
- **Lease Agreements**: Digital lease creation and management
- **Rent Collection**: Automated rent tracking and payment processing
- **Lease Renewals**: Automated renewal notifications and processing

### Real Estate Agent Management
- **Agent Profiles**: Agent licensing and performance tracking
- **Brokerage Management**: Multi-brokerage support
- **Commission Tracking**: Automated commission calculations
- **Client Relationship Management**: Agent-client relationship tracking

### Property Maintenance
- **Maintenance Requests**: Tenant-initiated and proactive maintenance
- **Vendor Management**: Preferred vendor network
- **Work Order Tracking**: Complete maintenance lifecycle
- **Cost Tracking**: Maintenance budgeting and expense tracking

### Financial Management
- **Rent Roll Reports**: Monthly rental income tracking
- **Expense Tracking**: Property-specific expense management
- **Late Fee Management**: Automated late fee calculations
- **Financial Reporting**: Comprehensive financial analytics

### Lead Management
- **Property Inquiries**: Lead capture and qualification
- **Showing Management**: Property showing scheduling
- **Client Matching**: Property-client matching algorithms
- **Follow-up Automation**: Automated lead nurturing

## Models Overview

### Core Models
- `Property`: Central property management
- `RealEstateAgent`: Agent and broker management
- `PropertyListing`: MLS and listing management
- `LeaseAgreement`: Rental agreement management
- `PropertyTenant`: Tenant management
- `PropertyOwner`: Property ownership tracking

### Supporting Models
- `PropertyTransaction`: Sales and transaction tracking
- `PropertyInquiry`: Lead and inquiry management
- `PropertyPhoto`: Property media management
- `MaintenanceRequest`: Maintenance workflow
- `RentPayment`: Payment processing and tracking
- `PropertyInspection`: Inspection management
- `PropertyValuation`: Property appraisal and valuation
- `PropertyClient`: Client relationship management
- `RealEstateBrokerage`: Brokerage management

## Key Features

### Multi-Tenant Architecture
- Tenant-isolated data
- Role-based access control
- Scalable architecture

### MLS Integration
- Multiple MLS provider support
- Automated listing syndication
- Real-time market data

### Advanced Analytics
- Market analysis
- Performance metrics
- Financial reporting
- Predictive analytics

### Mobile-First Design
- Responsive interface
- Mobile app ready
- Offline capability

### Compliance & Security
- Data encryption
- Audit trails
- Regulatory compliance
- GDPR compliance

## Installation

```bash
composer require aero/real-estate
```

## Configuration

```php
// config/real-estate.php
return [
    'mls' => [
        'enabled' => true,
        'providers' => [...],
    ],
    'property' => [
        'default_commission_rate' => 6.0,
        'default_lease_duration' => 12,
    ],
];
```

## Usage Examples

### Property Management
```php
// Create a property
$property = Property::create([
    'address_line_1' => '123 Main St',
    'city' => 'New York',
    'state' => 'NY',
    'property_type' => Property::TYPE_SINGLE_FAMILY,
    'bedrooms' => 3,
    'bathrooms' => 2,
    'square_feet' => 1500,
    'current_value' => 500000,
]);

// Add photos
$property->photos()->create([
    'filename' => 'front-view.jpg',
    'photo_type' => PropertyPhoto::TYPE_EXTERIOR,
    'is_primary' => true,
]);
```

### Lease Management
```php
// Create a lease agreement
$lease = LeaseAgreement::create([
    'property_id' => $property->id,
    'tenant_id' => $tenant->id,
    'monthly_rent' => 2500,
    'security_deposit' => 5000,
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
]);

// Record rent payment
$payment = $lease->rentPayments()->create([
    'due_date' => '2024-01-01',
    'amount_due' => 2500,
    'amount_paid' => 2500,
    'payment_method' => RentPayment::METHOD_BANK_TRANSFER,
    'status' => RentPayment::STATUS_PAID,
]);
```

### Maintenance Management
```php
// Create maintenance request
$maintenance = MaintenanceRequest::create([
    'property_id' => $property->id,
    'tenant_id' => $tenant->id,
    'category' => MaintenanceRequest::CATEGORY_PLUMBING,
    'priority' => MaintenanceRequest::PRIORITY_HIGH,
    'title' => 'Kitchen sink leak',
    'description' => 'The kitchen sink is leaking under the cabinet',
]);

// Assign to vendor and complete
$maintenance->update([
    'vendor_id' => $vendor->id,
    'status' => MaintenanceRequest::STATUS_COMPLETED,
    'actual_cost' => 150.00,
]);
```

## API Endpoints

### Properties
- `GET /real-estate/properties` - List properties
- `POST /real-estate/properties` - Create property
- `GET /real-estate/properties/{id}` - Get property details
- `PUT /real-estate/properties/{id}` - Update property
- `DELETE /real-estate/properties/{id}` - Delete property

### Leases
- `GET /real-estate/leases` - List leases
- `POST /real-estate/leases` - Create lease
- `GET /real-estate/leases/{id}/payments` - Payment history

### Maintenance
- `GET /real-estate/maintenance` - List requests
- `POST /real-estate/maintenance` - Create request
- `PUT /real-estate/maintenance/{id}/complete` - Mark complete

## License

This package is part of the aeos365 and is proprietary software.