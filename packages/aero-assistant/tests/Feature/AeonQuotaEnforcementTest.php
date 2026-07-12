<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Message;
use Aero\Assistant\Services\AeonService;
use Aero\Assistant\Tests\Fakes\FakeAeonQuota;
use Aero\Assistant\Tests\Fakes\FakeAiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Ai\AeonQuotaContract;
use Aero\Contracts\Ai\AiProvider;

class AeonQuotaEnforcementTest extends PackageTestCase
{
    private function bindQuota(array $status): FakeAeonQuota
    {
        $quota = new FakeAeonQuota($status);
        $this->app->instance(AeonQuotaContract::class, $quota);

        return $quota;
    }

    public function test_refuses_when_ai_not_entitled_without_calling_the_model(): void
    {
        $provider = new FakeAiProvider();
        $this->app->instance(AiProvider::class, $provider);
        $quota = $this->bindQuota(['enabled' => false]);

        $out = $this->app->make(AeonService::class)->send(1, null, 'hi');

        $this->assertStringContainsString("isn't included in your current plan", $out['reply']->content);
        $this->assertSame([], $provider->received, 'model must not be called when not entitled');
        $this->assertSame(0, $quota->recorded, 'nothing is metered on refusal');
    }

    public function test_refuses_when_monthly_allowance_is_used(): void
    {
        $provider = new FakeAiProvider();
        $this->app->instance(AiProvider::class, $provider);
        $quota = $this->bindQuota(['enabled' => true, 'allowed' => false, 'limit' => 200]);

        $out = $this->app->make(AeonService::class)->send(1, null, 'hi');

        $this->assertStringContainsString("used this month's AI message allowance", $out['reply']->content);
        $this->assertStringContainsString('200', $out['reply']->content);
        $this->assertSame([], $provider->received);
        $this->assertSame(0, $quota->recorded);
    }

    public function test_answers_and_meters_one_message_when_within_allowance(): void
    {
        $this->app->instance(AiProvider::class, new FakeAiProvider());
        $quota = $this->bindQuota(['enabled' => true, 'allowed' => true, 'limit' => 200, 'used' => 5]);

        $out = $this->app->make(AeonService::class)->send(1, null, 'hi');

        $this->assertSame('Hello from Aeon test', $out['reply']->content);
        $this->assertSame(1, $quota->recorded, 'one delivered message is counted');
        $this->assertSame(2, Message::count()); // user + assistant
    }

    public function test_unmetered_when_no_quota_contract_is_bound(): void
    {
        // Standalone / unbound: the assistant answers normally, nothing to meter.
        $this->app->forgetInstance(AeonQuotaContract::class);
        $this->app->instance(AiProvider::class, new FakeAiProvider());

        $out = $this->app->make(AeonService::class)->send(1, null, 'hi');

        $this->assertSame('Hello from Aeon test', $out['reply']->content);
    }
}
