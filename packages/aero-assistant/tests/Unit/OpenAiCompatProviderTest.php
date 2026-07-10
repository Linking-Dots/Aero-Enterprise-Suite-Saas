<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Unit;

use Aero\Assistant\Providers\Models\OpenAiCompatProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Illuminate\Support\Facades\Http;

class OpenAiCompatProviderTest extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('aeon.providers.openai', [
            'api_key' => 'test-key',
            'model' => 'gpt-test',
            'base_url' => 'https://api.openai.test/v1',
            'timeout' => 5,
        ]);
    }

    public function test_chat_maps_messages_tools_and_parses_tool_calls(): void
    {
        Http::fake([
            'api.openai.test/*' => Http::response([
                'model' => 'gpt-test',
                'choices' => [['message' => [
                    'content' => null,
                    'tool_calls' => [[
                        'id' => 'call_1', 'type' => 'function',
                        'function' => ['name' => 'query_data', 'arguments' => '{"entity":"employees"}'],
                    ]],
                ]]],
                'usage' => ['total_tokens' => 9],
            ], 200),
        ]);

        $result = (new OpenAiCompatProvider())->chat(
            [
                ['role' => 'system', 'content' => 'You are Aeon.'],
                ['role' => 'user', 'content' => 'how many employees?'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [['name' => 'query_data', 'args' => ['entity' => 'users']]]],
                ['role' => 'tool', 'results' => [['name' => 'query_data', 'response' => ['total' => 3]]]],
            ],
            [['name' => 'query_data', 'description' => 'query', 'parameters' => ['entity' => ['type' => 'string']]]],
        );

        $this->assertTrue($result->success);
        $this->assertSame('query_data', $result->toolCalls[0]['name']);
        $this->assertSame('employees', $result->toolCalls[0]['args']['entity']);
        $this->assertSame(9, $result->tokensUsed);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $assistant = collect($body['messages'])->firstWhere('role', 'assistant');
            $tool = collect($body['messages'])->firstWhere('role', 'tool');

            return str_contains($request->url(), '/chat/completions')
                && $body['tools'][0]['function']['name'] === 'query_data'
                && $assistant['tool_calls'][0]['function']['name'] === 'query_data'
                && $tool['tool_call_id'] === $assistant['tool_calls'][0]['id'];
        });
    }

    public function test_chat_returns_failed_result_on_http_error(): void
    {
        Http::fake(['api.openai.test/*' => Http::response(['error' => 'nope'], 500)]);

        $result = (new OpenAiCompatProvider())->chat([['role' => 'user', 'content' => 'x']]);

        $this->assertFalse($result->success);
    }
}
