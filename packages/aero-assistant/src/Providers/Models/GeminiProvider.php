<?php

declare(strict_types=1);

namespace Aero\Assistant\Providers\Models;

use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;

class GeminiProvider implements AiProvider
{
    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        return AiChatResult::failed('not implemented', config('aeon.providers.gemini.model', ''));
    }

    public function embed(array $texts, array $options = []): array
    {
        return [];
    }

    public function isAvailable(): bool
    {
        return false;
    }
}
