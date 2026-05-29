<?php

declare(strict_types=1);

namespace Aero\I18n\Tests\Unit;

use Aero\I18n\TranslationEngine;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 07 (aero-i18n) Tasks 1 + 2 — i18n alignment + fallback regression.
 *
 * Phase 1 audit raised concern about:
 *   T1: declared tenant_tables didn't match the shipped migration shape
 *       (was: tenant_translations, tenant_locales, locale_preferences;
 *        actually: languages, translations). Fixed in this commit.
 *
 *   T2: no translation driver fallback. Verification of the source
 *       shows the engine ALREADY has translateWithFallback() and
 *       batchTranslateWithFallback() methods that iterate registered
 *       drivers in priority order — Phase 1 was inaccurate. This test
 *       pins the fallback so a refactor can't accidentally remove it.
 */
class TranslationEngineFallbackTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(TranslationEngine::class))->getFileName());
    }

    public function test_translate_with_fallback_method_exists(): void
    {
        $r = new ReflectionClass(TranslationEngine::class);

        $this->assertTrue($r->hasMethod('translateWithFallback'),
            'TranslationEngine::translateWithFallback() must exist — required for '.
            'graceful degradation when the primary driver fails (network, rate '.
            'limit, API outage).');
    }

    public function test_batch_translate_with_fallback_method_exists(): void
    {
        $r = new ReflectionClass(TranslationEngine::class);

        $this->assertTrue($r->hasMethod('batchTranslateWithFallback'),
            'TranslationEngine::batchTranslateWithFallback() must exist.');
    }

    public function test_fallback_iterates_drivers_in_priority_order(): void
    {
        $source = $this->source();

        // Pin the foreach-over-drivers pattern in translateWithFallback
        $this->assertMatchesRegularExpression(
            '/translateWithFallback[\s\S]{0,300}foreach\s*\(\s*\$this->drivers/',
            $source,
            'translateWithFallback() must iterate $this->drivers in order so the '.
            'primary driver gets first crack at each translation.'
        );
    }

    public function test_batch_fallback_only_retries_remaining_strings(): void
    {
        $source = $this->source();

        // Optimization: when batchTranslateWithFallback gets PARTIAL results
        // from driver A, only the UNTRANSLATED strings should be retried on
        // driver B — never re-translating already-resolved keys
        $this->assertMatchesRegularExpression(
            '/batchTranslateWithFallback[\s\S]{0,1200}\$remaining\s*=\s*\$stillMissing/',
            $source,
            'batchTranslateWithFallback() must track remaining-untranslated and '.
            'only retry those on the next driver — otherwise driver B re-translates '.
            'strings A already handled, doubling external API cost.'
        );
    }

    public function test_supports_locale_check_skips_unsupported_drivers(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/->\s*supportsLocale\(\$to\)/',
            $source,
            'Drivers that explicitly do not support the target locale must be '.
            "skipped — there's no point asking a Chinese-only driver to translate to Hindi."
        );
    }

    public function test_init_drivers_respects_primary_config(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            "/config\(\s*['\"]locale\.translation_api\.primary['\"]/",
            $source,
            'initDrivers() must read locale.translation_api.primary so operators '.
            'can choose LibreTranslate or MyMemory as the primary at deploy time.'
        );
    }

    public function test_tenant_tables_match_shipped_migrations(): void
    {
        $config = require dirname(__DIR__, 2).'/config/module.php';

        $this->assertSame(
            ['languages', 'translations'],
            $config['tenancy']['tenant_tables'],
            "Plan 07 T1 — declared tenant_tables must match the shipped migrations ".
            "(2026_05_05_000000_create_languages_table + 2026_05_05_000001_create_translations_table). ".
            "Previously declared tenant_translations/tenant_locales/locale_preferences — none existed."
        );

        $this->assertSame([], $config['tenancy']['central_tables'],
            'aero-i18n has no central tables — translations live in tenant DB only.');
    }
}
