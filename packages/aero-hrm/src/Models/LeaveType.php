<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends TenantModel
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'color',
        'days_per_year',
        'is_paid',
        'requires_approval',
        'carry_forward',
        'encashable',
        'max_carry_forward',
        'is_active',
    ];

    protected $casts = [
        'days_per_year' => 'decimal:2',
        'max_carry_forward' => 'decimal:2',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'carry_forward' => 'boolean',
        'encashable' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): Factory
    {
        return LeaveTypeFactory::new();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
