<?php

declare(strict_types=1);

namespace Aero\Contracts\Ai;

/**
 * Provider-agnostic AI model interface. Feature code depends only on this.
 * Drivers (Gemini, OpenAI, Anthropic, OpenAI-compatible) live in aero-assistant.
 *
 * @param array<int,array{role:string,content:string}> $messages
 */
interface AiProvider
{
    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult;

    /**
     * @param  array<int,string>  $texts
     * @return array<int,array<int,float>> one vector per text
     */
    public function embed(array $texts, array $options = []): array;

    public function isAvailable(): bool;
}
