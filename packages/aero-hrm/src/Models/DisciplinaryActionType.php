<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryActionType extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'severity',
        'points',
        'requires_investigation',
        'is_active',
    ];

    protected $casts = [
        'points' => 'integer',
        'requires_investigation' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Severity constants
     */
    public const SEVERITY_MINOR = 'minor';

    public const SEVERITY_MODERATE = 'moderate';

    public const SEVERITY_MAJOR = 'major';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the disciplinary cases for this action type.
     */
    public function cases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class, 'action_type_id');
    }

    /**
     * Scope a query to only include active action types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
