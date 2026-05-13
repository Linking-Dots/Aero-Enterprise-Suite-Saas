<?php

namespace Aero\Blockchain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TokenTransfer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_token_transfers';

    protected $fillable = [
        'transaction_id', 'token_address', 'from_address', 'to_address',
        'amount', 'token_id', 'transfer_type', 'log_index', 'block_number',
        'transaction_index', 'event_signature', 'raw_data', 'decoded_data',
        'timestamp',
    ];

    protected $casts = [
        'transaction_id' => 'integer',
        'amount' => 'decimal:18',
        'token_id' => 'integer',
        'log_index' => 'integer',
        'block_number' => 'integer',
        'transaction_index' => 'integer',
        'raw_data' => 'json',
        'decoded_data' => 'json',
        'timestamp' => 'datetime',
    ];

    const TYPE_ERC20 = 'erc20';

    const TYPE_ERC721 = 'erc721';

    const TYPE_ERC1155 = 'erc1155';

    const TYPE_NATIVE = 'native';

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function token()
    {
        return $this->belongsTo(CryptocurrencyToken::class, 'token_address', 'contract_address');
    }

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_address', 'address');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_address', 'address');
    }

    public function isERC20Transfer()
    {
        return $this->transfer_type === self::TYPE_ERC20;
    }

    public function isNFTTransfer()
    {
        return in_array($this->transfer_type, [self::TYPE_ERC721, self::TYPE_ERC1155]);
    }

    public function isMint()
    {
        return $this->from_address === '0x0000000000000000000000000000000000000000';
    }

    public function isBurn()
    {
        return $this->to_address === '0x0000000000000000000000000000000000000000';
    }

    public function getFormattedAmountAttribute()
    {
        if ($this->token) {
            $decimals = $this->token->decimals ?: 18;
            $displayAmount = $this->amount / pow(10, $decimals);

            return number_format($displayAmount, min(8, $decimals)).' '.$this->token->symbol;
        }

        return number_format($this->amount, 8);
    }

    public function getTransferDirectionAttribute()
    {
        if ($this->isMint()) {
            return 'mint';
        }
        if ($this->isBurn()) {
            return 'burn';
        }

        return 'transfer';
    }

    public function getUsdValueAttribute()
    {
        // In a real implementation, this would calculate USD value
        // based on token price and amount at time of transfer
        return null;
    }

    public function getAgeAttribute()
    {
        return $this->timestamp ? $this->timestamp->diffForHumans() : null;
    }

    public function scopeByToken($query, $tokenAddress)
    {
        return $query->where('token_address', $tokenAddress);
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
        return $query->where('transfer_type', $type);
    }

    public function scopeMints($query)
    {
        return $query->where('from_address', '0x0000000000000000000000000000000000000000');
    }

    public function scopeBurns($query)
    {
        return $query->where('to_address', '0x0000000000000000000000000000000000000000');
    }

    public function scopeNFTs($query)
    {
        return $query->whereIn('transfer_type', [self::TYPE_ERC721, self::TYPE_ERC1155]);
    }

    public function scopeTokens($query)
    {
        return $query->where('transfer_type', self::TYPE_ERC20);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    public function scopeValueGreaterThan($query, $value)
    {
        return $query->where('amount', '>', $value);
    }

    public function scopeByBlock($query, $blockNumber)
    {
        return $query->where('block_number', $blockNumber);
    }
}
