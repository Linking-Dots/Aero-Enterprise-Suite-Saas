<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * Stores partial registration data for users who want to resume later.
 *
 * @property int $id
 * @property string $email
 * @property string $token Hashed magic link token
 * @property string $step Current registration step
 * @property array $data Serialized registration data
 * @property Carbon $expires_at Token expiration
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PartialRegistration extends CentralModel
{
    use HasFactory;
    use Notifiable;

    protected $table = 'partial_registrations';

    protected $fillable = [
        'email',
        'token',
        'step',
        'data',
        'expires_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Check if the registration link has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope to get non-expired registrations.
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
