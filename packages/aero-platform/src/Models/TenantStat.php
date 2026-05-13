<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TenantStat Model
 *
 * Stores daily aggregated statistics for each tenant.
 * Data is collected by a scheduled job from tenant databases.
 *
 * @property int $id
 * @property string $tenant_id
 * @property Carbon $date
 * @property int $total_users
 * @property int $active_users
 * @property float $total_revenue
 * @property float $mrr
 * @property int $active_projects
 * @property int $total_documents
 * @property int $total_employees
 * @property int $storage_used_mb
 * @property int $api_requests
 * @property array|null $module_usage
 * @property Carbon $created_at
 */
class TenantStat extends CentralModel
{
    /**
     * Use the central database connection.
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     */
    protected $table = 'tenant_stats';

    /**
     * Indicates if the model should be timestamped.
     * Only created_at is used.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'date',
        'total_users',
        'active_users',
        'total_revenue',
        'mrr',
        'active_projects',
        'total_documents',
        'total_employees',
        'storage_used_mb',
        'api_requests',
        'module_usage',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_revenue' => 'decimal:2',
            'mrr' => 'decimal:2',
            'module_usage' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns these stats.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get stats for a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Record or update stats for a tenant on a specific date.
     */
    public static function recordStats(string $tenantId, array $stats, ?string $date = null): self
    {
        $date = $date ?? now()->toDateString();

        return static::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'date' => $date,
            ],
            array_merge($stats, ['created_at' => now()])
        );
    }

    /**
     * Get aggregated stats for all tenants on a specific date.
     */
    public static function getGlobalTotals(string $date): array
    {
        return static::where('date', $date)
            ->selectRaw('
                COUNT(DISTINCT tenant_id) as total_tenants,
                SUM(total_users) as total_users,
                SUM(active_users) as active_users,
                SUM(total_revenue) as total_revenue,
                SUM(mrr) as total_mrr,
                SUM(storage_used_mb) as total_storage_mb,
                SUM(api_requests) as total_api_requests
            ')
            ->first()
            ->toArray();
    }
}
