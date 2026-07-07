<?php

declare(strict_types=1);

namespace Aero\Assistant\Services;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Models\Message;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AeonService
{
    public function __construct(private AiProvider $provider) {}

    /**
     * Run one chat turn: persist the user message, call the model, persist the
     * assistant reply as generative-UI blocks.
     *
     * @return array{conversation: Conversation, reply: Message}
     */
    public function send(int $userId, ?int $conversationId, string $text): array
    {
        return DB::transaction(function () use ($userId, $conversationId, $text) {
            $conversation = $conversationId
                ? Conversation::where('user_id', $userId)->findOrFail($conversationId)
                : Conversation::create(['user_id' => $userId, 'title' => Str::limit($text, 40)]);

            $conversation->messages()->create(['role' => 'user', 'content' => $text]);

            $result = $this->provider->chat($this->buildHistory($conversation));

            $content = $result->success
                ? $result->content
                : 'Sorry — Aeon is temporarily unavailable. Please try again.';

            $reply = $conversation->messages()->create([
                'role'     => 'assistant',
                'content'  => $content,
                'blocks'   => [['type' => 'text', 'text' => $content]],
                'tokens'   => $result->tokensUsed,
                'provider' => config('aeon.provider'),
                'model'    => $result->model,
            ]);

            return ['conversation' => $conversation, 'reply' => $reply];
        });
    }

    /**
     * Build canonical message history (system prompt + prior turns).
     *
     * @return array<int,array{role:string,content:string}>
     */
    private function buildHistory(Conversation $conversation): array
    {
        $messages = [[
            'role' => 'system',
            'content' => (string) config('aeon.system_prompt'),
        ]];

        foreach ($conversation->messages()->orderBy('id')->get() as $m) {
            $messages[] = ['role' => $m->role, 'content' => (string) $m->content];
        }

        return $messages;
    }
}
