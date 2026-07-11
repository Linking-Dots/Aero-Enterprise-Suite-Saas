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
                    ['functionCall' => ['name' => 'navigate', 'args' => ['route' => '/hrm/leave/types', 'label' => 'Leave Types']], 'thoughtSignature' => 'sig-abc'],
                ]]]],
            ], 200),
        ]);

        $result = (new GeminiProvider())->chat(
            [['role' => 'user', 'content' => 'open leave types']],
            [['name' => 'navigate', 'description' => 'go', 'parameters' => ['route' => ['type' => 'string']]]],
        );

        $this->assertSame('Sure', $result->content);
        $this->assertCount(1, $result->toolCalls);
        $this->assertSame('navigate', $result->toolCalls[0]['name']);
        $this->assertSame('/hrm/leave/types', $result->toolCalls[0]['args']['route']);
        $this->assertSame('sig-abc', $result->toolCalls[0]['sig']);

        Http::assertSent(fn ($request) => isset($request['tools'])
            && $request['tools'][0]['functionDeclarations'][0]['name'] === 'navigate');
    }

    public function test_chat_serialises_tool_turns_as_function_call_and_response(): void
    {
        Http::fake([
            '*generativelanguage*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'There are 3.']]]]],
            ], 200),
        ]);

        (new GeminiProvider())->chat([
            ['role' => 'user', 'content' => 'how many employees?'],
            ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'query_data', 'args' => ['entity' => 'employees'], 'sig' => 'sig-abc']]],
            ['role' => 'tool', 'results' => [['name' => 'query_data', 'response' => ['status' => 'ok', 'data' => ['total' => 3]]]]],
        ]);

        Http::assertSent(function ($request) {
            $contents = $request['contents'];
            // args/response are cast to objects in the payload (JSON object shape).
            $response = (array) $contents[2]['parts'][0]['functionResponse']['response'];

            return $contents[1]['role'] === 'model'
                && $contents[1]['parts'][0]['functionCall']['name'] === 'query_data'
                && $contents[1]['parts'][0]['thoughtSignature'] === 'sig-abc'
                && $contents[2]['role'] === 'user'
                && $contents[2]['parts'][0]['functionResponse']['name'] === 'query_data'
                && $response['data']['total'] === 3;
        });
    }

    public function test_chat_returns_failed_result_on_http_error(): void
    {
        Http::fake(['*generativelanguage*' => Http::response(['error' => 'nope'], 429)]);

        $result = (new GeminiProvider())->chat([['role' => 'user', 'content' => 'x']]);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }
}
