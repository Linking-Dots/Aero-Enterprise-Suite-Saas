<?php

declare(strict_types=1);

namespace Aero\Contracts\Ai;

/** Immutable result of one AI chat turn (provider-agnostic). */
final class AiChatResult
{
    public function __construct(
        public readonly string $content,
        public readonly array $toolCalls = [],
        public readonly int $tokensUsed = 0,
        public readonly string $model = '',
        public readonly bool $success = true,
        public readonly ?string $error = null,
    ) {}

    public static function failed(string $error, string $model = ''): self
    {
        return new self(content: '', toolCalls: [], tokensUsed: 0, model: $model, success: false, error: $error);
    }
}
