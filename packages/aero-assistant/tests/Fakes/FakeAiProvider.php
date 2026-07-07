<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Fakes;

use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;

class FakeAiProvider implements AiProvider
{
    public array $received = [];

    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        $this->received = $messages;

        return new AiChatResult(content: 'Hello from Aeon test', tokensUsed: 5, model: 'fake');
    }

    public function embed(array $texts, array $options = []): array
    {
        return array_map(fn () => array_fill(0, 768, 0.0), $texts);
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
