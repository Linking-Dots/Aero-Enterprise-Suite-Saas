<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkLocation extends TenantModel
{
    use SoftDeletes;

    protected $table = 'work_locations';

    protected $fillable = ['name', 'type', 'address', 'city', 'country', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude'  => 'float',
            'longitude' => 'float',
        ];
    }
}
