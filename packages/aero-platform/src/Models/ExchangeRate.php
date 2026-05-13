<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends CentralModel
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'source',
        'metadata',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the latest exchange rate for a currency pair.
     */
    public static function getRate(string $from, string $to, ?Carbon $date = null): ?float
    {
        $query = static::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('effective_date', '<=', $date ?? today())
            ->orderBy('effective_date', 'desc');

        $rate = $query->first();

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Get the latest rate or return 1.0 if same currency or not found.
     */
    public static function getRateOrDefault(string $from, string $to, ?Carbon $date = null): float
    {
        if ($from === $to) {
            return 1.0;
        }

        return static::getRate($from, $to, $date) ?? 1.0;
    }

    /**
     * Check if a rate exists for a specific date.
     */
    public static function hasRate(string $from, string $to, Carbon $date): bool
    {
        return static::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('effective_date', $date->toDateString())
            ->exists();
    }

    /**
     * Store or update a rate for a specific date.
     */
    public static function storeRate(
        string $from,
        string $to,
        float $rate,
        Carbon $date,
        string $source = 'manual',
        ?array $metadata = null
    ): self {
        return static::updateOrCreate(
            [
                'from_currency' => $from,
                'to_currency' => $to,
                'effective_date' => $date->toDateString(),
            ],
            [
                'rate' => $rate,
                'source' => $source,
                'metadata' => $metadata,
            ]
        );
    }
}
