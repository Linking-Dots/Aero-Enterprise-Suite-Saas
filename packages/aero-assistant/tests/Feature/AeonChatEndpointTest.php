<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Message;
use Aero\Assistant\Tests\Fakes\FakeAiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Assistant\Tests\Stubs\User;
use Aero\Contracts\Ai\AiProvider;

class AeonChatEndpointTest extends PackageTestCase
{
    public function test_post_message_returns_reply_blocks_and_persists(): void
    {
        $this->app->instance(AiProvider::class, new FakeAiProvider());

        $response = $this->actingAs(new User(['id' => 7]))
            ->withoutMiddleware()
            ->postJson('/aeon/message', ['message' => 'Hello Aeon']);

        $response->assertOk()
            ->assertJsonPath('reply.role', 'assistant')
            ->assertJsonPath('reply.blocks.0.type', 'text')
            ->assertJsonPath('reply.blocks.0.text', 'Hello from Aeon test');

        $this->assertSame(2, Message::count());
    }

    public function test_message_is_required(): void
    {
        $this->app->instance(AiProvider::class, new FakeAiProvider());

        $this->actingAs(new User(['id' => 7]))
            ->withoutMiddleware()
            ->postJson('/aeon/message', ['message' => ''])
            ->assertStatus(422);
    }
}
