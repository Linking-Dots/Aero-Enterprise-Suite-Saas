<?php

namespace Aero\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Navigation Preference Model
 *
 * Stores per-user navigation customisation: pinned items, hidden items,
 * custom ordering, quick-action shortcuts, and sidebar display preferences.
 *
 * @property int $id
 * @property int $user_id
 * @property array|null $pinned_items
 * @property array|null $hidden_items
 * @property array|null $custom_order
 * @property array|null $quick_actions
 * @property bool $show_labels
 * @property bool $compact_mode
 * @property string $sidebar_position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserNavigationPreference extends TenantModel
{
    protected $fillable = [
        'user_id',
        'pinned_items',
        'hidden_items',
        'custom_order',
        'quick_actions',
        'show_labels',
        'compact_mode',
        'sidebar_position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pinned_items' => 'array',
            'hidden_items' => 'array',
            'custom_order' => 'array',
            'quick_actions' => 'array',
            'show_labels' => 'boolean',
            'compact_mode' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get preference for a user, creating defaults if none exist.
     */
    public static function getOrCreateForUser(int $userId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'pinned_items' => [],
                'hidden_items' => [],
                'custom_order' => [],
                'quick_actions' => [],
                'show_labels' => true,
                'compact_mode' => false,
                'sidebar_position' => 'left',
            ]
        );
    }
}
