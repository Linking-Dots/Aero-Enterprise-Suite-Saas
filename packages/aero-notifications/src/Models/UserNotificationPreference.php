<?php

declare(strict_types=1);

namespace Aero\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    protected $fillable = ['user_id','event_type','channel','enabled','quiet_hours_start','quiet_hours_end','digest_frequency','options'];
    protected $casts = ['enabled' => 'boolean','options' => 'array'];

    public static function getForUser(int $userId): array { return static::where('user_id', $userId)->get()->groupBy('event_type')->map->mapWithKeys(fn($i) => [$i->channel => $i->only(['enabled','quiet_hours_start','quiet_hours_end','digest_frequency','options'])])->toArray(); }
    public static function setForUser(int $userId, string $eventType, string $channel, bool $enabled, array $options = []): static { return static::updateOrCreate(['user_id' => $userId, 'event_type' => $eventType, 'channel' => $channel], array_merge(['enabled' => $enabled], $options)); }
    public static function isEnabledForUser(int $userId, string $eventType, string $channel): bool { $pref = static::where('user_id', $userId)->where('event_type', $eventType)->where('channel', $channel)->first(); return $pref ? (bool) $pref->enabled : true; }
    public static function getQuietHours(int $userId): array { $pref = static::where('user_id', $userId)->whereNotNull('quiet_hours_start')->first(); return $pref ? ['start' => $pref->quiet_hours_start, 'end' => $pref->quiet_hours_end] : config('aero.notifications.quiet_hours', []); }
    public static function getDigestFrequency(int $userId): ?string { return static::where('user_id', $userId)->whereNotNull('digest_frequency')->value('digest_frequency'); }
    public static function resetForUser(int $userId): void { static::where('user_id', $userId)->delete(); }

    public static function seedDefaultsForUser(int $userId, array $defaults = []): void
    {
        $channels = $defaults['channels'] ?? ['mail', 'sms', 'push', 'database'];
        $events = $defaults['events'] ?? ['security_alert', 'login', 'profile_update', 'system_notification'];
        $digest = $defaults['digest_frequency'] ?? 'immediate';
        $quietStart = $defaults['quiet_hours_start'] ?? null;
        $quietEnd = $defaults['quiet_hours_end'] ?? null;

        foreach ($events as $event) {
            foreach ($channels as $channel) {
                $existing = static::where('user_id', $userId)
                    ->where('event_type', $event)
                    ->where('channel', $channel)
                    ->first();

                if (! $existing) {
                    static::create([
                        'user_id' => $userId,
                        'event_type' => $event,
                        'channel' => $channel,
                        'enabled' => true,
                        'quiet_hours_start' => $quietStart,
                        'quiet_hours_end' => $quietEnd,
                        'digest_frequency' => $digest,
                        'options' => [],
                    ]);
                }
            }
        }
    }
}
