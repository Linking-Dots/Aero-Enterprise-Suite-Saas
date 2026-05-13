<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetentionPolicy extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'action',
        'retention_days',
        'filters',
        'is_active',
        'last_run_at',
        'next_run_at',
        'schedule',
        'records_processed',
        'notes',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * The tenant that owns the retention policy.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    /**
     * Check if policy is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if policy is due to run.
     */
    public function isDue(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->next_run_at === null || $this->next_run_at->isPast();
    }

    /**
     * Calculate next run date based on schedule.
     */
    public function calculateNextRun(): void
    {
        $this->next_run_at = match ($this->schedule) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay(),
        };
    }

    /**
     * Get the query for the entity type.
     */
    public function getEntityQuery()
    {
        $queries = [
            'audit_logs' => AuditLog::query(),
            'activities' => Activity::query(),
            'data_exports' => DataExport::query(),
            'trash' => null, // Special case for trash - handled in service
        ];

        $query = $queries[$this->entity_type] ?? null;

        if ($query && $this->filters) {
            foreach ($this->filters as $key => $value) {
                $query->where($key, $value);
            }
        }

        return $query;
    }
}
