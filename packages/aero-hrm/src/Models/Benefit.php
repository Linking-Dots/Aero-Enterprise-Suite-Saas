<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Benefit extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',         // health, retirement, insurance, leave, other
        'value',        // matches migration column
        'value_type',   // fixed | percentage
        'is_active',
    ];

    protected $casts = [
        'value'     => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employees with this benefit.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_benefits')
            ->withPivot('enrollment_date', 'end_date', 'coverage_level', 'cost_to_employee', 'status', 'notes')
            ->withTimestamps();
    }
}
