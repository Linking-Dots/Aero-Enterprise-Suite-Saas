<?php

namespace Aero\Quality\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;

class InspectionChecklist extends TenantModel
{
    protected $fillable = [
        'inspection_id',
        'checkpoint_name',
        'checkpoint_description',
        'expected_value',
        'actual_value',
        'result',
        'notes',
    ];
}
