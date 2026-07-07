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
     * @param  array<string,mixed>  $context  who/where the user is (page, user_name, roles)
     * @return array{conversation: Conversation, reply: Message}
     */
    public function send(int $userId, ?int $conversationId, string $text, array $context = []): array
    {
        return DB::transaction(function () use ($userId, $conversationId, $text, $context) {
            $conversation = $conversationId
                ? Conversation::where('user_id', $userId)->findOrFail($conversationId)
                : Conversation::create(['user_id' => $userId, 'title' => Str::limit($text, 40)]);

            $conversation->messages()->create(['role' => 'user', 'content' => $text]);

            $result = $this->provider->chat($this->buildHistory($conversation, $context));

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
     * Build canonical message history (system prompt + live context + prior turns).
     *
     * @param  array<string,mixed>  $context
     * @return array<int,array{role:string,content:string}>
     */
    private function buildHistory(Conversation $conversation, array $context = []): array
    {
        $system = (string) config('aeon.system_prompt');
        $preamble = $this->contextPreamble($context);
        if ($preamble !== '') {
            $system .= "\n\n# Live context (this session)\n".$preamble;
        }

        $messages = [['role' => 'system', 'content' => $system]];

        foreach ($conversation->messages()->orderBy('id')->get() as $m) {
            $messages[] = ['role' => $m->role, 'content' => (string) $m->content];
        }

        return $messages;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function contextPreamble(array $context): string
    {
        $bits = [];
        if (! empty($context['user_name'])) {
            $bits[] = 'You are speaking with '.$context['user_name'].'.';
        }
        if (! empty($context['roles'])) {
            $bits[] = 'Their role(s): '.implode(', ', (array) $context['roles']).'.';
        }
        if (! empty($context['page'])) {
            $bits[] = 'They are currently viewing the page "'.$context['page'].'" — tailor guidance to where they are when relevant.';
        }

        return implode(' ', $bits);
    }
}
