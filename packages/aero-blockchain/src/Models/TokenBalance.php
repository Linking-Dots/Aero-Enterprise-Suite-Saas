<?php

namespace Aero\Blockchain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TokenBalance extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_token_balances';

    protected $fillable = [
        'wallet_id', 'token_id', 'balance', 'locked_balance', 'staked_balance',
        'last_updated', 'is_frozen', 'metadata',
    ];

    protected $casts = [
        'wallet_id' => 'integer',
        'token_id' => 'integer',
        'balance' => 'decimal:18',
        'locked_balance' => 'decimal:18',
        'staked_balance' => 'decimal:18',
        'last_updated' => 'datetime',
        'is_frozen' => 'boolean',
        'metadata' => 'json',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function token()
    {
        return $this->belongsTo(CryptocurrencyToken::class);
    }

    public function getAvailableBalanceAttribute()
    {
        return $this->balance - ($this->locked_balance ?: 0) - ($this->staked_balance ?: 0);
    }

    public function getFormattedBalanceAttribute()
    {
        if ($this->token) {
            $decimals = $this->token->decimals ?: 18;
            $displayBalance = $this->balance / pow(10, $decimals);

            return number_format($displayBalance, min(8, $decimals)).' '.$this->token->token_symbol;
        }

        return number_format($this->balance, 8);
    }

    public function getUsdValueAttribute()
    {
        if ($this->token && $this->token->price_usd) {
            $decimals = $this->token->decimals ?: 18;
            $displayBalance = $this->balance / pow(10, $decimals);

            return $displayBalance * $this->token->price_usd;
        }

        return null;
    }

    public function updateBalance($newBalance)
    {
        $this->update([
            'balance' => $newBalance,
            'last_updated' => now(),
        ]);
    }

    public function scopeNonZero($query)
    {
        return $query->where('balance', '>', 0);
    }

    public function scopeByToken($query, $tokenId)
    {
        return $query->where('token_id', $tokenId);
    }

    public function scopeFrozen($query)
    {
        return $query->where('is_frozen', true);
    }

    public function scopeWithStake($query)
    {
        return $query->where('staked_balance', '>', 0);
    }
}
