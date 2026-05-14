<?php

namespace Aero\Blockchain\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blockchain extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_networks';

    protected $fillable = [
        'network_name', 'network_type', 'chain_id', 'rpc_endpoint', 'explorer_url',
        'native_token', 'genesis_block_hash', 'consensus_algorithm', 'block_time',
        'confirmation_blocks', 'gas_limit', 'gas_price', 'is_testnet',
        'network_config', 'is_active', 'created_by',
    ];

    protected $casts = [
        'chain_id' => 'integer',
        'block_time' => 'integer',
        'confirmation_blocks' => 'integer',
        'gas_limit' => 'integer',
        'gas_price' => 'decimal:9',
        'is_testnet' => 'boolean',
        'network_config' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_ETHEREUM = 'ethereum';

    const TYPE_BITCOIN = 'bitcoin';

    const TYPE_POLYGON = 'polygon';

    const TYPE_BSC = 'bsc';

    const TYPE_AVALANCHE = 'avalanche';

    const TYPE_PRIVATE = 'private';

    const TYPE_CONSORTIUM = 'consortium';

    const TYPE_HYPERLEDGER = 'hyperledger';

    const CONSENSUS_POW = 'proof_of_work';

    const CONSENSUS_POS = 'proof_of_stake';

    const CONSENSUS_POA = 'proof_of_authority';

    const CONSENSUS_PBFT = 'practical_byzantine_fault_tolerance';

    const CONSENSUS_RAFT = 'raft';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function smartContracts()
    {
        return $this->hasMany(SmartContract::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function getLatestBlockAttribute()
    {
        return $this->blocks()->orderBy('block_number', 'desc')->first();
    }

    public function getTotalTransactionsAttribute()
    {
        return $this->transactions()->count();
    }

    public function getNetworkHealthAttribute()
    {
        $recentBlocks = $this->blocks()
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        $expectedBlocks = 3600 / ($this->block_time ?: 15); // blocks per hour
        $health = ($recentBlocks / $expectedBlocks) * 100;

        return min(100, round($health, 2));
    }

    public function getAverageBlockTimeAttribute()
    {
        return $this->blocks()
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('AVG(UNIX_TIMESTAMP(created_at) - UNIX_TIMESTAMP(parent_timestamp)) as avg_time')
            ->value('avg_time') ?: $this->block_time;
    }

    public function isMainnet()
    {
        return ! $this->is_testnet;
    }

    public function supportsSmartContracts()
    {
        return in_array($this->network_type, [
            self::TYPE_ETHEREUM,
            self::TYPE_POLYGON,
            self::TYPE_BSC,
            self::TYPE_AVALANCHE,
            self::TYPE_HYPERLEDGER,
        ]);
    }

    public function getTransactionFeeAttribute()
    {
        if ($this->network_type === self::TYPE_BITCOIN) {
            return $this->transactions()
                ->where('created_at', '>=', now()->subHour())
                ->avg('fee');
        }

        return $this->gas_price * 21000; // Standard ETH transfer
    }

    public function getConfirmationTimeAttribute()
    {
        return ($this->confirmation_blocks ?: 1) * ($this->block_time ?: 15);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMainnet($query)
    {
        return $query->where('is_testnet', false);
    }

    public function scopeTestnet($query)
    {
        return $query->where('is_testnet', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('network_type', $type);
    }

    public function scopeSupportsContracts($query)
    {
        return $query->whereIn('network_type', [
            self::TYPE_ETHEREUM,
            self::TYPE_POLYGON,
            self::TYPE_BSC,
            self::TYPE_AVALANCHE,
            self::TYPE_HYPERLEDGER,
        ]);
    }
}
