<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface TranslationDriverInterface
{
    public function translate(string $key, array $replace = [], ?string $locale = null): string;
    public function has(string $key, ?string $locale = null): bool;
    public function getLocale(): string;

    /**
     * @return array{locale: string, translations: array<string, string>}
     */
    public function getSharedProps(): array;
}
