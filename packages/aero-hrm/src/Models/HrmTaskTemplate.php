<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HrmTaskTemplate extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_task_templates';

    protected $fillable = ['name', 'type', 'description', 'tasks', 'active'];

    protected function casts(): array
    {
        return [
            'tasks' => 'array',
            'active' => 'boolean',
        ];
    }
}
