<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface TranslationDriverInterface
{
    public function translate(string $key, array $replace = [], ?string $locale = null): string;

    public function has(string $key, ?string $locale = null): bool;

    public function getLocale(): string;

    /**
     * Return shared i18n props for the Inertia global page object.
     *
     * @return array{locale: string, translations: array<string, string>}
     */
    public function getSharedProps(): array;
}
