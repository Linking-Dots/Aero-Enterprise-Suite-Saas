<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HrmPublicHoliday extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_public_holidays';

    protected $fillable = ['name', 'date', 'is_optional'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_optional' => 'boolean',
        ];
    }
}
