<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Embedding;
use Aero\Assistant\Services\RagService;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;

class RagServiceTest extends PackageTestCase
{
    public function test_cosine_similarity(): void
    {
        $rag = $this->app->make(RagService::class);
        $this->assertEqualsWithDelta(1.0, $rag->cosine([1, 0], [1, 0]), 1e-9);
        $this->assertEqualsWithDelta(0.0, $rag->cosine([1, 0], [0, 1]), 1e-9);
    }

    public function test_retrieve_ranks_by_similarity_and_applies_threshold(): void
    {
        // Query embeds to a vector close to [1,0].
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function chat(array $m, array $t = [], array $o = []): AiChatResult
            {
                return new AiChatResult('x');
            }

            public function embed(array $texts, array $o = []): array
            {
                return [[0.9, 0.1]];
            }

            public function isAvailable(): bool
            {
                return true;
            }
        });

        Embedding::create(['source_type' => 'module', 'source_ref' => 'a', 'title' => 'Near', 'chunk_text' => 'near', 'vector' => [1, 0], 'dims' => 2, 'checksum' => 'a']);
        Embedding::create(['source_type' => 'module', 'source_ref' => 'b', 'title' => 'Far', 'chunk_text' => 'far', 'vector' => [0, 1], 'dims' => 2, 'checksum' => 'b']);

        $hits = $this->app->make(RagService::class)->retrieve('anything');

        $this->assertCount(1, $hits);            // 'Far' is below the 0.55 threshold
        $this->assertSame('Near', $hits[0]['title']);
    }

    public function test_retrieve_returns_empty_when_index_empty(): void
    {
        $this->assertSame([], $this->app->make(RagService::class)->retrieve('anything'));
    }
}
