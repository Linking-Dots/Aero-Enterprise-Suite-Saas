<?php

namespace Aero\Auth\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * TenantInvitation Model
 *
 * Handles team member invitations within tenant context.
 * Supports invitation lifecycle: create, send, accept, cancel, expire.
 *
 * @property int $id
 * @property string $email
 * @property string $token
 * @property string $role
 * @property int|null $invited_by
 * @property Carbon|null $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $cancelled_at
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $inviter
 * @property-read bool $is_pending
 * @property-read bool $is_expired
 * @property-read bool $is_accepted
 * @property-read bool $is_cancelled
 */
class TenantInvitation extends Model
{
    use HasFactory, Notifiable;

    /**
     * Default invitation validity period in days.
     */
    public const EXPIRY_DAYS = 7;

    /**
     * Default audit label (mirrors the value previously inherited from TenantModel;
     * auth is now context-free, so identity models are plain Models — same pattern
     * as {@see User}/{@see UserDevice}).
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'token',
        'role',
        'invited_by',
        'expires_at',
        'accepted_at',
        'cancelled_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TenantInvitation $invitation) {
            // Generate unique token if not provided
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }

            // Set default expiration if not provided
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = Carbon::now()->addDays(self::EXPIRY_DAYS);
            }
        });
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * Get the user who sent this invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the invitation is still valid (not expired, not used).
     */
    public function isValid(): bool
    {
        return $this->accepted_at === null
            && $this->cancelled_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /**
     * Scope to get only pending (not accepted, not cancelled, not expired) invitations.
     *
     * @param  Builder<TenantInvitation>  $query
     * @return Builder<TenantInvitation>
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to get expired invitations.
     *
     * @param  Builder<TenantInvitation>  $query
     * @return Builder<TenantInvitation>
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope to find invitation by token.
     *
     * @param  Builder<TenantInvitation>  $query
     * @return Builder<TenantInvitation>
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    /**
     * Check if the invitation is still pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->accepted_at === null
            && $this->cancelled_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /**
     * Check if the invitation has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function getIsAcceptedAttribute(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Check if the invitation has been cancelled.
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Mark the invitation as accepted.
     */
    public function markAsAccepted(): bool
    {
        return $this->update(['accepted_at' => Carbon::now()]);
    }

    /**
     * Mark the invitation as cancelled.
     */
    public function cancel(): bool
    {
        return $this->update(['cancelled_at' => Carbon::now()]);
    }

    /**
     * Extend the invitation expiration date.
     */
    public function extend(?int $days = null): bool
    {
        $days = $days ?? self::EXPIRY_DAYS;

        return $this->update([
            'expires_at' => Carbon::now()->addDays($days),
        ]);
    }

    /**
     * Find a valid invitation by token.
     */
    public static function findValidByToken(string $token): ?self
    {
        return static::byToken($token)->pending()->first();
    }

    /**
     * Check if an email already has a pending invitation.
     */
    public static function hasPendingInvitation(string $email): bool
    {
        return static::where('email', $email)->pending()->exists();
    }
}
