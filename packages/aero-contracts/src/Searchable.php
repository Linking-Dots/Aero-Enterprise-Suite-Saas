<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface Searchable
{
    /** @return array<string> */
    public function getSearchableColumns(): array;
    public function getSearchResultTitle(): string;
    public function getSearchResultUrl(): ?string;
    public function getSearchResultType(): string;
    public function getSearchResultSubtitle(): ?string;
    public function getSearchResultIcon(): ?string;
}
