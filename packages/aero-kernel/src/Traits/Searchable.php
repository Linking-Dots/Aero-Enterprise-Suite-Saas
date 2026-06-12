<?php

declare(strict_types=1);

namespace Aero\Kernel\Traits;

/**
 * Searchable Trait
 *
 * Provides default implementations for the Searchable contract.
 * Models should override getSearchResultTitle(), getSearchResultUrl(),
 * and getSearchResultType() for accurate search rendering.
 *
 * Usage:
 *   class User extends Model implements SearchableContract
 *   {
 *       use SearchableTrait;
 *
 *       public function getSearchableColumns(): array
 *       {
 *           return ['name', 'email', 'user_name', 'phone'];
 *       }
 *   }
 */
trait Searchable
{
    /**
     * Default searchable columns (override in model).
     */
    public function getSearchableColumns(): array
    {
        return property_exists($this, 'searchableColumns')
            ? $this->searchableColumns
            : [];
    }

    /**
     * Default title uses the model's primary display column or ID.
     */
    public function getSearchResultTitle(): string
    {
        return $this->name ?? $this->title ?? $this->email ?? ('#'.$this->getKey());
    }

    /**
     * Default URL is null (no direct route).
     */
    public function getSearchResultUrl(): ?string
    {
        return null;
    }

    /**
     * Default type uses the short class name.
     */
    public function getSearchResultType(): string
    {
        return class_basename(static::class);
    }

    /**
     * Default subtitle is null.
     */
    public function getSearchResultSubtitle(): ?string
    {
        return null;
    }

    /**
     * Default icon is null.
     */
    public function getSearchResultIcon(): ?string
    {
        return null;
    }
}
