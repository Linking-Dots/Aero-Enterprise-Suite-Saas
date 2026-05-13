<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preference_key',
        'preference_value',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Scope a query to find a preference by key for a user.
     */
    public function scopeByKey($query, $userId, $key)
    {
        return $query->where('user_id', $userId)->where('preference_key', $key);
    }

    /**
     * Get a preference value for a user.
     */
    public static function getPreference($userId, $key, $default = null)
    {
        $preference = self::where('user_id', $userId)
            ->where('preference_key', $key)
            ->first();

        return $preference ? $preference->preference_value : $default;
    }

    /**
     * Set a preference value for a user.
     */
    public static function setPreference($userId, $key, $value, $metadata = null)
    {
        return self::updateOrCreate(
            ['user_id' => $userId, 'preference_key' => $key],
            [
                'preference_value' => $value,
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Delete a preference for a user.
     */
    public static function deletePreference($userId, $key)
    {
        return self::where('user_id', $userId)
            ->where('preference_key', $key)
            ->delete();
    }

    /**
     * Check if a user has a specific preference.
     */
    public static function hasPreference($userId, $key)
    {
        return self::where('user_id', $userId)
            ->where('preference_key', $key)
            ->exists();
    }

    /**
     * Get all preferences for a user as a key-value array.
     */
    public static function getAllPreferences($userId)
    {
        return self::where('user_id', $userId)
            ->pluck('preference_value', 'preference_key')
            ->toArray();
    }

    /**
     * Bulk set preferences for a user.
     */
    public static function bulkSetPreferences($userId, array $preferences)
    {
        foreach ($preferences as $key => $value) {
            self::updateOrCreate(
                ['user_id' => $userId, 'preference_key' => $key],
                ['preference_value' => $value]
            );
        }
    }
}
