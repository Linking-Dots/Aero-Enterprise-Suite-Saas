<?php

namespace Aero\Blockchain\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsensusNode extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_consensus_nodes';

    protected $fillable = [
        'blockchain_id', 'node_id', 'node_name', 'node_type', 'node_address',
        'operator_address', 'status', 'stake_amount', 'voting_power',
        'commission_rate', 'uptime_percentage', 'last_heartbeat', 'version',
        'location', 'hardware_info', 'network_info', 'performance_metrics',
        'is_active', 'joined_at', 'created_by',
    ];

    protected $casts = [
        'blockchain_id' => 'integer',
        'stake_amount' => 'decimal:18',
        'voting_power' => 'decimal:4',
        'commission_rate' => 'decimal:4',
        'uptime_percentage' => 'decimal:2',
        'last_heartbeat' => 'datetime',
        'hardware_info' => 'json',
        'network_info' => 'json',
        'performance_metrics' => 'json',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'created_by' => 'integer',
    ];

    const TYPE_VALIDATOR = 'validator';

    const TYPE_MINER = 'miner';

    const TYPE_AUTHORITY = 'authority';

    const TYPE_OBSERVER = 'observer';

    const TYPE_LIGHT = 'light';

    const TYPE_FULL = 'full';

    const TYPE_ARCHIVE = 'archive';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_JAILED = 'jailed';

    const STATUS_UNBONDING = 'unbonding';

    const STATUS_SLASHED = 'slashed';

    const STATUS_MAINTENANCE = 'maintenance';

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function minedBlocks()
    {
        return $this->hasMany(Block::class, 'miner', 'node_address');
    }

    public function isValidator()
    {
        return $this->node_type === self::TYPE_VALIDATOR;
    }

    public function isMiner()
    {
        return $this->node_type === self::TYPE_MINER;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isJailed()
    {
        return $this->status === self::STATUS_JAILED;
    }

    public function getHealthStatusAttribute()
    {
        if (! $this->last_heartbeat) {
            return 'Unknown';
        }

        $minutesAgo = now()->diffInMinutes($this->last_heartbeat);

        return match (true) {
            $minutesAgo <= 5 => 'Healthy',
            $minutesAgo <= 15 => 'Warning',
            $minutesAgo <= 60 => 'Degraded',
            default => 'Offline'
        };
    }

    public function getAnnualRewardAttribute()
    {
        // Simplified reward calculation
        if ($this->stake_amount && $this->blockchain) {
            $networkRewardRate = 0.10; // 10% annual reward
            $commissionTaken = $this->stake_amount * $networkRewardRate * ($this->commission_rate / 100);

            return $commissionTaken;
        }

        return 0;
    }

    public function getPerformanceScoreAttribute()
    {
        $score = 100;

        // Deduct for low uptime
        if ($this->uptime_percentage < 99) {
            $score -= (99 - $this->uptime_percentage) * 2;
        }

        // Deduct for inactivity
        if ($this->health_status === 'Offline') {
            $score -= 50;
        } elseif ($this->health_status === 'Degraded') {
            $score -= 20;
        }

        // Deduct for slashing/jailing
        if ($this->isJailed()) {
            $score -= 30;
        }

        return max(0, $score);
    }

    public function getBlocksMinedTodayAttribute()
    {
        return $this->minedBlocks()
            ->whereDate('timestamp', today())
            ->count();
    }

    public function updateHeartbeat($performanceData = [])
    {
        $updateData = [
            'last_heartbeat' => now(),
            'status' => self::STATUS_ACTIVE,
        ];

        if (! empty($performanceData)) {
            $updateData['performance_metrics'] = array_merge(
                $this->performance_metrics ?: [],
                $performanceData
            );
        }

        $this->update($updateData);
    }

    public function updateStake($newStakeAmount)
    {
        $oldStake = $this->stake_amount;
        $this->update(['stake_amount' => $newStakeAmount]);

        // Update voting power based on new stake
        $this->updateVotingPower();

        return $newStakeAmount - $oldStake; // Return difference
    }

    public function updateVotingPower()
    {
        // Calculate voting power as percentage of total network stake
        $totalNetworkStake = self::where('blockchain_id', $this->blockchain_id)
            ->sum('stake_amount');

        if ($totalNetworkStake > 0) {
            $votingPower = ($this->stake_amount / $totalNetworkStake) * 100;
            $this->update(['voting_power' => $votingPower]);
        }
    }

    public function jail($reason = null)
    {
        $this->update([
            'status' => self::STATUS_JAILED,
            'performance_metrics' => array_merge($this->performance_metrics ?: [], [
                'jailed_at' => now()->toISOString(),
                'jail_reason' => $reason,
            ]),
        ]);
    }

    public function unjail()
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'performance_metrics' => array_merge($this->performance_metrics ?: [], [
                'unjailed_at' => now()->toISOString(),
            ]),
        ]);
    }

    public function slash($percentage, $reason = null)
    {
        $slashAmount = $this->stake_amount * ($percentage / 100);
        $newStake = $this->stake_amount - $slashAmount;

        $this->update([
            'stake_amount' => max(0, $newStake),
            'status' => self::STATUS_SLASHED,
            'performance_metrics' => array_merge($this->performance_metrics ?: [], [
                'slashed_at' => now()->toISOString(),
                'slash_amount' => $slashAmount,
                'slash_percentage' => $percentage,
                'slash_reason' => $reason,
            ]),
        ]);

        $this->updateVotingPower();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeValidators($query)
    {
        return $query->where('node_type', self::TYPE_VALIDATOR);
    }

    public function scopeMiners($query)
    {
        return $query->where('node_type', self::TYPE_MINER);
    }

    public function scopeJailed($query)
    {
        return $query->where('status', self::STATUS_JAILED);
    }

    public function scopeHighStake($query, $threshold = 1000000)
    {
        return $query->where('stake_amount', '>=', $threshold);
    }

    public function scopeRecentHeartbeat($query, $minutes = 15)
    {
        return $query->where('last_heartbeat', '>=', now()->subMinutes($minutes));
    }

    public function scopeHighUptime($query, $threshold = 95)
    {
        return $query->where('uptime_percentage', '>=', $threshold);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', '%'.$location.'%');
    }
}
