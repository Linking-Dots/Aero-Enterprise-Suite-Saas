<?php

namespace Aero\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Navigation Analytic Model
 *
 * Tracks per-user navigation usage for AI-powered suggestions:
 * visit counts, last/first visited timestamps per nav path.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nav_path
 * @property string|null $nav_name
 * @property string|null $module
 * @property int $visit_count
 * @property Carbon|null $last_visited_at
 * @property Carbon|null $first_visited_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserNavigationAnalytic extends TenantModel
{
    protected $fillable = [
        'user_id',
        'nav_path',
        'nav_name',
        'module',
        'visit_count',
        'last_visited_at',
        'first_visited_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_visited_at' => 'datetime',
            'first_visited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a navigation visit for the given user and path.
     * Creates the record if it doesn't exist, otherwise increments visit_count.
     */
    public static function track(int $userId, string $path, ?string $name = null, ?string $module = null): static
    {
        $record = static::firstOrNew(
            ['user_id' => $userId, 'nav_path' => $path],
            ['first_visited_at' => now()]
        );

        $record->visit_count = ($record->visit_count ?? 0) + 1;
        $record->last_visited_at = now();

        if ($name !== null && $record->nav_name === null) {
            $record->nav_name = $name;
        }

        if ($module !== null && $record->module === null) {
            $record->module = $module;
        }

        $record->save();

        return $record;
    }

    /**
     * Get the top N most visited nav items for a user.
     */
    public static function getTopForUser(int $userId, int $limit = 10): Collection
    {
        return static::where('user_id', $userId)
            ->orderByDesc('visit_count')
            ->orderByDesc('last_visited_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recently visited items for a user.
     */
    public static function getRecentForUser(int $userId, int $limit = 10): Collection
    {
        return static::where('user_id', $userId)
            ->whereNotNull('last_visited_at')
            ->orderByDesc('last_visited_at')
            ->limit($limit)
            ->get();
    }
}
