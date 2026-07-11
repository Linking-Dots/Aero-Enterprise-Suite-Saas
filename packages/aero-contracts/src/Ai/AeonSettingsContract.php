<?php

declare(strict_types=1);

namespace Aero\Contracts\Ai;

/**
 * Central control-plane settings for the Aeon assistant, resolved from the
 * platform (landlord) database. The assistant package depends only on this
 * contract; the platform package binds an implementation that reads
 * platform_settings.ai_settings. When nothing is bound (standalone edition,
 * isolated tests) the assistant falls back to its own config()/.env values.
 *
 * resolve() returns:
 *   [
 *     'enabled' => bool,
 *     'provider' => 'gemini'|'openai',
 *     'fast_model' => string, 'premium_model' => string,
 *     'api_key' => ?string, 'base_url' => ?string,
 *     'token_fuse_per_conversation' => int,
 *     'token_fuse_per_user_daily' => int,
 *     'max_tool_steps' => int,
 *   ]
 */
interface AeonSettingsContract
{
    /** @return array<string,mixed> */
    public function resolve(): array;
}
