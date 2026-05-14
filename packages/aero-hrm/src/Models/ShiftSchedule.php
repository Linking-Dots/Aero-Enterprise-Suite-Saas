<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftSchedule extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'break_duration',
        'grace_period',
        'working_days',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_duration' => 'integer',
        'grace_period' => 'integer',
        'working_days' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employees assigned to this shift.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(\Aero\Core\Models\User::class, 'employee_shift_schedule')
            ->withPivot(['effective_from', 'effective_to'])
            ->withTimestamps();
    }
}
