<?php

namespace Aero\Blockchain\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Block extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_blocks';

    protected $fillable = [
        'blockchain_id', 'block_number', 'block_hash', 'parent_hash',
        'merkle_root', 'timestamp', 'parent_timestamp', 'nonce', 'difficulty',
        'gas_limit', 'gas_used', 'miner', 'reward', 'transaction_count',
        'block_size', 'confirmation_count', 'is_finalized', 'block_data',
    ];

    protected $casts = [
        'blockchain_id' => 'integer',
        'block_number' => 'integer',
        'timestamp' => 'datetime',
        'parent_timestamp' => 'datetime',
        'nonce' => 'integer',
        'difficulty' => 'decimal:2',
        'gas_limit' => 'integer',
        'gas_used' => 'integer',
        'reward' => 'decimal:18',
        'transaction_count' => 'integer',
        'block_size' => 'integer',
        'confirmation_count' => 'integer',
        'is_finalized' => 'boolean',
        'block_data' => 'json',
    ];

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function parentBlock()
    {
        return $this->belongsTo(Block::class, 'parent_hash', 'block_hash');
    }

    public function childBlocks()
    {
        return $this->hasMany(Block::class, 'parent_hash', 'block_hash');
    }

    public function getBlockTimeAttribute()
    {
        if ($this->parent_timestamp) {
            return $this->timestamp->diffInSeconds($this->parent_timestamp);
        }

        return null;
    }

    public function getGasUtilizationAttribute()
    {
        if (! $this->gas_limit) {
            return 0;
        }

        return round(($this->gas_used / $this->gas_limit) * 100, 2);
    }

    public function getAverageTransactionFeeAttribute()
    {
        if ($this->transaction_count === 0) {
            return 0;
        }

        return $this->transactions()->avg('fee') ?: 0;
    }

    public function getTotalTransactionValueAttribute()
    {
        return $this->transactions()->sum('value');
    }

    public function getConfirmationStatusAttribute()
    {
        $requiredConfirmations = $this->blockchain->confirmation_blocks ?: 6;

        return match (true) {
            $this->confirmation_count >= $requiredConfirmations => 'confirmed',
            $this->confirmation_count > 0 => 'confirming',
            default => 'unconfirmed'
        };
    }

    public function isConfirmed()
    {
        return $this->confirmation_status === 'confirmed';
    }

    public function isPending()
    {
        return $this->confirmation_status === 'unconfirmed';
    }

    public function validateHash()
    {
        // Basic hash validation - would need more sophisticated validation in production
        return ! empty($this->block_hash) &&
               strlen($this->block_hash) === 66 &&
               str_starts_with($this->block_hash, '0x');
    }

    public function calculateMerkleRoot()
    {
        $transactionHashes = $this->transactions()
            ->pluck('transaction_hash')
            ->toArray();

        if (empty($transactionHashes)) {
            return null;
        }

        // Simplified Merkle root calculation
        return hash('sha256', implode('', $transactionHashes));
    }

    public function incrementConfirmations()
    {
        $this->increment('confirmation_count');

        // Check if block should be finalized
        $requiredConfirmations = $this->blockchain->confirmation_blocks ?: 6;
        if ($this->confirmation_count >= $requiredConfirmations && ! $this->is_finalized) {
            $this->update(['is_finalized' => true]);
        }
    }

    public function scopeConfirmed($query)
    {
        return $query->where('is_finalized', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_finalized', false);
    }

    public function scopeByNumber($query, $blockNumber)
    {
        return $query->where('block_number', $blockNumber);
    }

    public function scopeByHash($query, $hash)
    {
        return $query->where('block_hash', $hash);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    public function scopeByMiner($query, $miner)
    {
        return $query->where('miner', $miner);
    }

    public function scopeWithTransactions($query)
    {
        return $query->where('transaction_count', '>', 0);
    }

    public function scopeHighGasUsage($query, $threshold = 80)
    {
        return $query->whereRaw('(gas_used / gas_limit * 100) >= ?', [$threshold]);
    }
}
