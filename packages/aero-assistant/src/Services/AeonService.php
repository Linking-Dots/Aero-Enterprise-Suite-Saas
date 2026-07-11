<?php

declare(strict_types=1);

namespace Aero\Assistant\Services;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Models\Message;
use Aero\Assistant\Tools\ToolRegistry;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Aeon's agentic engine. One user turn runs a bounded tool loop:
 *
 *   model → tool call(s) → execute → feed results back → model … → final answer
 *
 * so the model can CHAIN (find a record, then act on it) and REASON over real
 * data (query results are returned to it, not just rendered). UI-terminal tools
 * (navigate, prepare_operation) end the loop — their output is a block the user
 * interacts with. Everything the tools produced is rendered as generative-UI
 * blocks; the model's final text leads the reply.
 */
class AeonService
{
    /** Tools whose output is a UI hand-off — the loop ends when one succeeds. */
    private const TERMINAL_TOOLS = ['prepare_operation'];

    public function __construct(
        private AiProvider $provider,
        private RagService $rag,
        private ToolRegistry $tools,
    ) {}

    /**
     * Run one chat turn: persist the user message, run the agent loop, persist
     * the assistant reply as generative-UI blocks.
     *
     * @param  array<string,mixed>  $context  who/where the user is (page, user_name, roles)
     * @param  callable|null  $onEvent  fn(string $stageLabel) — progress for streaming UIs
     * @return array{conversation: Conversation, reply: Message}
     */
    public function send(int $userId, ?int $conversationId, string $text, array $context = [], ?callable $onEvent = null): array
    {
        // Persist the conversation + user turn atomically, THEN call the model
        // (an external, multi-second, retrying HTTP call must not be held inside
        // a DB transaction).
        $conversation = DB::transaction(function () use ($userId, $conversationId, $text) {
            $conversation = $conversationId
                ? Conversation::where('user_id', $userId)->findOrFail($conversationId)
                : Conversation::create(['user_id' => $userId, 'title' => Str::limit($text, 40)]);
            $conversation->messages()->create(['role' => 'user', 'content' => $text]);

            return $conversation;
        });

        $emit = static function (string $label) use ($onEvent): void {
            if ($onEvent) {
                $onEvent($label);
            }
        };

        // Plan AI quota (SaaS): entitlement + monthly message allowance. Refuse
        // before spending anything; the message is a soft upsell, not an error.
        $quota = $this->quotaStatus();
        if ($quota !== null && ! ($quota['enabled'] ?? true)) {
            $msg = 'The AI assistant isn\'t included in your current plan. Ask your administrator to upgrade to unlock Aeon.';

            return $this->persistReply($conversation, [
                'content' => $msg, 'blocks' => [['type' => 'text', 'text' => $msg]],
                'tokens' => 0, 'tool_log' => [], 'model' => '',
            ]);
        }
        if ($quota !== null && ! ($quota['allowed'] ?? true)) {
            $msg = 'You\'ve used this month\'s AI message allowance ('.(int) ($quota['limit'] ?? 0).' messages). '
                .'It resets at the start of your next billing month — or ask your administrator to upgrade the plan for more.';

            return $this->persistReply($conversation, [
                'content' => $msg, 'blocks' => [['type' => 'text', 'text' => $msg]],
                'tokens' => 0, 'tool_log' => [], 'model' => '',
            ]);
        }

        // Daily token fuse — cost protection under the message quota.
        if ($this->overBudget($userId)) {
            $limitMsg = 'You\'ve reached today\'s Aeon usage limit — it resets at midnight. If you need more, ask your administrator to raise the Aeon budget.';

            return $this->persistReply($conversation, [
                'content' => $limitMsg,
                'blocks' => [['type' => 'text', 'text' => $limitMsg]],
                'tokens' => 0, 'tool_log' => [], 'model' => '',
            ]);
        }

        $emit('Consulting the knowledge base…');
        $chunks = $this->rag->retrieve($this->retrievalQuery($conversation, $text));

        $transcript = $this->buildHistory($conversation, $context, $chunks);
        $declarations = $this->tools->declarations();
        $maxLoops = max(1, (int) (\Aero\Assistant\Support\AeonConfig::resolve()['max_tool_steps'] ?? config('aeon.agent.max_loops', 5)));

        $blocks = [];        // generative-UI blocks accumulated from tools
        $toolLog = [];       // persisted transcript of tool activity
        $totalTokens = 0;
        $content = '';
        $model = '';
        $failed = false;
        $terminal = null;    // ['kind' => tool name, 'text' => fallback lead text]
        $navFailed = false;
        $usedTools = false;

        for ($loop = 0; $loop < $maxLoops; $loop++) {
            $emit($loop === 0 ? 'Thinking…' : 'Reasoning over the results…');
            $result = $this->provider->chat($transcript, $declarations);
            $totalTokens += $result->tokensUsed;
            $model = $result->model !== '' ? $result->model : $model;

            if (! $result->success) {
                $failed = true;
                break;
            }
            $content = trim($result->content);

            if (empty($result->toolCalls)) {
                break; // final answer
            }
            $usedTools = true;
            $transcript[] = ['role' => 'assistant', 'content' => $result->content, 'tool_calls' => $result->toolCalls];

            $responses = [];
            foreach ($result->toolCalls as $call) {
                $name = (string) ($call['name'] ?? '');
                $args = (array) ($call['args'] ?? []);
                $out = $this->executeToolCall($name, $args, $userId, $emit);

                $responses[] = ['name' => $name, 'response' => $out['response']];
                $toolLog[] = ['name' => $name, 'args' => $args, 'summary' => Str::limit((string) $out['summary'], 200)];
                foreach ($out['blocks'] as $b) {
                    // A retrying model can emit the same call twice — don't render twins.
                    if (! in_array($b, $blocks, true)) {
                        $blocks[] = $b;
                    }
                }
                if ($out['terminal']) {
                    $terminal = ['kind' => $name, 'text' => (string) $out['summary']];
                }
                if ($name === 'navigate') {
                    $navFailed = ($out['response']['status'] ?? '') === 'error';
                }
            }

            if ($terminal) {
                break; // a form/navigation is on screen — the user takes it from here
            }
            $transcript[] = ['role' => 'tool', 'results' => $responses];
        }

        $blocks = array_slice($blocks, 0, 10);

        // Lead text: the model's final words win; tool/terminal text is the fallback.
        if ($failed && $content === '' && empty($blocks)) {
            $content = 'Sorry — Aeon is temporarily unavailable. Please try again.';
        } elseif ($content === '') {
            $lastToolText = '';
            foreach (array_reverse($toolLog) as $entry) {
                if (! str_starts_with((string) ($entry['summary'] ?? ''), 'tool error')
                    && ! str_starts_with((string) ($entry['summary'] ?? ''), 'navigation failed')
                    && ($entry['summary'] ?? '') !== '') {
                    $lastToolText = (string) $entry['summary'];
                    break;
                }
            }
            $content = match (true) {
                $terminal !== null && $terminal['text'] !== '' => $terminal['text'],
                $lastToolText !== '' => $lastToolText,
                $navFailed => "I'm not certain which page you mean — tell me the section (e.g. Human Resources → Time & Attendance) and I'll take you there.",
                default => 'Okay.',
            };
        }

        $outBlocks = array_merge([['type' => 'text', 'text' => $content]], $blocks);
        if (! $failed && ! $usedTools && ! empty($chunks)) {
            $titles = array_values(array_unique(array_map(static fn ($c) => $c['title'], $chunks)));
            $outBlocks[] = ['type' => 'chips', 'variant' => 'source', 'items' => $titles];
        }

        // Count this delivered message against the tenant's monthly allowance
        // (only when the model actually produced a reply, not on provider failure).
        if (! $failed) {
            $this->recordQuota();
        }

        return $this->persistReply($conversation, [
            'content' => $content,
            'blocks' => $outBlocks,
            'tokens' => $totalTokens,
            'tool_log' => $toolLog,
            'model' => $model,
        ]);
    }

