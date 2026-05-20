<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlatformMetricDaily extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';

    protected $table = 'platform_metrics_daily';

    protected $fillable = [
        'date',
        'mrr',
        'arr',
        'plan_mrr',
        'product_mrr',
        'plan_arr',
        'product_arr',
        'new_tenants',
        'churned_tenants',
        'active_tenants',
        'trial_tenants',
        'total_revenue',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mrr' => 'decimal:2',
            'arr' => 'decimal:2',
            'plan_mrr' => 'decimal:2',
            'product_mrr' => 'decimal:2',
            'plan_arr' => 'decimal:2',
            'product_arr' => 'decimal:2',
            'total_revenue' => 'decimal:2',
        ];
    }

    public static function forDate(string $date): ?self
    {
        return static::where('date', $date)->first();
    }

    /**
     * @return Collection<int, static>
     */
    public static function range(string $from, string $to): Collection
    {
        return static::whereBetween('date', [$from, $to])->orderBy('date')->get();
    }
}
