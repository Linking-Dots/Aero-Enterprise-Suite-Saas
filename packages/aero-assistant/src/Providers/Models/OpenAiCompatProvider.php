<?php

declare(strict_types=1);

namespace Aero\Assistant\Providers\Models;

use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI-compatible chat/embeddings driver. Points at api.openai.com by default
 * but works against any /chat/completions-compatible server (OpenRouter, Ollama,
 * LM Studio, vLLM) via AEON_OPENAI_BASE_URL — so Aeon can run fully local.
 *
 * Canonical message/tool shapes are defined on AiProvider; this driver adapts
 * them to the OpenAI wire format. tool_call ids are synthesized deterministically
 * (aeon_{turn}_{i}) on both the assistant turn and the tool-result turn, which is
 * safe because Aeon always answers a turn's calls in full and in order.
 */
class OpenAiCompatProvider implements AiProvider
{
    private string $key;
    private string $model;
    private string $base;
    private int $timeout;

    public function __construct()
    {
        $cfg = (array) config('aeon.providers.openai', []);
        // Key / model / base URL from the central control plane when available.
        $central = \Aero\Assistant\Support\AeonConfig::resolve();
        $this->key = (string) ($central['api_key'] ?? $cfg['api_key'] ?? '');
        $this->model = (string) ($central['fast_model'] ?? $cfg['model'] ?? 'gpt-4o-mini');
        $this->base = rtrim((string) ($central['base_url'] ?? $cfg['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $this->timeout = (int) ($cfg['timeout'] ?? 30);
    }

    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        $wire = [];
        $turn = 0;
        $pendingIds = []; // ids assigned to the last assistant turn's calls, in order
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';

            if ($role === 'tool') {
                foreach (array_values((array) ($m['results'] ?? [])) as $i => $r) {
                    $wire[] = [
                        'role' => 'tool',
                        'tool_call_id' => $pendingIds[$i] ?? ('aeon_'.$turn.'_'.$i),
                        'content' => json_encode($r['response'] ?? [], JSON_UNESCAPED_UNICODE),
                    ];
                }
                continue;
            }

            $entry = ['role' => $role, 'content' => (string) ($m['content'] ?? '')];
            if ($role === 'assistant' && ! empty($m['tool_calls'])) {
                $turn++;
                $pendingIds = [];
                $entry['tool_calls'] = [];
                foreach (array_values((array) $m['tool_calls']) as $i => $call) {
                    $id = 'aeon_'.$turn.'_'.$i;
                    $pendingIds[] = $id;
                    $entry['tool_calls'][] = [
                        'id' => $id,
                        'type' => 'function',
                        'function' => [
                            'name' => (string) ($call['name'] ?? ''),
                            'arguments' => json_encode($call['args'] ?? [], JSON_UNESCAPED_UNICODE),
                        ],
                    ];
                }
                if ($entry['content'] === '') {
                    $entry['content'] = null;
                }
            }
            $wire[] = $entry;
        }

        $payload = [
            'model' => $this->model,
            'messages' => $wire,
            'temperature' => (float) ($options['temperature'] ?? config('aeon.providers.openai.temperature', 0.6)),
            'max_tokens' => (int) ($options['max_tokens'] ?? config('aeon.providers.openai.max_tokens', 1200)),
        ];
        if (! empty($tools)) {
            $payload['tools'] = array_map(static fn ($t) => [
                'type' => 'function',
                'function' => [
                    'name' => $t['name'],
                    'description' => $t['description'] ?? '',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) ($t['parameters'] ?? []),
                    ],
                ],
            ], $tools);
        }

        try {
            $res = Http::withToken($this->key)
                ->timeout($this->timeout)
                ->retry(2, 500, throw: false)
                ->post("{$this->base}/chat/completions", $payload);

            if ($res->failed()) {
                return AiChatResult::failed('OpenAI-compatible HTTP '.$res->status(), $this->model);
            }

            $json = $res->json();
            $msg = (array) data_get($json, 'choices.0.message', []);
            $toolCalls = [];
            foreach ((array) ($msg['tool_calls'] ?? []) as $tc) {
                $args = json_decode((string) data_get($tc, 'function.arguments', '{}'), true);
                $toolCalls[] = [
                    'name' => (string) data_get($tc, 'function.name', ''),
                    'args' => is_array($args) ? $args : [],
                ];
            }

            return new AiChatResult(
                content: (string) ($msg['content'] ?? ''),
                toolCalls: $toolCalls,
                tokensUsed: (int) data_get($json, 'usage.total_tokens', 0),
                model: (string) data_get($json, 'model', $this->model),
            );
        } catch (\Throwable $e) {
            Log::error('Aeon OpenAiCompatProvider chat failed', ['error' => $e->getMessage()]);

            return AiChatResult::failed($e->getMessage(), $this->model);
        }
    }

    public function embed(array $texts, array $options = []): array
    {
        $embedModel = (string) config('aeon.providers.openai.embed_model', 'text-embedding-3-small');
        try {
            $res = Http::withToken($this->key)
                ->timeout($this->timeout)
                ->post("{$this->base}/embeddings", ['model' => $embedModel, 'input' => array_values($texts)]);
            if ($res->failed()) {
                return array_fill(0, count($texts), []);
            }
            $rows = (array) data_get($res->json(), 'data', []);
            usort($rows, static fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

            return array_map(static fn ($r) => (array) ($r['embedding'] ?? []), $rows);
        } catch (\Throwable $e) {
            Log::error('Aeon OpenAiCompatProvider embed failed', ['error' => $e->getMessage()]);

            return array_fill(0, count($texts), []);
        }
    }

    public function isAvailable(): bool
    {
        try {
            return Http::withToken($this->key)->timeout(5)->get("{$this->base}/models")->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
