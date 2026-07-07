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
    public function __construct(private AiProvider $provider, private RagService $rag) {}

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

            $chunks = $this->rag->retrieve($text);

            $result = $this->provider->chat($this->buildHistory($conversation, $context, $chunks));

            $content = $result->success
                ? $result->content
                : 'Sorry — Aeon is temporarily unavailable. Please try again.';

            $blocks = [['type' => 'text', 'text' => $content]];
            if ($result->success && ! empty($chunks)) {
                $titles = array_values(array_unique(array_map(static fn ($c) => $c['title'], $chunks)));
                $blocks[] = ['type' => 'chips', 'variant' => 'source', 'items' => $titles];
            }

            $reply = $conversation->messages()->create([
                'role'     => 'assistant',
                'content'  => $content,
                'blocks'   => $blocks,
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
    private function buildHistory(Conversation $conversation, array $context = [], array $chunks = []): array
    {
        $system = (string) config('aeon.system_prompt');
        $preamble = $this->contextPreamble($context);
        if ($preamble !== '') {
            $system .= "\n\n# Live context (this session)\n".$preamble;
        }

        $grounding = $this->grounding($chunks);
        if ($grounding !== '') {
            $system .= "\n\n# Knowledge base — ground your answer in THIS install\n".$grounding;
        }

        $messages = [['role' => 'system', 'content' => $system]];

        foreach ($conversation->messages()->orderBy('id')->get() as $m) {
            $messages[] = ['role' => $m->role, 'content' => (string) $m->content];
        }

        return $messages;
    }

    /**
     * Turn retrieved chunks into a grounding block for the system prompt.
     *
     * @param  array<int,array{title:string,text:string}>  $chunks
     */
    private function grounding(array $chunks): string
    {
        if (empty($chunks)) {
            return '';
        }

        $out = "These are real facts about the current AEOS365 install (modules, pages, routes, permitted actions). "
            ."Prefer them over assumptions. If they don't cover the question, answer from general knowledge and say so. "
            ."Do NOT invent pages, buttons or actions that are not listed here.\n\n";
        foreach ($chunks as $c) {
            $out .= "### {$c['title']}\n{$c['text']}\n\n";
        }

        return $out;
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
