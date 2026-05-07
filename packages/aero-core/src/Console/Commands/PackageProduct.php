<?php

namespace Aero\Core\Console\Commands;

use Aero\Core\Services\ProductManifestLoader;
use Illuminate\Console\Command;

class PackageProduct extends Command
{
    protected $signature = 'aero:package-product
                              {--output= : Output directory for the archive (default: ./dist)}
                              {--no-verify : Skip manifest validation before packaging}';

    protected $description = 'Generate a distributable product archive for marketplace distribution';

    public function handle(ProductManifestLoader $loader): int
    {
        $manifest = $loader->load();

        if (! $manifest->supportsStandalone()) {
            $this->error("Product [{$manifest->id}] is SaaS-only and cannot be packaged for standalone distribution.");

            return self::FAILURE;
        }

        if (! $this->option('no-verify')) {
            $this->info('Validating module manifests...');
            $result = $this->callSilent('aero:validate-manifests', ['--strict' => true]);

            if ($result !== self::SUCCESS) {
                $this->error('Manifest validation failed. Fix errors before packaging. Run: php artisan aero:validate-manifests --strict');

                return self::FAILURE;
            }
        }

        $outputDir = $this->option('output') ?? base_path('dist');
        $archiveName = "{$manifest->id}-v{$manifest->version}-standalone.zip";
        $archivePath = "{$outputDir}/{$archiveName}";

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->info("Packaging [{$manifest->name}] v{$manifest->version}...");

        $excludeArgs = $this->buildExcludes();
        $basePath = base_path();

        $zipCommand = "zip -r \"{$archivePath}\" . ".implode(' ', $excludeArgs);
        $this->info("Running: cd {$basePath} && {$zipCommand}");

        $output = [];
        $code = 0;
        exec("cd \"{$basePath}\" && {$zipCommand} 2>&1", $output, $code);

        if ($code !== 0) {
            $this->error('Archive creation failed.');
            $this->line(implode("\n", $output));

            return self::FAILURE;
        }

        if (! file_exists($archivePath)) {
            $this->error("Archive was not created at: {$archivePath}");

            return self::FAILURE;
        }

        $sizeMb = round(filesize($archivePath) / 1024 / 1024, 2);
        $this->info("Archive created: {$archivePath} ({$sizeMb} MB)");

        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Product', $manifest->name],
                ['Version', $manifest->version],
                ['Product ID', $manifest->id],
                ['Archive', $archivePath],
                ['Size', "{$sizeMb} MB"],
            ]
        );

        return self::SUCCESS;
    }

    private function buildExcludes(): array
    {
        $excludePatterns = [
            '.git/*',
            '.github/*',
            'node_modules/*',
            'dist/*',
            'packages/*/tests/*',
            '.claude/*',
            'docs/superpowers/*',
            'storage/logs/*',
            'storage/framework/cache/*',
            'storage/framework/sessions/*',
            'storage/app/aeos.*',
            '.env',
            '*.test',
            'phpunit.xml',
            'phpunit.xml.dist',
            'deptrac.yaml',
            '.php-cs-fixer.php',
        ];

        return array_map(fn ($p) => "--exclude=\"{$p}\"", $excludePatterns);
    }
}
