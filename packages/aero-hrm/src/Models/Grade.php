<?php

namespace Aero\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'level',
        'min_salary',
        'max_salary',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employees for the grade.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(\Aero\Core\Models\User::class, 'grade_id');
    }
}
