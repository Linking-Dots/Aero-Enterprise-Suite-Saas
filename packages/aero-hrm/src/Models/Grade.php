<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends TenantModel
{
    use SoftDeletes;

    protected $table = 'grades';

    protected $fillable = ['name', 'code', 'min_salary', 'max_salary', 'is_active'];

    protected function casts(): array
    {
        return [
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'is_active'  => 'boolean',
        ];
    }
}
