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

class AeonDataToolTest extends PackageTestCase
{
    public function test_data_tool_call_emits_the_tools_blocks(): void
    {
        $tool = new class implements AeonToolContract
        {
            public function name(): string { return 'demo_stats'; }

            public function description(): string { return 'demo'; }

            public function parameters(): array { return []; }

            public function run(array $args, ?int $userId): array
            {
                return [
                    'text' => '2 users total.',
                    'blocks' => [['type' => 'stats', 'items' => [['k' => 'Total', 'v' => '2']]]],
                ];
            }
        };
        $this->app->instance(ToolRegistry::class, new ToolRegistry([$tool]));

        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function chat(array $m, array $t = [], array $o = []): AiChatResult
            {
                return new AiChatResult(content: '', toolCalls: [['name' => 'demo_stats', 'args' => []]]);
            }

            public function embed(array $texts, array $o = []): array { return []; }

            public function isAvailable(): bool { return true; }
        });

        $out = $this->app->make(AeonService::class)->send(1, null, 'how many users?');
        $blocks = new Collection($out['reply']->blocks);

        $this->assertSame('2 users total.', $blocks->firstWhere('type', 'text')['text']);
        $stats = $blocks->firstWhere('type', 'stats');
        $this->assertNotNull($stats, 'expected a stats block from the tool');
        $this->assertSame('2', $stats['items'][0]['v']);
    }
}
