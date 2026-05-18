<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforcePlan extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_workforce_plans';

    /** @var list<string> */
    protected $fillable = [
        'fiscal_year',
        'department_id',
        'target_headcount',
        'target_hires',
        'target_attrition',
        'notes',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'target_headcount' => 'integer',
            'target_hires' => 'integer',
            'target_attrition' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
