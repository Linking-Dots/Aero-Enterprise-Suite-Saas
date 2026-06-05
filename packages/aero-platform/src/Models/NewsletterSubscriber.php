<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Newsletter Subscriber Model
 *
 * Manages newsletter subscriptions for platform marketing.
 */
class NewsletterSubscriber extends CentralModel
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'central';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const SOURCE_WEBSITE = 'website';

    public const SOURCE_REGISTRATION = 'registration';

    public const SOURCE_IMPORT = 'import';

    public const SOURCE_API = 'api';

    protected $fillable = [
        'email',
        'name',
        'status',
        'confirmation_token',
        'confirmed_at',
        'unsubscribed_at',
        'unsubscribe_reason',
        'source',
        'preferences',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'preferences' => 'array',
        'metadata' => 'array',
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'source' => self::SOURCE_WEBSITE,
        'preferences' => '[]',
        'metadata' => '[]',
    ];

    protected $hidden = [
        'confirmation_token',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            if (empty($subscriber->confirmation_token)) {
                $subscriber->confirmation_token = Str::random(64);
            }
        });
    }

    /**
     * Scope for confirmed subscribers.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope for pending subscribers.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Selectable status options (value => label) for filters and forms.
     *
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_UNSUBSCRIBED => 'Unsubscribed',
        ];
    }

    /**
     * Selectable source options (value => label) for filters and forms.
     *
     * @return array<string, string>
     */
    public static function getSourceOptions(): array
    {
        return [
            self::SOURCE_WEBSITE => 'Website',
            self::SOURCE_REGISTRATION => 'Registration',
            self::SOURCE_IMPORT => 'Import',
            self::SOURCE_API => 'API',
        ];
    }

    /**
     * Scope for active subscribers (not unsubscribed).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    /**
     * Scope by preference.
     */
    public function scopeWithPreference($query, string $preference)
    {
        return $query->whereJsonContains('preferences', $preference);
    }

    /**
     * Check if subscriber is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Check if subscriber is unsubscribed.
     */
    public function isUnsubscribed(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED;
    }

    /**
     * Confirm the subscription.
     */
    public function confirm(): bool
    {
        return $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmation_token' => null,
        ]);
    }

    /**
     * Unsubscribe.
     */
    public function unsubscribe(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
            'unsubscribe_reason' => $reason,
        ]);
    }

    /**
     * Resubscribe.
     */
    public function resubscribe(): bool
    {
        return $this->update([
            'status' => self::STATUS_CONFIRMED,
            'unsubscribed_at' => null,
            'unsubscribe_reason' => null,
        ]);
    }

    /**
     * Generate unsubscribe URL.
     */
    public function getUnsubscribeUrl(): string
    {
        $token = $this->confirmation_token ?? Str::random(64);

        if (! $this->confirmation_token) {
            $this->update(['confirmation_token' => $token]);
        }

        return url("/newsletter/unsubscribe/{$token}");
    }

    /**
     * Check if has specific preference.
     */
    public function hasPreference(string $preference): bool
    {
        return in_array($preference, $this->preferences ?? [], true);
    }

    /**
     * Add preference.
     */
    public function addPreference(string $preference): bool
    {
        $preferences = $this->preferences ?? [];

        if (! in_array($preference, $preferences, true)) {
            $preferences[] = $preference;

            return $this->update(['preferences' => $preferences]);
        }

        return true;
    }

    /**
     * Remove preference.
     */
    public function removePreference(string $preference): bool
    {
        $preferences = array_filter(
            $this->preferences ?? [],
            fn ($p) => $p !== $preference
        );

        return $this->update(['preferences' => array_values($preferences)]);
    }

    /**
     * Find by confirmation token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('confirmation_token', $token)->first();
    }

    /**
     * Subscribe an email (create or update existing).
     */
    public static function subscribe(
        string $email,
        ?string $name = null,
        string $source = self::SOURCE_WEBSITE,
        array $preferences = [],
        bool $requireConfirmation = true
    ): self {
        $subscriber = static::withTrashed()->where('email', $email)->first();

        if ($subscriber) {
            // Reactivate if soft deleted
            if ($subscriber->trashed()) {
                $subscriber->restore();
            }

            // Resubscribe if unsubscribed
            if ($subscriber->isUnsubscribed()) {
                $subscriber->resubscribe();
            }

            // Update name if provided
            if ($name) {
                $subscriber->update(['name' => $name]);
            }

            return $subscriber;
        }

        return static::create([
            'email' => $email,
            'name' => $name,
            'source' => $source,
            'preferences' => $preferences,
            'status' => $requireConfirmation ? self::STATUS_PENDING : self::STATUS_CONFIRMED,
            'confirmed_at' => $requireConfirmation ? null : now(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Get preference options.
     */
    public static function getPreferenceOptions(): array
    {
        return [
            'product_updates' => 'Product Updates',
            'feature_releases' => 'New Feature Releases',
            'tips_tutorials' => 'Tips & Tutorials',
            'company_news' => 'Company News',
            'promotions' => 'Promotions & Offers',
            'events' => 'Events & Webinars',
        ];
    }
}
