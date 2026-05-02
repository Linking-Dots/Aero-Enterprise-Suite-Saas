<?php

declare(strict_types=1);

namespace Aero\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = ['key','value','description','tenant_id'];
    protected $casts = ['value' => 'json'];

    public static function getValue(string $key, mixed $default = null): mixed { return static::where('key', $key)->value('value') ?? $default; }
    public static function setValue(string $key, mixed $value, ?string $description = null, ?int $tenantId = null): static { return static::updateOrCreate(['key' => $key, 'tenant_id' => $tenantId], ['value' => $value, 'description' => $description]); }
    public static function isChannelEnabled(string $channel, ?int $tenantId = null): bool { return (bool) static::getValue("channel.{$channel}.enabled", true); }
    public static function getRetryConfig(): array { return static::getValue('retry_config', ['max_attempts' => 3, 'backoff' => [1,5,15]]); }
}
