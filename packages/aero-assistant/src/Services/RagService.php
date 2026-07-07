<?php

declare(strict_types=1);

namespace Aero\Assistant\Services;

use Aero\Assistant\Models\Embedding;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\Log;

/**
 * Retrieval: embeds the user's query and returns the most similar knowledge
 * chunks (cosine similarity computed in PHP — the KB is small, so no vector DB
 * is needed). Fails soft: any error returns [] and the chat proceeds ungrounded.
 */
class RagService
{
    public function __construct(private AiProvider $provider) {}

    /**
     * @return array<int,array{score:float,title:string,source_ref:string,source_type:string,text:string}>
     */
    public function retrieve(string $query, ?int $k = null, ?float $threshold = null): array
    {
        if (! config('aeon.rag.enabled', true)) {
            return [];
        }

        try {
            $rows = Embedding::query()->get(['source_type', 'source_ref', 'title', 'chunk_text', 'vector']);
            if ($rows->isEmpty()) {
                return []; // nothing indexed yet — skip the query-embedding call entirely
            }

            $q = $this->provider->embed([$query])[0] ?? [];
            if (empty($q)) {
                return [];
            }

            $k = $k ?? (int) config('aeon.rag.top_k', 5);
            $threshold = $threshold ?? (float) config('aeon.rag.threshold', 0.55);

            $scored = [];
            foreach ($rows as $r) {
                $v = $r->vector;
                if (! is_array($v) || empty($v)) {
                    continue;
                }
                $score = $this->cosine($q, $v);
                if ($score >= $threshold) {
                    $scored[] = [
                        'score' => $score,
                        'title' => (string) $r->title,
                        'source_ref' => (string) $r->source_ref,
                        'source_type' => (string) $r->source_type,
                        'text' => (string) $r->chunk_text,
                    ];
                }
            }

            usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);

            return array_slice($scored, 0, $k);
        } catch (\Throwable $e) {
            Log::warning('Aeon RAG retrieve failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<int,float>  $a
     * @param  array<int,float>  $b
     */
    public function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        if ($na <= 0.0 || $nb <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}
