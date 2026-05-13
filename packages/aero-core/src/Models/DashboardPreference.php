<?php

namespace Aero\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dashboard Preference Model
 *
 * Per-user dashboard layout customisation.
 *
 * @property int $id
 * @property int $user_id
 * @property string $dashboard_key
 * @property array|null $layout
 * @property array $hidden_widgets
 * @property array $collapsed_sections
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DashboardPreference extends TenantModel
{
    protected $fillable = [
        'user_id',
        'dashboard_key',
        'layout',
        'hidden_widgets',
        'collapsed_sections',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'hidden_widgets' => 'array',
            'collapsed_sections' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
