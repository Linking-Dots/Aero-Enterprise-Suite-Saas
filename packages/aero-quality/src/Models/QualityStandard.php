<?php

namespace Aero\Quality\Models;

use Illuminate\Database\Eloquent\Model;

class QualityStandard extends TenantModel
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'standard_type',
        'specifications',
        'tolerance',
        'unit_of_measure',
        'is_active',
    ];

    protected $casts = [
        'specifications' => 'array',
        'is_active' => 'boolean',
    ];
}
