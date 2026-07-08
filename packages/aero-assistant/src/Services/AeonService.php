<?php

declare(strict_types=1);

namespace Aero\Assistant\Services;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Models\Message;
use Aero\Assistant\Tools\ToolRegistry;
use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AeonService
{
    public function __construct(
        private AiProvider $provider,
        private RagService $rag,
        private ToolRegistry $tools,
    ) {}

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

            $result = $this->provider->chat(
                $this->buildHistory($conversation, $context, $chunks),
                $this->tools->declarations(),
            );

            $nav = $this->firstValidNavigate($result);
            $navAttempted = $this->attemptedNavigate($result);
            $dataTool = $nav ? null : $this->firstDataTool($result);

            if ($nav) {
                $content = trim($result->content) !== '' ? $result->content : "Sure — here's {$nav['label']}.";
                $blocks = [
                    ['type' => 'text', 'text' => $content],
                    ['type' => 'action', 'kind' => 'navigate', 'title' => $nav['label'], 'route' => $nav['route'], 'confirm_label' => 'Open →'],
                ];
            } elseif ($dataTool) {
                $out = $this->runDataTool($dataTool, $userId);
                $content = $out['text'];
                $blocks = array_merge([['type' => 'text', 'text' => $content]], $out['blocks']);
            } elseif ($navAttempted) {
                $content = trim($result->content) !== ''
                    ? $result->content
                    : "I'm not certain which page you mean — tell me the section (e.g. Human Resources → Time & Attendance) and I'll take you there.";
                $blocks = [['type' => 'text', 'text' => $content]];
            } else {
                $content = $result->success
                    ? (trim($result->content) !== '' ? $result->content : 'Okay.')
                    : 'Sorry — Aeon is temporarily unavailable. Please try again.';
                $blocks = [['type' => 'text', 'text' => $content]];
                if ($result->success && ! empty($chunks)) {
                    $titles = array_values(array_unique(array_map(static fn ($c) => $c['title'], $chunks)));
                    $blocks[] = ['type' => 'chips', 'variant' => 'source', 'items' => $titles];
                }
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
     * First navigate tool-call whose route is real (validated against the module registry).
     *
     * @return array{route:string,label:string}|null
     */
    private function firstValidNavigate(AiChatResult $result): ?array
    {
        foreach ($result->toolCalls as $call) {
            if (($call['name'] ?? '') !== 'navigate') {
                continue;
            }
            $route = $call['args']['route'] ?? null;
            if ($this->tools->isValidRoute($route)) {
                return [
                    'route' => $route,
                    'label' => $call['args']['label'] ?? ($this->tools->labelForRoute($route) ?? 'the page'),
                ];
            }
        }

        return null;
    }

    private function attemptedNavigate(AiChatResult $result): bool
    {
        foreach ($result->toolCalls as $call) {
            if (($call['name'] ?? '') === 'navigate') {
                return true;
            }
        }

        return false;
    }

    /**
     * First tool-call that matches a registered data tool.
     *
     * @return array{name:string,args:array<string,mixed>}|null
     */
    private function firstDataTool(AiChatResult $result): ?array
    {
        foreach ($result->toolCalls as $call) {
            $name = $call['name'] ?? '';
            if ($name !== 'navigate' && $this->tools->dataTool($name)) {
                return ['name' => $name, 'args' => (array) ($call['args'] ?? [])];
            }
        }

        return null;
    }

    /**
     * @param  array{name:string,args:array<string,mixed>}  $call
     * @return array{text:string,blocks:array<int,array<string,mixed>>}
     */
    private function runDataTool(array $call, int $userId): array
    {
        try {
            $result = $this->tools->dataTool($call['name'])->run($call['args'], $userId);

            return [
                'text' => (string) ($result['text'] ?? 'Here you go.'),
                'blocks' => array_values((array) ($result['blocks'] ?? [])),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Aeon data tool failed', ['tool' => $call['name'], 'error' => $e->getMessage()]);

            return ['text' => "I couldn't pull those numbers just now — please try again.", 'blocks' => []];
        }
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
