<?php

namespace Aero\Blockchain\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptocurrencyToken extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blockchain_tokens';

    protected $fillable = [
        'blockchain_id', 'contract_address', 'token_name', 'token_symbol',
        'token_type', 'decimals', 'total_supply', 'max_supply', 'circulating_supply',
        'is_mintable', 'is_burnable', 'is_pausable', 'owner_address',
        'market_cap', 'price_usd', 'volume_24h', 'price_change_24h',
        'holders_count', 'transfers_count', 'logo_url', 'website_url',
        'description', 'social_links', 'is_verified', 'verification_tier',
        'created_by',
    ];

    protected $casts = [
        'blockchain_id' => 'integer',
        'decimals' => 'integer',
        'total_supply' => 'decimal:18',
        'max_supply' => 'decimal:18',
        'circulating_supply' => 'decimal:18',
        'is_mintable' => 'boolean',
        'is_burnable' => 'boolean',
        'is_pausable' => 'boolean',
        'market_cap' => 'decimal:2',
        'price_usd' => 'decimal:8',
        'volume_24h' => 'decimal:2',
        'price_change_24h' => 'decimal:4',
        'holders_count' => 'integer',
        'transfers_count' => 'integer',
        'social_links' => 'json',
        'is_verified' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_ERC20 = 'erc20';

    const TYPE_ERC721 = 'erc721';

    const TYPE_ERC1155 = 'erc1155';

    const TYPE_BEP20 = 'bep20';

    const TYPE_SPL = 'spl';

    const TYPE_NATIVE = 'native';

    const TIER_UNVERIFIED = 'unverified';

    const TIER_BASIC = 'basic';

    const TIER_STANDARD = 'standard';

    const TIER_PREMIUM = 'premium';

    const TIER_ENTERPRISE = 'enterprise';

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function smartContract()
    {
        return $this->belongsTo(SmartContract::class, 'contract_address', 'contract_address');
    }

    public function tokenTransfers()
    {
        return $this->hasMany(TokenTransfer::class, 'token_address', 'contract_address');
    }

    public function tokenBalances()
    {
        return $this->hasMany(TokenBalance::class);
    }

    public function isERC20()
    {
        return $this->token_type === self::TYPE_ERC20;
    }

    public function isNFT()
    {
        return in_array($this->token_type, [self::TYPE_ERC721, self::TYPE_ERC1155]);
    }

    public function isFungible()
    {
        return in_array($this->token_type, [self::TYPE_ERC20, self::TYPE_BEP20, self::TYPE_SPL]);
    }

    public function getFormattedSupplyAttribute()
    {
        if ($this->decimals && $this->total_supply) {
            $displaySupply = $this->total_supply / pow(10, $this->decimals);

            return number_format($displaySupply, 0).' '.$this->token_symbol;
        }

        return number_format($this->total_supply, 0).' '.$this->token_symbol;
    }

    public function getFormattedMarketCapAttribute()
    {
        if (! $this->market_cap) {
            return 'N/A';
        }

        if ($this->market_cap >= 1000000000) {
            return '$'.number_format($this->market_cap / 1000000000, 2).'B';
        }
        if ($this->market_cap >= 1000000) {
            return '$'.number_format($this->market_cap / 1000000, 2).'M';
        }
        if ($this->market_cap >= 1000) {
            return '$'.number_format($this->market_cap / 1000, 2).'K';
        }

        return '$'.number_format($this->market_cap, 2);
    }

    public function getFormattedVolumeAttribute()
    {
        if (! $this->volume_24h) {
            return 'N/A';
        }

        if ($this->volume_24h >= 1000000000) {
            return '$'.number_format($this->volume_24h / 1000000000, 2).'B';
        }
        if ($this->volume_24h >= 1000000) {
            return '$'.number_format($this->volume_24h / 1000000, 2).'M';
        }
        if ($this->volume_24h >= 1000) {
            return '$'.number_format($this->volume_24h / 1000, 2).'K';
        }

        return '$'.number_format($this->volume_24h, 2);
    }

    public function getPriceChangeColorAttribute()
    {
        if (! $this->price_change_24h) {
            return 'default';
        }

        return $this->price_change_24h > 0 ? 'success' : 'danger';
    }

    public function getVerificationBadgeAttribute()
    {
        return match ($this->verification_tier) {
            self::TIER_ENTERPRISE => '🏢 Enterprise',
            self::TIER_PREMIUM => '⭐ Premium',
            self::TIER_STANDARD => '✅ Standard',
            self::TIER_BASIC => '📝 Basic',
            default => '❓ Unverified'
        };
    }

    public function getHolderDistributionAttribute()
    {
        // Calculate top holder concentration
        $topHolders = $this->tokenBalances()
            ->orderBy('balance', 'desc')
            ->limit(10)
            ->sum('balance');

        if ($this->circulating_supply > 0) {
            return round(($topHolders / $this->circulating_supply) * 100, 2);
        }

        return 0;
    }

    public function getActivityScoreAttribute()
    {
        $recentTransfers = $this->tokenTransfers()
            ->where('timestamp', '>=', now()->subDays(7))
            ->count();

        return match (true) {
            $recentTransfers >= 1000 => 'Very High',
            $recentTransfers >= 100 => 'High',
            $recentTransfers >= 10 => 'Medium',
            $recentTransfers >= 1 => 'Low',
            default => 'No Activity'
        };
    }

    public function updatePrice($priceUsd, $volume24h = null, $priceChange24h = null)
    {
        $updateData = ['price_usd' => $priceUsd];

        if ($volume24h !== null) {
            $updateData['volume_24h'] = $volume24h;
        }

        if ($priceChange24h !== null) {
            $updateData['price_change_24h'] = $priceChange24h;
        }

        if ($priceUsd && $this->circulating_supply) {
            $updateData['market_cap'] = $priceUsd * ($this->circulating_supply / pow(10, $this->decimals ?: 18));
        }

        $this->update($updateData);
    }

    public function updateSupply($totalSupply, $circulatingSupply = null)
    {
        $updateData = ['total_supply' => $totalSupply];

        if ($circulatingSupply !== null) {
            $updateData['circulating_supply'] = $circulatingSupply;
        } else {
            $updateData['circulating_supply'] = $totalSupply;
        }

        $this->update($updateData);

        // Update market cap if price is available
        if ($this->price_usd) {
            $this->updatePrice($this->price_usd);
        }
    }

    public function updateHolderCount()
    {
        $count = $this->tokenBalances()
            ->where('balance', '>', 0)
            ->count();

        $this->update(['holders_count' => $count]);
    }

    public function updateTransferCount()
    {
        $count = $this->tokenTransfers()->count();
        $this->update(['transfers_count' => $count]);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('token_type', $type);
    }

    public function scopeFungible($query)
    {
        return $query->whereIn('token_type', [self::TYPE_ERC20, self::TYPE_BEP20, self::TYPE_SPL]);
    }

    public function scopeNFTs($query)
    {
        return $query->whereIn('token_type', [self::TYPE_ERC721, self::TYPE_ERC1155]);
    }

    public function scopeHighMarketCap($query, $threshold = 1000000)
    {
        return $query->where('market_cap', '>=', $threshold);
    }

    public function scopeActive($query, $days = 30)
    {
        return $query->whereHas('tokenTransfers', function ($q) use ($days) {
            $q->where('timestamp', '>=', now()->subDays($days));
        });
    }

    public function scopeByTier($query, $tier)
    {
        return $query->where('verification_tier', $tier);
    }

    public function scopeWithPrice($query)
    {
        return $query->whereNotNull('price_usd')
            ->where('price_usd', '>', 0);
    }
}
