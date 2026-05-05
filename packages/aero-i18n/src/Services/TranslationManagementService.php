<?php

namespace Aero\I18n\Services;

use Aero\I18n\Models\Language;
use Aero\I18n\Models\Translation;
use Aero\I18n\TranslationEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TranslationManagementService
{
    public function __construct(
        protected TranslationEngine $engine,
    ) {}

    public function getLanguages(): Collection
    {
        return Language::ordered()->get();
    }

    public function getEnabledLanguages(): Collection
    {
        return Language::enabled()->ordered()->get();
    }

    public function enableLanguage(string $code): Language
    {
        $language = Language::where('code', $code)->firstOrFail();
        $language->update(['is_enabled' => true]);
        return $language;
    }

    public function disableLanguage(string $code): Language
    {
        $language = Language::where('code', $code)->firstOrFail();
        $language->update(['is_enabled' => false]);
        return $language;
    }

    public function getTranslations(string $languageCode, ?string $namespace = null, ?string $group = null): Collection
    {
        return Translation::forLanguage($languageCode)
            ->forNamespace($namespace)
            ->forGroup($group)
            ->get();
    }

    public function updateTranslation(int $id, string $value): Translation
    {
        $translation = Translation::findOrFail($id);
        $translation->update([
            'value' => $value,
            'is_custom' => true,
        ]);
        return $translation;
    }

    public function autoTranslate(string $languageCode, array $keys): array
    {
        $results = [];

        foreach ($keys as $key) {
            $sourceValue = $this->getEnglishTranslation($key);
            if ($sourceValue) {
                $translatedValue = $this->engine->translate($sourceValue, 'en', $languageCode);
                $results[$key] = $translatedValue;
            }
        }

        return $results;
    }

    public function importTranslations(string $languageCode, array $data, string $format = 'json'): int
    {
        $count = 0;

        foreach ($data as $key => $value) {
            Translation::updateOrCreate(
                [
                    'language_code' => $languageCode,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'is_custom' => true,
                ]
            );
            $count++;
        }

        return $count;
    }

    public function exportTranslations(string $languageCode, string $format = 'json'): array
    {
        $translations = Translation::forLanguage($languageCode)
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        return $translations;
    }

    public function detectMissingTranslations(string $languageCode): array
    {
        $englishKeys = Translation::forLanguage('en')
            ->pluck('key')
            ->toArray();

        $targetKeys = Translation::forLanguage($languageCode)
            ->pluck('key')
            ->toArray();

        $missing = array_diff($englishKeys, $targetKeys);

        return array_values($missing);
    }

    public function seedLanguages(): void
    {
        $defaultLanguages = [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'is_enabled' => true,
                'is_rtl' => false,
                'direction' => 'ltr',
                'sort_order' => 0,
            ],
            [
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag' => '🇪🇸',
                'is_enabled' => false,
                'is_rtl' => false,
                'direction' => 'ltr',
                'sort_order' => 1,
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'flag' => '🇫🇷',
                'is_enabled' => false,
                'is_rtl' => false,
                'direction' => 'ltr',
                'sort_order' => 2,
            ],
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'flag' => '🇸🇦',
                'is_enabled' => false,
                'is_rtl' => true,
                'direction' => 'rtl',
                'sort_order' => 3,
            ],
            [
                'code' => 'de',
                'name' => 'German',
                'native_name' => 'Deutsch',
                'flag' => '🇩🇪',
                'is_enabled' => false,
                'is_rtl' => false,
                'direction' => 'ltr',
                'sort_order' => 4,
            ],
        ];

        foreach ($defaultLanguages as $language) {
            Language::firstOrCreate(['code' => $language['code']], $language);
        }
    }

    protected function getEnglishTranslation(string $key): ?string
    {
        return Translation::forLanguage('en')
            ->where('key', $key)
            ->value('value');
    }
}
