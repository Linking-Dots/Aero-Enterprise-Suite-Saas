<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Assistant\Tests\Stubs\User;

class FeedbackEndpointTest extends PackageTestCase
{
    private function replyFor(int $userId): array
    {
        $conversation = Conversation::create(['user_id' => $userId, 'title' => 't']);
        $message = $conversation->messages()->create(['role' => 'assistant', 'content' => 'hi', 'blocks' => []]);

        return [$conversation, $message];
    }

    public function test_owner_can_rate_and_clear_feedback(): void
    {
        [, $message] = $this->replyFor(7);

        $this->actingAs(new User(['id' => 7]))
            ->withoutMiddleware()
            ->postJson("/aeon/messages/{$message->id}/feedback", ['value' => 1])
            ->assertOk()
            ->assertJsonPath('feedback', 1);

        $this->assertSame(1, $message->fresh()->feedback);

        $this->actingAs(new User(['id' => 7]))
            ->withoutMiddleware()
            ->postJson("/aeon/messages/{$message->id}/feedback", ['value' => 0])
            ->assertOk();

        $this->assertNull($message->fresh()->feedback);
    }

    public function test_other_users_cannot_rate_someone_elses_conversation(): void
    {
        [, $message] = $this->replyFor(7);

        $this->actingAs(new User(['id' => 8]))
            ->withoutMiddleware()
            ->postJson("/aeon/messages/{$message->id}/feedback", ['value' => -1])
            ->assertStatus(403);
    }
}
