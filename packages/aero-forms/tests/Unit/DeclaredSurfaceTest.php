<?php

declare(strict_types=1);

namespace Aero\Forms\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Plan 11 (aero-forms) — declared-surface / implementation alignment pin.
 *
 * Phase 1 audit declared 3 components (forms, submissions, templates)
 * + claimed PDF generation in the description. Only forms + submissions
 * actually ship. Trimmed to match reality; deferred items in 'roadmap'.
 */
class DeclaredSurfaceTest extends TestCase
{
    private function config(): array
    {
        return require dirname(__DIR__, 2).'/config/module.php';
    }

    public function test_declared_components_match_shipped_controllers(): void
    {
        $config = $this->config();
        $codes = array_column($config['submodules'][0]['components'], 'code');

        $this->assertSame(['forms', 'submissions'], $codes,
            "Declared components must match shipped controllers: forms (FormController) ".
            "and submissions (FormSubmissionController). 'templates' was declared but ".
            "never implemented — moved to roadmap (Plan 11).");
    }

    public function test_roadmap_documents_deferred_items(): void
    {
        $config = $this->config();

        $this->assertArrayHasKey('roadmap', $config);
        foreach (['templates', 'pdf_generation', 'conditional_logic'] as $deferred) {
            $this->assertArrayHasKey($deferred, $config['roadmap'],
                "Roadmap must explain why '{$deferred}' was deferred — prevents a future ".
                "maintainer assuming it was an oversight.");
        }
    }

    public function test_form_controller_file_exists(): void
    {
        $this->assertFileExists(dirname(__DIR__, 2).'/src/Http/Controllers/FormController.php');
    }

    public function test_form_submission_controller_file_exists(): void
    {
        $this->assertFileExists(dirname(__DIR__, 2).'/src/Http/Controllers/FormSubmissionController.php');
    }
}
