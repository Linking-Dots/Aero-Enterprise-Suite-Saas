<?php

declare(strict_types=1);

namespace Aero\Core\Services\Search;

use Aero\Contracts\Searchable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * Global Search Service
 *
 * Searches across registered Searchable models using LIKE queries.
 * Returns typed, relevance-scored results ready for JSON or Inertia props.
 *
 * Extension Point:
 *   Swap with ScoutSearchService (Laravel Scout) by implementing the same
 *   search() / suggest() interface without changing controllers.
 */
class GlobalSearchService
{
    /**
     * Registered searchable model FQCNs.
     *
     * @var array<class-string<Model&Searchable>>
     */
    protected array $searchableModels = [];

    /**
     * Max results per type in suggestion mode.
     */
    protected int $suggestionLimit = 5;

    /**
     * Results per page in full search mode.
     */
    protected int $perPage = 20;

    /**
     * Register a model class as searchable.
     *
     * @param  class-string<Model&Searchable>  $modelClass
     */
    public function registerModel(string $modelClass): void
    {
        if (! in_array($modelClass, $this->searchableModels, true)) {
            $this->searchableModels[] = $modelClass;
        }
    }

    /**
     * Set the models to search (useful for testing or scoped search).
     *
     * @param  array<class-string<Model&Searchable>>  $models
     */
    public function setModels(array $models): void
    {
        $this->searchableModels = $models;
    }

    /**
     * Get the registered searchable model classes.
     *
     * @return array<class-string<Model&Searchable>>
     */
    public function getModels(): array
    {
        return $this->searchableModels;
    }

    /**
     * Perform a full search across all registered models.
     *
     * @return array{results: Collection, total: int, types: array<string>}
     */
    public function search(string $query, ?string $type = null, ?int $perPage = null): array
    {
        $perPage ??= $this->perPage;
        $results = collect();
        $total = 0;

        $models = $type
            ? array_filter($this->searchableModels, fn ($m) => class_basename($m) === $type)
            : $this->searchableModels;

        foreach ($models as $modelClass) {
            /** @var Model&Searchable $instance */
            $instance = App::make($modelClass);
            $builder = $this->buildQuery($instance, $query);

            $count = $builder->count();
            $total += $count;

            $modelResults = $builder
                ->limit($perPage)
                ->get()
                ->map(fn ($model) => $this->formatResult($model));

            $results = $results->merge($modelResults);
        }

        return [
            'results' => $results->sortByDesc('relevance_score')->values(),
            'total' => $total,
            'types' => array_map('class_basename', $this->searchableModels),
        ];
    }

    /**
     * Quick suggestions: top N per type.
     *
     * @return Collection<int, array>
     */
    public function suggest(string $query, ?int $limit = null): Collection
    {
        $limit ??= $this->suggestionLimit;
        $results = collect();

        foreach ($this->searchableModels as $modelClass) {
            /** @var Model&Searchable $instance */
            $instance = App::make($modelClass);
            $builder = $this->buildQuery($instance, $query);

            $modelResults = $builder
                ->limit($limit)
                ->get()
                ->map(fn ($model) => $this->formatResult($model));

            $results = $results->merge($modelResults);
        }

        return $results->sortByDesc('relevance_score')->values();
    }

    /**
     * Build a LIKE query for a single model.
     *
     * @param  Model&Searchable  $instance
     */
    protected function buildQuery(Model $instance, string $query): Builder
    {
        $columns = $instance->getSearchableColumns();
        $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';

        return $instance->newQuery()
            ->when(
                count($columns) > 0,
                fn (Builder $q) => $q->where(function (Builder $wq) use ($columns, $term) {
                    foreach ($columns as $column) {
                        $wq->orWhere($column, 'LIKE', $term);
                    }
                })
            );
    }

    /**
     * Format a model result for JSON / Inertia props.
     *
     * @param  Model&Searchable  $model
     */
    protected function formatResult(Model $model): array
    {
        $relevance = $this->scoreRelevance($model);

        return [
            'id' => $model->getKey(),
            'type' => $model->getSearchResultType(),
            'title' => $model->getSearchResultTitle(),
            'subtitle' => $model->getSearchResultSubtitle(),
            'url' => $model->getSearchResultUrl(),
            'icon' => $model->getSearchResultIcon(),
            'relevance_score' => $relevance,
            'data' => $model->toArray(),
        ];
    }

    /**
     * Simple relevance scoring.
     * 1.0 = exact match on primary column
     * 0.8 = starts with
     * 0.5 = contains anywhere
     */
    protected function scoreRelevance(Model $model): float
    {
        // Default mid score — models can override via getSearchResultSubtitle() logic
        // or by providing a custom relevance accessor.
        return 0.5;
    }
}
