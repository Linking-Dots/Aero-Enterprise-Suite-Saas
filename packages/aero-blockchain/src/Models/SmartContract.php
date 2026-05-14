<?php

namespace Aero\Blockchain\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmartContract extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_smart_contracts';

    protected $fillable = [
        'blockchain_id', 'contract_address', 'contract_name', 'contract_type',
        'creator_address', 'bytecode', 'abi', 'source_code', 'compiler_version',
        'optimization_enabled', 'deployment_transaction_hash', 'deployment_block',
        'status', 'verification_status', 'total_transactions', 'balance',
        'created_at_block', 'metadata', 'is_proxy', 'implementation_address',
        'created_by',
    ];

    protected $casts = [
        'blockchain_id' => 'integer',
        'abi' => 'json',
        'optimization_enabled' => 'boolean',
        'deployment_block' => 'integer',
        'total_transactions' => 'integer',
        'balance' => 'decimal:18',
        'created_at_block' => 'integer',
        'metadata' => 'json',
        'is_proxy' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_ERC20 = 'erc20';

    const TYPE_ERC721 = 'erc721';

    const TYPE_ERC1155 = 'erc1155';

    const TYPE_MULTISIG = 'multisig';

    const TYPE_DEX = 'dex';

    const TYPE_LENDING = 'lending';

    const TYPE_GOVERNANCE = 'governance';

    const TYPE_ESCROW = 'escrow';

    const TYPE_SUPPLY_CHAIN = 'supply_chain';

    const TYPE_VOTING = 'voting';

    const TYPE_CUSTOM = 'custom';

    const STATUS_ACTIVE = 'active';

    const STATUS_PAUSED = 'paused';

    const STATUS_DEPRECATED = 'deprecated';

    const STATUS_DESTROYED = 'destroyed';

    const VERIFICATION_PENDING = 'pending';

    const VERIFICATION_VERIFIED = 'verified';

    const VERIFICATION_FAILED = 'failed';

    const VERIFICATION_PARTIAL = 'partial';

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'contract_address', 'contract_address');
    }

    public function deploymentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'deployment_transaction_hash', 'transaction_hash');
    }

    public function events()
    {
        return $this->hasMany(ContractEvent::class);
    }

    public function implementations()
    {
        return $this->hasMany(SmartContract::class, 'implementation_address', 'contract_address');
    }

    public function proxy()
    {
        return $this->belongsTo(SmartContract::class, 'implementation_address', 'contract_address');
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPaused()
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function isVerified()
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    public function isToken()
    {
        return in_array($this->contract_type, [
            self::TYPE_ERC20,
            self::TYPE_ERC721,
            self::TYPE_ERC1155,
        ]);
    }

    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 8).' '.($this->blockchain->native_token ?: 'ETH');
    }

    public function getActivityScoreAttribute()
    {
        $recentTransactions = $this->transactions()
            ->where('timestamp', '>=', now()->subDays(30))
            ->count();

        return match (true) {
            $recentTransactions >= 1000 => 'Very High',
            $recentTransactions >= 100 => 'High',
            $recentTransactions >= 10 => 'Medium',
            $recentTransactions >= 1 => 'Low',
            default => 'Inactive'
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_PAUSED => 'warning',
            self::STATUS_DEPRECATED => 'danger',
            self::STATUS_DESTROYED => 'error',
            default => 'default'
        };
    }

    public function getVerificationColorAttribute()
    {
        return match ($this->verification_status) {
            self::VERIFICATION_VERIFIED => 'success',
            self::VERIFICATION_PENDING => 'warning',
            self::VERIFICATION_PARTIAL => 'primary',
            self::VERIFICATION_FAILED => 'danger',
            default => 'default'
        };
    }

    public function hasFunction($functionName)
    {
        if (! is_array($this->abi)) {
            return false;
        }

        foreach ($this->abi as $function) {
            if (isset($function['name']) && $function['name'] === $functionName) {
                return true;
            }
        }

        return false;
    }

    public function getFunction($functionName)
    {
        if (! is_array($this->abi)) {
            return null;
        }

        foreach ($this->abi as $function) {
            if (isset($function['name']) && $function['name'] === $functionName) {
                return $function;
            }
        }

        return null;
    }

    public function getFunctions()
    {
        if (! is_array($this->abi)) {
            return [];
        }

        return array_filter($this->abi, function ($item) {
            return isset($item['type']) && $item['type'] === 'function';
        });
    }

    public function getEvents()
    {
        if (! is_array($this->abi)) {
            return [];
        }

        return array_filter($this->abi, function ($item) {
            return isset($item['type']) && $item['type'] === 'event';
        });
    }

    public function verify($sourceCode, $compilerVersion, $optimizationEnabled = false)
    {
        // In a real implementation, this would compile the source code
        // and compare the bytecode hash with the deployed contract

        $this->update([
            'source_code' => $sourceCode,
            'compiler_version' => $compilerVersion,
            'optimization_enabled' => $optimizationEnabled,
            'verification_status' => self::VERIFICATION_PENDING,
        ]);

        // Placeholder for actual verification logic
        // This would typically involve:
        // 1. Compiling the source code with specified settings
        // 2. Comparing bytecode hashes
        // 3. Updating verification status

        return true;
    }

    public function pause()
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    public function resume()
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function deprecate()
    {
        $this->update(['status' => self::STATUS_DEPRECATED]);
    }

    public function updateTransactionCount()
    {
        $count = $this->transactions()->count();
        $this->update(['total_transactions' => $count]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('contract_type', $type);
    }

    public function scopeTokens($query)
    {
        return $query->whereIn('contract_type', [
            self::TYPE_ERC20,
            self::TYPE_ERC721,
            self::TYPE_ERC1155,
        ]);
    }

    public function scopeProxies($query)
    {
        return $query->where('is_proxy', true);
    }

    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->whereHas('transactions', function ($q) use ($days) {
            $q->where('timestamp', '>=', now()->subDays($days));
        });
    }

    public function scopeHighActivity($query, $threshold = 100)
    {
        return $query->where('total_transactions', '>=', $threshold);
    }

    public function scopeByCreator($query, $creatorAddress)
    {
        return $query->where('creator_address', $creatorAddress);
    }
}
