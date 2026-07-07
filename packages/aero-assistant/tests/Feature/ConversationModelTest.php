<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Tests\PackageTestCase;

class ConversationModelTest extends PackageTestCase
{
    public function test_conversation_has_messages_with_json_blocks(): void
    {
        $c = Conversation::create(['user_id' => 1, 'title' => 'First chat']);
        $c->messages()->create([
            'role' => 'assistant',
            'content' => 'hi',
            'blocks' => [['type' => 'text', 'text' => 'hi']],
            'tokens' => 3,
            'provider' => 'gemini',
            'model' => 'gemini-flash-latest',
        ]);

        $c->refresh();
        $this->assertCount(1, $c->messages);
        $this->assertSame('text', $c->messages->first()->blocks[0]['type']);
        $this->assertSame(1, $c->user_id);
    }
}