    /**
     * Execute one tool call and normalise the outcome.
     *
     * @param  array<string,mixed>  $args
     * @return array{response:array<string,mixed>,blocks:array<int,array<string,mixed>>,summary:string,terminal:bool}
     */
    private function executeToolCall(string $name, array $args, int $userId, callable $emit): array
    {
        if ($name === 'navigate') {
            $route = isset($args['route']) ? (string) $args['route'] : null;
            if ($this->tools->isValidRoute($route)) {
                $label = (string) ($args['label'] ?? ($this->tools->labelForRoute($route) ?? 'the page'));
                $emit("Opening {$label}…");

                return [
                    'response' => ['status' => 'shown', 'route' => $route, 'label' => $label],
                    'blocks' => [['type' => 'action', 'kind' => 'navigate', 'title' => $label, 'route' => $route, 'confirm_label' => 'Open →']],
                    'summary' => "Sure — here's {$label}.",
                    'terminal' => true,
                ];
            }

            // Invalid route → tell the model so it can correct from the knowledge base.
            return [
                'response' => ['status' => 'error', 'message' => 'Route "'.($route ?? '').'" does not exist. Use an exact route from the knowledge base.'],
                'blocks' => [],
                'summary' => 'navigation failed: unknown route '.($route ?? ''),
                'terminal' => false,
            ];
        }

        $tool = $this->tools->dataTool($name);
        if (! $tool) {
            return [
                'response' => ['status' => 'error', 'message' => "Unknown tool {$name}."],
                'blocks' => [],
                'summary' => "unknown tool {$name}",
                'terminal' => false,
            ];
        }

        $emit($name === 'prepare_operation' ? 'Preparing the form…' : 'Querying live data…');

        try {
            $out = $tool->run($args, $userId);
            $text = (string) ($out['text'] ?? 'Here you go.');
            $response = ['status' => 'ok', 'summary' => $text];
            if (isset($out['data'])) {
                $response['data'] = $out['data'];
            }

            return [
                'response' => $response,
                'blocks' => array_values((array) ($out['blocks'] ?? [])),
                'summary' => $text,
                // Tools may decide terminality per-call (e.g. prepare_operation
                // returns terminal=false when it needs the record id first).
                'terminal' => (bool) ($out['terminal'] ?? in_array($name, self::TERMINAL_TOOLS, true)),
            ];
        } catch (\Throwable $e) {
            Log::warning('Aeon tool failed', ['tool' => $name, 'error' => $e->getMessage()]);

            return [
                'response' => ['status' => 'error', 'message' => Str::limit($e->getMessage(), 160)],
                'blocks' => [],
                'summary' => 'tool error: '.Str::limit($e->getMessage(), 80),
                'terminal' => false,
            ];
        }
    }

