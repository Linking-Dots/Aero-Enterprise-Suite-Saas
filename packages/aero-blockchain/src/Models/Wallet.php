<?php

namespace Aero\Blockchain\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_wallets';

    protected $fillable = [
        'blockchain_id', 'user_id', 'address', 'wallet_name', 'wallet_type',
        'public_key', 'encrypted_private_key', 'derivation_path', 'balance',
        'nonce', 'is_contract', 'contract_type', 'last_activity',
        'transaction_count', 'is_watched_only', 'metadata', 'tags',
        'is_active',
    ];

    protected $casts = [
        'blockchain_id' => 'integer',
        'user_id' => 'integer',
        'balance' => 'decimal:18',
        'nonce' => 'integer',
        'is_contract' => 'boolean',
        'last_activity' => 'datetime',
        'transaction_count' => 'integer',
        'is_watched_only' => 'boolean',
        'metadata' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'encrypted_private_key',
    ];

    const TYPE_EOA = 'externally_owned_account';

    const TYPE_CONTRACT = 'contract_account';

    const TYPE_MULTISIG = 'multisig';

    const TYPE_HD_WALLET = 'hierarchical_deterministic';

    const TYPE_HARDWARE = 'hardware';

    const TYPE_PAPER = 'paper';

    const TYPE_BRAIN = 'brain';

    const TYPE_CUSTODIAL = 'custodial';

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outgoingTransactions()
    {
        return $this->hasMany(Transaction::class, 'from_address', 'address');
    }

    public function incomingTransactions()
    {
        return $this->hasMany(Transaction::class, 'to_address', 'address');
    }

    public function allTransactions()
    {
        return Transaction::where('from_address', $this->address)
            ->orWhere('to_address', $this->address);
    }

    public function tokenBalances()
    {
        return $this->hasMany(TokenBalance::class);
    }

    public function smartContract()
    {
        return $this->belongsTo(SmartContract::class, 'address', 'contract_address');
    }

    public function isContract()
    {
        return $this->is_contract;
    }

    public function isExternallyOwned()
    {
        return ! $this->is_contract;
    }

    public function isMultisig()
    {
        return $this->wallet_type === self::TYPE_MULTISIG;
    }

    public function isHardwareWallet()
    {
        return $this->wallet_type === self::TYPE_HARDWARE;
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 8).' '.($this->blockchain->native_token ?: 'ETH');
    }

    public function getActivityStatusAttribute()
    {
        if (! $this->last_activity) {
            return 'Never Active';
        }

        $daysAgo = $this->last_activity->diffInDays(now());

        return match (true) {
            $daysAgo <= 1 => 'Very Active',
            $daysAgo <= 7 => 'Active',
            $daysAgo <= 30 => 'Moderately Active',
            $daysAgo <= 90 => 'Low Activity',
            default => 'Inactive'
        };
    }

    public function getTotalSentAttribute()
    {
        return $this->outgoingTransactions()
            ->where('status', Transaction::STATUS_CONFIRMED)
            ->sum('value');
    }

    public function getTotalReceivedAttribute()
    {
        return $this->incomingTransactions()
            ->where('status', Transaction::STATUS_CONFIRMED)
            ->sum('value');
    }

    public function getProfitLossAttribute()
    {
        return $this->total_received - $this->total_sent;
    }

    public function getUsdValueAttribute()
    {
        // In a real implementation, this would fetch current exchange rates
        // and calculate USD value of all balances
        return null;
    }

    public function getAddressTypeAttribute()
    {
        if ($this->is_contract) {
            return 'Contract';
        }

        return match ($this->wallet_type) {
            self::TYPE_MULTISIG => 'Multi-Signature',
            self::TYPE_HD_WALLET => 'HD Wallet',
            self::TYPE_HARDWARE => 'Hardware Wallet',
            self::TYPE_PAPER => 'Paper Wallet',
            self::TYPE_BRAIN => 'Brain Wallet',
            self::TYPE_CUSTODIAL => 'Custodial',
            default => 'Standard Wallet'
        };
    }

    public function updateBalance($newBalance)
    {
        $this->update([
            'balance' => $newBalance,
            'last_activity' => now(),
        ]);
    }

    public function incrementNonce()
    {
        $this->increment('nonce');
        $this->update(['last_activity' => now()]);
    }

    public function updateTransactionCount()
    {
        $count = $this->allTransactions()->count();
        $this->update(['transaction_count' => $count]);
    }

    public function addTag($tag)
    {
        $tags = $this->tags ?: [];
        if (! in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag($tag)
    {
        $tags = $this->tags ?: [];
        $tags = array_values(array_filter($tags, fn ($t) => $t !== $tag));
        $this->update(['tags' => $tags]);
    }

    public function hasTag($tag)
    {
        return in_array($tag, $this->tags ?: []);
    }

    public function validateAddress()
    {
        // Basic validation - in production would use proper address validation
        return ! empty($this->address) &&
               strlen($this->address) === 42 &&
               str_starts_with($this->address, '0x');
    }

    public function generateAddress($privateKey = null)
    {
        // In a real implementation, this would generate a valid Ethereum address
        // from a private key using elliptic curve cryptography

        if (! $privateKey) {
            // Generate random private key
            $privateKey = bin2hex(random_bytes(32));
        }

        // Placeholder address generation
        $this->address = '0x'.substr(hash('sha256', $privateKey), 0, 40);
        $this->public_key = '0x'.hash('sha256', $privateKey.'public');

        return $this->address;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeContracts($query)
    {
        return $query->where('is_contract', true);
    }

    public function scopeExternallyOwned($query)
    {
        return $query->where('is_contract', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('wallet_type', $type);
    }

    public function scopeWithBalance($query, $minBalance = 0)
    {
        return $query->where('balance', '>', $minBalance);
    }

    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_activity', '>=', now()->subDays($days));
    }

    public function scopeHighActivity($query, $threshold = 100)
    {
        return $query->where('transaction_count', '>=', $threshold);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWatchedOnly($query)
    {
        return $query->where('is_watched_only', true);
    }

    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
