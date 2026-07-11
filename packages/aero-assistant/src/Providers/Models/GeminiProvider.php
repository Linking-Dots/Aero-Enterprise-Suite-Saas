<?php

declare(strict_types=1);

namespace Aero\Assistant\Providers\Models;

use Aero\Assistant\Support\AeonConfig;
use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AiProvider
{
    private string $key;
    private string $model;
    private string $endpoint;
    private int $timeout;

    public function __construct()
    {
        $cfg = config('aeon.providers.gemini');
        // Provider + key + default model come from the central control plane
        // (platform_settings.ai_settings) when available, else this package's
        // config/.env. Endpoint/timeout stay local (rarely operator-tuned).
        $central = AeonConfig::resolve();
        $this->key = (string) ($central['api_key'] ?? $cfg['api_key'] ?? '');
        $this->model = (string) ($central['fast_model'] ?? $cfg['model'] ?? 'gemini-flash-latest');
        $this->endpoint = rtrim((string) ($cfg['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->timeout = (int) ($cfg['timeout'] ?? 30);
    }

    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        $system = null;
        $contents = [];
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            if ($role === 'system') {
                $system = $m['content'] ?? '';
                continue;
            }

            // Tool results answer the preceding model turn's functionCalls.
            // Gemini only knows roles user|model: functionResponse parts ride
            // in a 'user' content, one part per call, in order.
            if ($role === 'tool') {
                $parts = [];
                foreach ((array) ($m['results'] ?? []) as $r) {
                    $parts[] = ['functionResponse' => [
                        'name' => (string) ($r['name'] ?? ''),
                        'response' => (object) ($r['response'] ?? []),
                    ]];
                }
                if (! empty($parts)) {
                    $contents[] = ['role' => 'user', 'parts' => $parts];
                }
                continue;
            }

            $parts = [];
            $text = (string) ($m['content'] ?? '');
            if ($text !== '') {
                $parts[] = ['text' => $text];
            }
            if ($role === 'assistant') {
                foreach ((array) ($m['tool_calls'] ?? []) as $call) {
                    $part = ['functionCall' => [
                        'name' => (string) ($call['name'] ?? ''),
                        'args' => (object) ($call['args'] ?? []),
                    ]];
                    // Gemini thinking models REQUIRE the thought signature captured
                    // with the functionCall to be echoed back verbatim — omitting it
                    // is a 400 (INVALID_ARGUMENT: missing thought_signature).
                    if (! empty($call['sig'])) {
                        $part['thoughtSignature'] = (string) $call['sig'];
                    }
                    $parts[] = $part;
                }
            }
            if (empty($parts)) {
                $parts[] = ['text' => ''];
            }
            $contents[] = [
                'role'  => $role === 'assistant' ? 'model' : 'user',
                'parts' => $parts,
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => (float) ($options['temperature'] ?? config('aeon.providers.gemini.temperature', 0.6)),
                'maxOutputTokens' => (int) ($options['max_tokens'] ?? config('aeon.providers.gemini.max_tokens', 1200)),
            ],
        ];
        if ($system) {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }
        if (! empty($tools)) {
            // Neutral declarations → Gemini functionDeclarations.
            $payload['tools'] = [['functionDeclarations' => array_map(static fn ($t) => [
                'name' => $t['name'],
                'description' => $t['description'] ?? '',
                'parameters' => [
                    'type' => 'object',
                    'properties' => (object) ($t['parameters'] ?? []),
                    'required' => [],
                ],
            ], $tools)]];
        }

        // Try the primary model, then fall back to alternates when a model is
        // rate-limited (429) or overloaded (503) — Google throttles/overloads
        // individual models transiently, so an alternate usually answers.
        $attempts = (int) config('aeon.providers.gemini.retries', 2);
        $baseMs = (int) config('aeon.providers.gemini.retry_base_ms', 500);
        $lastStatus = null;

        foreach ($this->modelChain() as $model) {
            try {
                $res = null;
                for ($i = 0; $i <= $attempts; $i++) {
                    $res = Http::withHeaders(['x-goog-api-key' => $this->key])
                        ->timeout($this->timeout)
                        ->post("{$this->endpoint}/models/{$model}:generateContent", $payload);

                    if (! in_array($res->status(), [429, 503], true)) {
                        break;
                    }
                    if ($i < $attempts) {
                        usleep($baseMs * 1000 * ($i + 1)); // 500ms, 1000ms, …
                    }
                }

                $lastStatus = $res->status();

                if (in_array($lastStatus, [429, 503], true)) {
                    continue; // this model is busy — try the next in the chain
                }

                if ($res->failed()) {
                    return AiChatResult::failed('Gemini HTTP '.$lastStatus, $model);
                }

                $json = $res->json();
                $parts = (array) data_get($json, 'candidates.0.content.parts', []);
                $text = '';
                $toolCalls = [];
                foreach ($parts as $p) {
                    if (isset($p['text'])) {
                        $text .= $p['text'];
                    }
                    if (isset($p['functionCall'])) {
                        $call = [
                            'name' => (string) ($p['functionCall']['name'] ?? ''),
                            'args' => (array) ($p['functionCall']['args'] ?? []),
                        ];
                        // Keep the thought signature so the next turn can echo it.
                        if (! empty($p['thoughtSignature'])) {
                            $call['sig'] = (string) $p['thoughtSignature'];
                        }
                        $toolCalls[] = $call;
                    }
                }
                $tokens = (int) data_get($json, 'usageMetadata.totalTokenCount', 0);

                return new AiChatResult(content: $text, toolCalls: $toolCalls, tokensUsed: $tokens, model: $model);
            } catch (\Throwable $e) {
                Log::error('Aeon GeminiProvider chat failed', ['model' => $model, 'error' => $e->getMessage()]);
                $lastStatus = $lastStatus ?? 0;
            }
        }

        return AiChatResult::failed('Gemini unavailable (HTTP '.$lastStatus.')', $this->model);
    }

    /**
     * Primary model followed by de-duplicated fallbacks.
     *
     * @return array<int,string>
     */
    private function modelChain(): array
    {
        $fallbacks = config('aeon.providers.gemini.fallback_models', []);
        if (is_string($fallbacks)) {
            $fallbacks = array_filter(array_map('trim', explode(',', $fallbacks)));
        }

        return array_values(array_unique(array_merge([$this->model], (array) $fallbacks)));
    }

    public function embed(array $texts, array $options = []): array
    {
        $cfg = config('aeon.providers.gemini');
        $embedModel = (string) ($cfg['embed_model'] ?? 'gemini-embedding-001');
        $dims = (int) ($cfg['embed_dims'] ?? 768);
        $out = [];
        foreach ($texts as $text) {
            $res = Http::withHeaders(['x-goog-api-key' => $this->key])
                ->timeout($this->timeout)
                ->post("{$this->endpoint}/models/{$embedModel}:embedContent", [
                    'model' => "models/{$embedModel}",
                    'content' => ['parts' => [['text' => $text]]],
                    'outputDimensionality' => $dims,
                ]);
            $out[] = (array) data_get($res->json(), 'embedding.values', []);
        }

        return $out;
    }

    public function isAvailable(): bool
    {
        try {
            return Http::withHeaders(['x-goog-api-key' => $this->key])
                ->timeout(5)->get("{$this->endpoint}/models")->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
