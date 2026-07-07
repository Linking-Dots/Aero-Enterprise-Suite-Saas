<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Models\Message;
use Aero\Assistant\Services\AeonService;
use Aero\Assistant\Tests\Fakes\FakeAiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Ai\AiProvider;

class AeonServiceTest extends PackageTestCase
{
    public function test_send_persists_user_and_assistant_messages(): void
    {
        $this->app->instance(AiProvider::class, new FakeAiProvider());

        $out = $this->app->make(AeonService::class)->send(1, null, 'How do I add an employee?');

        $this->assertInstanceOf(Conversation::class, $out['conversation']);
        $this->assertInstanceOf(Message::class, $out['reply']);
        $this->assertSame(1, Conversation::count());
        $this->assertSame(2, Message::count()); // user + assistant
        $this->assertSame('Hello from Aeon test', $out['reply']->content);
        $this->assertSame('text', $out['reply']->blocks[0]['type']);
        $this->assertSame('assistant', $out['reply']->role);
        $this->assertSame('fake', $out['reply']->model);
    }

    public function test_send_reuses_existing_conversation_and_sends_history(): void
    {
        $fake = new FakeAiProvider();
        $this->app->instance(AiProvider::class, $fake);
        $svc = $this->app->make(AeonService::class);

        $first = $svc->send(1, null, 'hi');
        $svc->send(1, $first['conversation']->id, 'again');

        $this->assertSame(1, Conversation::count());
        $this->assertSame(4, Message::count());
        // history passed to provider begins with the system prompt
        $this->assertSame('system', $fake->received[0]['role']);
    }
}
