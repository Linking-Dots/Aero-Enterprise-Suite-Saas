<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Unit;

use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Illuminate\Support\Facades\Http;

class GeminiProviderTest extends PackageTestCase
{
    public function test_chat_maps_messages_and_parses_reply(): void
    {
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Hi there']]]]],
                'usageMetadata' => ['totalTokenCount' => 12],
            ], 200),
        ]);

        $result = (new GeminiProvider())->chat([
            ['role' => 'system', 'content' => 'You are Aeon.'],
            ['role' => 'user', 'content' => 'hello'],
        ]);

        $this->assertTrue($result->success);
        $this->assertSame('Hi there', $result->content);
        $this->assertSame(12, $result->tokensUsed);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), ':generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && $body['systemInstruction']['parts'][0]['text'] === 'You are Aeon.'
                && $body['contents'][0]['role'] === 'user'
                && $body['contents'][0]['parts'][0]['text'] === 'hello';
        });
    }

    public function test_chat_parses_function_calls(): void
    {
        Http::fake([
            '*generativelanguage*' => Http::response([
                'candidates' => [['content' => ['parts' => [
                    ['text' => 'Sure'],
                    ['functionCall' => ['name' => 'navigate', 'args' => ['route' => '/hrm/leave/types', 'label' => 'Leave Types']]],
                ]]]],
            ], 200),
        ]);

        $result = (new GeminiProvider())->chat(
            [['role' => 'user', 'content' => 'open leave types']],
            [['functionDeclarations' => [['name' => 'navigate']]]],
        );

        $this->assertSame('Sure', $result->content);
        $this->assertCount(1, $result->toolCalls);
        $this->assertSame('navigate', $result->toolCalls[0]['name']);
        $this->assertSame('/hrm/leave/types', $result->toolCalls[0]['args']['route']);

        Http::assertSent(fn ($request) => isset($request['tools']));
    }

    public function test_chat_returns_failed_result_on_http_error(): void
    {
        Http::fake(['*generativelanguage*' => Http::response(['error' => 'nope'], 429)]);

        $result = (new GeminiProvider())->chat([['role' => 'user', 'content' => 'x']]);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }
}
