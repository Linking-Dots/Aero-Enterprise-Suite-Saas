<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

/**
 * Searchable Contract
 *
 * Defines how a model exposes itself to the global search service.
 * Implementing models declare which columns are searchable and how
 * search results should be rendered.
 */
interface Searchable
{
    /**
     * Columns that should be searched for this model.
     *
     * @return array<string>
     */
    public function getSearchableColumns(): array;

    /**
     * Title to display for this search result.
     *
     * @return string
     */
    public function getSearchResultTitle(): string;

    /**
     * URL to navigate to when this result is selected.
     *
     * @return string|null
     */
    public function getSearchResultUrl(): ?string;

    /**
     * Type label shown in the search UI (e.g. "User", "Role").
     *
     * @return string
     */
    public function getSearchResultType(): string;

    /**
     * Optional subtitle / description for the search result.
     *
     * @return string|null
     */
    public function getSearchResultSubtitle(): ?string;

    /**
     * Icon name for this result type (matches @aero/ui Icon component names).
     *
     * @return string|null
     */
    public function getSearchResultIcon(): ?string;
}
