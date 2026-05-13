<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'tenant_id',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant for the activity.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include activities for a specific module.
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope a query to only include activities for a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include activities for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include activities within a date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->where('created_at', '>=', $startDate);
    }

    /**
     * Scope a query to only include activities with a specific action.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get the human-readable description.
     */
    public function getHumanReadableDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the icon for the activity based on action.
     */
    public function getIcon(): string
    {
        return match ($this->action) {
            'created' => 'plus-circle',
            'updated' => 'pencil',
            'deleted' => 'trash',
            'login' => 'arrow-right-on-rectangle',
            'logout' => 'arrow-left-on-rectangle',
            'export' => 'download',
            'import' => 'upload',
            default => 'document',
        };
    }

    /**
     * Get the color for the activity based on action.
     */
    public function getColor(): string
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            'login' => 'success',
            'logout' => 'neutral',
            default => 'secondary',
        };
    }
}
