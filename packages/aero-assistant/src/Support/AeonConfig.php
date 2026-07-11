<?php

declare(strict_types=1);

namespace Aero\Assistant\Support;

use Aero\Contracts\Ai\AeonSettingsContract;

/**
 * Single resolution point for Aeon's runtime settings. Prefers the platform
 * control-plane (AeonSettingsContract, bound by aero-platform → reads
 * platform_settings.ai_settings) so the operator governs provider/models/key/
 * limits centrally; falls back to this package's own config()/.env when nothing
 * is bound (standalone edition, isolated tests).
 */
class AeonConfig
{
    /** @return array<string,mixed> */
    public static function resolve(): array
    {
        $central = self::fromContract();
        if ($central !== null) {
            return $central;
        }

        return [
            'enabled' => (bool) config('aeon.enabled', true),
            'provider' => (string) config('aeon.provider', 'gemini'),
            'fast_model' => (string) config('aeon.providers.gemini.model', 'gemini-flash-latest'),
            'premium_model' => (string) config('aeon.providers.gemini.model', 'gemini-flash-latest'),
            'api_key' => config('aeon.providers.gemini.api_key'),
            'base_url' => config('aeon.providers.openai.base_url'),
            'token_fuse_per_conversation' => (int) config('aeon.providers.gemini.max_tokens', 8000),
            'token_fuse_per_user_daily' => (int) config('aeon.budget.daily_tokens_per_user', 250000),
            'max_tool_steps' => (int) config('aeon.agent.max_loops', 5),
        ];
    }

    /** @return array<string,mixed>|null */
    private static function fromContract(): ?array
    {
        try {
            if (! function_exists('app') || ! app()->bound(AeonSettingsContract::class)) {
                return null;
            }
            $resolved = app(AeonSettingsContract::class)->resolve();

            return is_array($resolved) && $resolved !== [] ? $resolved : null;
        } catch (\Throwable) {
            return null; // never let settings resolution break a chat turn
        }
    }
}
