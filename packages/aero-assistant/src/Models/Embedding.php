<?php

namespace Aero\Assistant\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Embedding extends TenantModel
{
    use HasFactory;

    protected $table = 'assistant_embeddings';

    protected $fillable = [
        'source_type',
        'source_path',
        'module_name',
        'content',
        'content_chunk',
        'metadata',
        'embedding',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
    ];

    /**
     * Scope to filter by source type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeOfModule($query, string $module)
    {
        return $query->where('module_name', $module);
    }

    /**
     * Find similar embeddings using vector similarity search.
     *
     * @param  array  $queryEmbedding  The query embedding vector
     * @param  int  $limit  Number of results to return
     * @param  float  $threshold  Minimum similarity threshold (0-1)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findSimilar(array $queryEmbedding, int $limit = 5, float $threshold = 0.7)
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Use pgvector's cosine similarity
            $embeddingString = '['.implode(',', $queryEmbedding).']';

            return static::select('*')
                ->selectRaw('1 - (embedding <=> ?::vector) as similarity', [$embeddingString])
                ->whereRaw('1 - (embedding <=> ?::vector) > ?', [$embeddingString, $threshold])
                ->orderByRaw('embedding <=> ?::vector', [$embeddingString])
                ->limit($limit)
                ->get();
        } else {
            // Fallback: manual cosine similarity calculation (slower)
            return static::all()
                ->map(function ($item) use ($queryEmbedding) {
                    $item->similarity = static::cosineSimilarity($queryEmbedding, $item->embedding);

                    return $item;
                })
                ->where('similarity', '>', $threshold)
                ->sortByDesc('similarity')
                ->take($limit)
                ->values();
        }
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    protected static function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }

        $magnitude = sqrt($magnitudeA) * sqrt($magnitudeB);

        return $magnitude > 0 ? $dotProduct / $magnitude : 0.0;
    }
}
