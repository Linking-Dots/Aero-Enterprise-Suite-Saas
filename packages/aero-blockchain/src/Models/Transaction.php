<?php

namespace Aero\Blockchain\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_transactions';

    protected $fillable = [
        'blockchain_id', 'block_id', 'transaction_hash', 'from_address',
        'to_address', 'value', 'fee', 'gas_limit', 'gas_used', 'gas_price',
        'nonce', 'input_data', 'transaction_type', 'status', 'confirmation_count',
        'timestamp', 'is_internal', 'contract_address', 'method_call',
        'transaction_data', 'created_by',
    ];

    protected $casts = [
        'blockchain_id' => 'integer',
        'block_id' => 'integer',
        'value' => 'decimal:18',
        'fee' => 'decimal:18',
        'gas_limit' => 'integer',
        'gas_used' => 'integer',
        'gas_price' => 'decimal:9',
        'nonce' => 'integer',
        'timestamp' => 'datetime',
        'is_internal' => 'boolean',
        'transaction_data' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_TRANSFER = 'transfer';

    const TYPE_CONTRACT_CREATION = 'contract_creation';

    const TYPE_CONTRACT_CALL = 'contract_call';

    const TYPE_TOKEN_TRANSFER = 'token_transfer';

    const TYPE_NFT_TRANSFER = 'nft_transfer';

    const TYPE_MULTI_SIG = 'multi_sig';

    const TYPE_ATOMIC_SWAP = 'atomic_swap';

    const STATUS_PENDING = 'pending';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_FAILED = 'failed';

    const STATUS_DROPPED = 'dropped';

    const STATUS_REPLACED = 'replaced';

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_address', 'address');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_address', 'address');
    }

    public function tokenTransfers()
    {
        return $this->hasMany(TokenTransfer::class);
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isContractInteraction()
    {
        return in_array($this->transaction_type, [
            self::TYPE_CONTRACT_CREATION,
            self::TYPE_CONTRACT_CALL,
        ]);
    }

    public function isTokenTransaction()
    {
        return in_array($this->transaction_type, [
            self::TYPE_TOKEN_TRANSFER,
            self::TYPE_NFT_TRANSFER,
        ]);
    }

    public function getEffectiveGasPriceAttribute()
    {
        if ($this->gas_used > 0) {
            return $this->fee / $this->gas_used;
        }

        return $this->gas_price;
    }

    public function getGasUtilizationAttribute()
    {
        if (! $this->gas_limit) {
            return 0;
        }

        return round(($this->gas_used / $this->gas_limit) * 100, 2);
    }

    public function getConfirmationStatusAttribute()
    {
        $requiredConfirmations = $this->blockchain->confirmation_blocks ?: 6;

        return match (true) {
            $this->status === self::STATUS_FAILED => 'failed',
            $this->confirmation_count >= $requiredConfirmations => 'confirmed',
            $this->confirmation_count > 0 => 'confirming',
            default => 'pending'
        };
    }

    public function getAgeAttribute()
    {
        return $this->timestamp ? $this->timestamp->diffForHumans() : null;
    }

    public function getFormattedValueAttribute()
    {
        return number_format($this->value, 8).' '.($this->blockchain->native_token ?: 'ETH');
    }

    public function getFormattedFeeAttribute()
    {
        return number_format($this->fee, 8).' '.($this->blockchain->native_token ?: 'ETH');
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_DROPPED => 'default',
            self::STATUS_REPLACED => 'primary',
            default => 'default'
        };
    }

    public function validateSignature()
    {
        // In a real implementation, this would verify the transaction signature
        // against the from_address using elliptic curve cryptography
        return ! empty($this->transaction_hash) && ! empty($this->from_address);
    }

    public function confirm($confirmations = 1)
    {
        $this->increment('confirmation_count', $confirmations);

        $requiredConfirmations = $this->blockchain->confirmation_blocks ?: 6;
        if ($this->confirmation_count >= $requiredConfirmations && $this->isPending()) {
            $this->update(['status' => self::STATUS_CONFIRMED]);
        }
    }

    public function fail($reason = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'transaction_data' => array_merge($this->transaction_data ?: [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }

    public function replace($newTransactionHash)
    {
        $this->update([
            'status' => self::STATUS_REPLACED,
            'transaction_data' => array_merge($this->transaction_data ?: [], [
                'replaced_by' => $newTransactionHash,
                'replaced_at' => now()->toISOString(),
            ]),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByAddress($query, $address)
    {
        return $query->where(function ($q) use ($address) {
            $q->where('from_address', $address)
                ->orWhere('to_address', $address);
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeValueGreaterThan($query, $value)
    {
        return $query->where('value', '>', $value);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    public function scopeHighFee($query, $threshold = null)
    {
        $threshold = $threshold ?: 0.01; // Default threshold

        return $query->where('fee', '>', $threshold);
    }

    public function scopeContractInteractions($query)
    {
        return $query->whereIn('transaction_type', [
            self::TYPE_CONTRACT_CREATION,
            self::TYPE_CONTRACT_CALL,
        ]);
    }
}
