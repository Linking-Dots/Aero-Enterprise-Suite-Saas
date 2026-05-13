<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * PlatformStatDaily Model
 *
 * Stores platform-wide daily aggregated statistics.
 * Pre-computed rollup of tenant_stats for fast dashboard queries.
 *
 * @property int $id
 * @property Carbon $date
 * @property int $total_tenants
 * @property int $active_tenants
 * @property int $total_users
 * @property int $active_users
 * @property float $total_revenue
 * @property float $total_mrr
 * @property int $total_storage_mb
 * @property int $total_api_requests
 * @property int $new_signups
 * @property int $churned_tenants
 * @property int $trials_started
 * @property int $trials_converted
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class PlatformStatDaily extends CentralModel
{
    /**
     * Use the central database connection.
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     */
    protected $table = 'platform_stats_daily';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'date',
        'total_tenants',
        'active_tenants',
        'total_users',
        'active_users',
        'total_revenue',
        'total_mrr',
        'total_storage_mb',
        'total_api_requests',
        'new_signups',
        'churned_tenants',
        'trials_started',
        'trials_converted',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_revenue' => 'decimal:2',
            'total_mrr' => 'decimal:2',
        ];
    }

    /**
     * Get stats for a specific date.
     */
    public static function forDate(string $date): ?self
    {
        return static::where('date', $date)->first();
    }

    /**
     * Get stats for the last N days.
     */
    public static function lastDays(int $days = 30)
    {
        return static::where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Calculate growth percentage between two periods.
     */
    public static function calculateGrowth(string $metric, string $currentDate, string $previousDate): ?float
    {
        $current = static::forDate($currentDate)?->$metric ?? 0;
        $previous = static::forDate($previousDate)?->$metric ?? 0;

        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Aggregate and store daily platform stats from tenant_stats.
     */
    public static function aggregateFromTenantStats(string $date): self
    {
        $stats = TenantStat::getGlobalTotals($date);

        // Count tenant lifecycle events
        $newSignups = Tenant::whereDate('created_at', $date)->count();
        $activeTenants = Tenant::where('status', Tenant::STATUS_ACTIVE)->count();

        return static::updateOrCreate(
            ['date' => $date],
            [
                'total_tenants' => $stats['total_tenants'] ?? 0,
                'active_tenants' => $activeTenants,
                'total_users' => $stats['total_users'] ?? 0,
                'active_users' => $stats['active_users'] ?? 0,
                'total_revenue' => $stats['total_revenue'] ?? 0,
                'total_mrr' => $stats['total_mrr'] ?? 0,
                'total_storage_mb' => $stats['total_storage_mb'] ?? 0,
                'total_api_requests' => $stats['total_api_requests'] ?? 0,
                'new_signups' => $newSignups,
                // churned_tenants and trials need separate tracking
            ]
        );
    }
}
