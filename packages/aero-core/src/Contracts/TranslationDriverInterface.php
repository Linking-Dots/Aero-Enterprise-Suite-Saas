<?php

namespace Aero\Core\Contracts;

interface TranslationDriverInterface
{
    public function translate(string $key, array $replace = [], ?string $locale = null): string;
    public function has(string $key, ?string $locale = null): bool;
    public function getLocale(): string;
}
