<?php

namespace Aero\Blockchain\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockchainAnalytics extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_analytics';

    protected $fillable = [
        'blockchain_id', 'date', 'metric_type', 'metric_name', 'value',
        'previous_value', 'change_percentage', 'metadata', 'period',
    ];

    protected $casts = [
        'blockchain_id' => 'integer',
        'date' => 'date',
        'value' => 'decimal:18',
        'previous_value' => 'decimal:18',
        'change_percentage' => 'decimal:4',
        'metadata' => 'json',
    ];

    const TYPE_NETWORK = 'network';

    const TYPE_TRANSACTION = 'transaction';

    const TYPE_BLOCK = 'block';

    const TYPE_TOKEN = 'token';

    const TYPE_DEFI = 'defi';

    const TYPE_NFT = 'nft';

    const TYPE_CONSENSUS = 'consensus';

    const TYPE_SECURITY = 'security';

    const PERIOD_HOURLY = 'hourly';

    const PERIOD_DAILY = 'daily';

    const PERIOD_WEEKLY = 'weekly';

    const PERIOD_MONTHLY = 'monthly';

    const PERIOD_YEARLY = 'yearly';

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function getFormattedValueAttribute()
    {
        return match ($this->metric_name) {
            'total_value_locked', 'market_cap', 'volume' => '$'.number_format($this->value, 2),
            'gas_price' => number_format($this->value, 9).' Gwei',
            'hash_rate' => number_format($this->value / 1000000, 2).' MH/s',
            'active_addresses', 'total_addresses', 'transaction_count' => number_format($this->value, 0),
            default => number_format($this->value, 8)
        };
    }

    public function getChangeDirectionAttribute()
    {
        if (! $this->change_percentage) {
            return 'neutral';
        }

        return $this->change_percentage > 0 ? 'up' : 'down';
    }

    public function getChangeColorAttribute()
    {
        return match ($this->change_direction) {
            'up' => 'success',
            'down' => 'danger',
            default => 'default'
        };
    }

    public function scopeByType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeByMetric($query, $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    public function scopeDaily($query)
    {
        return $query->where('period', self::PERIOD_DAILY);
    }
}
