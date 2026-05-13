<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanySetting extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'companyName',
        'contactPerson',
        'address',
        'country',
        'city',
        'state',
        'postalCode',
        'email',
        'phoneNumber',
        'mobileNumber',
        'fax',
        'websiteUrl',
    ];
}
