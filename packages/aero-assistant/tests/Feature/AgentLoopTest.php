<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Services\AeonService;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Assistant\Tools\ToolRegistry;
use Aero\Contracts\Ai\AeonToolContract;
use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Collection;

/** A provider that plays a fixed script: one AiChatResult per model call. */
class ScriptedProvider implements AiProvider
{
    public array $transcripts = [];

    public function __construct(private array $script) {}

    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        $this->transcripts[] = $messages;

        return array_shift($this->script) ?? new AiChatResult(content: 'done');
    }

    public function embed(array $texts, array $options = []): array
    {
        return [];
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

class AgentLoopTest extends PackageTestCase
{
    private function countTool(): AeonToolContract
    {
        return new class implements AeonToolContract
        {
            public function name(): string { return 'demo_count'; }

            public function description(): string { return 'count things'; }

            public function parameters(): array { return []; }

            public function run(array $args, ?int $userId): array
            {
                return [
                    'text' => '42 employees.',
                    'blocks' => [['type' => 'stats', 'items' => [['k' => 'Employees', 'v' => '42']]]],
                    'data' => ['total' => 42],
                ];
            }
        };
    }

    public function test_tool_results_are_fed_back_and_model_composes_final_answer(): void
    {
        $provider = new ScriptedProvider([
            new AiChatResult(content: '', toolCalls: [['name' => 'demo_count', 'args' => []]], tokensUsed: 10),
            new AiChatResult(content: 'You have 42 employees — a solid mid-size team.', tokensUsed: 7),
        ]);
        $this->app->instance(AiProvider::class, $provider);
        $this->app->instance(ToolRegistry::class, new ToolRegistry([$this->countTool()]));

        $out = $this->app->make(AeonService::class)->send(1, null, 'how big is my team?');

        // Model's ANALYTICAL final text leads; the tool's block still renders.
        $blocks = new Collection($out['reply']->blocks);
        $this->assertSame('You have 42 employees — a solid mid-size team.', $blocks->firstWhere('type', 'text')['text']);
        $this->assertNotNull($blocks->firstWhere('type', 'stats'));

        // Second model call received the tool result (agentic round-trip).
        $this->assertCount(2, $provider->transcripts);
        $second = $provider->transcripts[1];
        $toolTurn = collect($second)->firstWhere('role', 'tool');
        $this->assertNotNull($toolTurn, 'tool results must be fed back to the model');
        $this->assertSame(42, $toolTurn['results'][0]['response']['data']['total']);

        // Token spend accumulates across the loop.
        $this->assertSame(17, $out['reply']->tokens);
        // Tool activity is persisted for grounded follow-ups.
        $this->assertSame('demo_count', $out['reply']->tool_calls[0]['name']);
    }

    public function test_loop_is_bounded_by_max_loops(): void
    {
        config()->set('aeon.agent.max_loops', 3);

        // Model calls the tool forever — the loop must cut it off.
        $provider = new class implements AiProvider
        {
            public int $calls = 0;

            public function chat(array $m, array $t = [], array $o = []): AiChatResult
            {
                $this->calls++;

                return new AiChatResult(content: '', toolCalls: [['name' => 'demo_count', 'args' => []]]);
            }

            public function embed(array $texts, array $o = []): array { return []; }

            public function isAvailable(): bool { return true; }
        };
        $this->app->instance(AiProvider::class, $provider);
        $this->app->instance(ToolRegistry::class, new ToolRegistry([$this->countTool()]));

        $out = $this->app->make(AeonService::class)->send(1, null, 'loop forever');

        $this->assertSame(3, $provider->calls);
        // Without final model text, the tool's own summary leads the reply.
        $this->assertSame('42 employees.', $out['reply']->content);
    }

    public function test_daily_token_budget_refuses_before_calling_the_model(): void
    {
        config()->set('aeon.budget.daily_tokens_per_user', 100);

        $provider = new ScriptedProvider([
            new AiChatResult(content: 'first answer', tokensUsed: 150),
        ]);
        $this->app->instance(AiProvider::class, $provider);

        $svc = $this->app->make(AeonService::class);
        $first = $svc->send(1, null, 'expensive question');
        $this->assertSame('first answer', $first['reply']->content);

        // Budget now exhausted — the next turn must refuse without a model call.
        $second = $svc->send(1, $first['conversation']->id, 'another question');
        $this->assertStringContainsString('usage limit', $second['reply']->content);
        $this->assertCount(1, $provider->transcripts, 'no model call may happen over budget');
    }
}