    /**
     * Persist the assistant reply (blocks + tool activity + token spend).
     *
     * @param  array{content:string,blocks:array<int,array<string,mixed>>,tokens:int,tool_log:array<int,array<string,mixed>>,model:string}  $data
     * @return array{conversation: Conversation, reply: Message}
     */
    private function persistReply(Conversation $conversation, array $data): array
    {
        $reply = $conversation->messages()->create([
            'role'       => 'assistant',
            'content'    => $data['content'],
            'blocks'     => $data['blocks'],
            'tool_calls' => $data['tool_log'] ?: null,
            'tokens'     => $data['tokens'],
            'provider'   => config('aeon.provider'),
            'model'      => $data['model'],
        ]);

        return ['conversation' => $conversation, 'reply' => $reply];
    }

    /**
     * The current tenant's AI quota status (plan entitlement + monthly allowance),
     * or null when no quota is enforced (standalone edition / unbound / errors —
     * fail-open so a quota fault never blocks the assistant).
     *
     * @return array<string,mixed>|null
     */
    private function quotaStatus(): ?array
    {
        try {
            if (! function_exists('app') || ! app()->bound(\Aero\Contracts\Ai\AeonQuotaContract::class)) {
                return null;
            }

            return app(\Aero\Contracts\Ai\AeonQuotaContract::class)->status();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Count one delivered message against the tenant's monthly allowance. */
    private function recordQuota(): void
    {
        try {
            if (function_exists('app') && app()->bound(\Aero\Contracts\Ai\AeonQuotaContract::class)) {
                app(\Aero\Contracts\Ai\AeonQuotaContract::class)->record();
            }
        } catch (\Throwable) {
            // metering must never break the chat turn
        }
    }

    /** Whether the user has spent today's token fuse (0 = unlimited). */
    private function overBudget(int $userId): bool
    {
        // Central control-plane daily token fuse (cost protection under the
        // per-plan message quota), falling back to this package's config.
        $limit = (int) (\Aero\Assistant\Support\AeonConfig::resolve()['token_fuse_per_user_daily']
            ?? config('aeon.budget.daily_tokens_per_user', 0));
        if ($limit <= 0) {
            return false;
        }

        $used = (int) Message::whereIn(
            'conversation_id',
            Conversation::where('user_id', $userId)->select('id'),
        )->where('created_at', '>=', now()->startOfDay())->sum('tokens');

        return $used >= $limit;
    }

    /**
     * Retrieval query: terse follow-ups ("what about types?") embed poorly, so
     * fold in the previous user message for context.
     */
    private function retrievalQuery(Conversation $conversation, string $text): string
    {
        if (str_word_count($text) >= 4) {
            return $text;
        }
        $prev = $conversation->messages()
            ->where('role', 'user')
            ->orderByDesc('id')->skip(1)->value('content');

        return $prev ? $prev.' — '.$text : $text;
    }

    /**
     * Build canonical message history (system prompt + live context + prior
     * turns). History is windowed, and prior tool activity is replayed as a
     * compact suffix so follow-ups keep their grounding.
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

        $window = max(2, (int) config('aeon.agent.history_window', 30));
        $recent = $conversation->messages()->orderByDesc('id')->limit($window)->get()->reverse();

        foreach ($recent as $m) {
            $content = (string) $m->content;
            if ($m->role === 'assistant' && ! empty($m->tool_calls)) {
                $content .= "\n\n[Tools used: ".$this->toolLogSummary((array) $m->tool_calls).']';
            }
            $messages[] = ['role' => $m->role, 'content' => $content];
        }

        return $messages;
    }

    /** @param array<int,array<string,mixed>> $log */
    private function toolLogSummary(array $log): string
    {
        $bits = [];
        foreach (array_slice($log, 0, 5) as $entry) {
            $args = [];
            foreach ((array) ($entry['args'] ?? []) as $k => $v) {
                if (is_scalar($v)) {
                    $args[] = $k.'='.Str::limit((string) $v, 30);
                }
            }
            $bits[] = ($entry['name'] ?? '?').'('.implode(', ', $args).') → '.($entry['summary'] ?? '');
        }

        return implode('; ', $bits);
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
