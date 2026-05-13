<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends CentralModel
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
