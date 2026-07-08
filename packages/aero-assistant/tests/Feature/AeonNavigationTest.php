<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Services\AeonService;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Collection;

class AeonNavigationTest extends PackageTestCase
{
    private function setModules(): void
    {
        config()->set('modules.hrm', [
            'name' => 'Human Resources',
            'nav_groups' => ['time' => ['label' => 'Time & Attendance']],
            'nav_group_map' => ['leaves' => 'time'],
            'submodules' => [[
                'code' => 'leaves',
                'name' => 'Leaves',
                'route' => '/hrm/leave/applications',
                'components' => [[
                    'code' => 'leave-types',
                    'name' => 'Leave Types',
                    'route' => '/hrm/leave/types',
                    'actions' => [],
                ]],
            ]],
        ]);
    }

    private function providerReturning(array $toolCalls, string $text = ''): AiProvider
    {
        return new class($toolCalls, $text) implements AiProvider
        {
            public function __construct(private array $toolCalls, private string $text) {}

            public function chat(array $m, array $t = [], array $o = []): AiChatResult
            {
                return new AiChatResult(content: $this->text, toolCalls: $this->toolCalls);
            }

            public function embed(array $texts, array $o = []): array
            {
                return [];
            }

            public function isAvailable(): bool
            {
                return true;
            }
        };
    }

    public function test_valid_navigate_call_produces_navigate_action_block(): void
    {
        $this->setModules();
        $this->app->instance(AiProvider::class, $this->providerReturning(
            [['name' => 'navigate', 'args' => ['route' => '/hrm/leave/types', 'label' => 'Leave Types']]],
            'Taking you there.'
        ));

        $out = $this->app->make(AeonService::class)->send(1, null, 'open leave types');

        $action = (new Collection($out['reply']->blocks))->firstWhere('type', 'action');
        $this->assertNotNull($action, 'expected an action block');
        $this->assertSame('navigate', $action['kind']);
        $this->assertSame('/hrm/leave/types', $action['route']);
    }

    public function test_navigate_to_unknown_route_is_rejected(): void
    {
        $this->setModules();
        $this->app->instance(AiProvider::class, $this->providerReturning(
            [['name' => 'navigate', 'args' => ['route' => '/hrm/made/up', 'label' => 'Nope']]],
            ''
        ));

        $out = $this->app->make(AeonService::class)->send(1, null, 'take me to a fake page');

        $action = (new Collection($out['reply']->blocks))->firstWhere('type', 'action');
        $this->assertNull($action, 'invalid route must not produce a navigate action');
    }
}
