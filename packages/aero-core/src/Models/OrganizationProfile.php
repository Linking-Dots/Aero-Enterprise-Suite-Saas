<?php

namespace Aero\Core\Models;

use Aero\Core\Encryption\EncryptedField;

class OrganizationProfile extends TenantModel
{
    protected $fillable = [
        'company_name', 'legal_name', 'registration_number', 'tax_id',
        'vat_number', 'industry', 'company_size', 'website', 'phone',
        'email', 'country', 'currency', 'fiscal_year_start', 'fiscal_year_end',
        'timezone', 'date_format', 'logo_path', 'addresses', 'contacts',
    ];

    protected $casts = [
        'tax_id' => EncryptedField::class,
        'addresses' => 'array',
        'contacts' => 'array',
    ];
}
