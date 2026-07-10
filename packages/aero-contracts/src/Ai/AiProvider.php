<?php

declare(strict_types=1);

namespace Aero\Contracts\Ai;

/**
 * Provider-agnostic AI model interface. Feature code depends only on this.
 * Drivers (Gemini, OpenAI-compatible, …) live in aero-assistant.
 *
 * Message shape (canonical, provider adapts to its own wire format):
 *   ['role' => 'system'|'user'|'assistant'|'tool', 'content' => string]
 *   assistant turns MAY carry 'tool_calls' => [['name' => string, 'args' => array], …]
 *   tool turns carry 'results' => [['name' => string, 'response' => array], …]
 *     answering the immediately preceding assistant turn's tool_calls, in order.
 *
 * Tool shape (neutral): [['name' => string, 'description' => string,
 *   'parameters' => array JSON-schema properties], …]
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
