<?php

declare(strict_types=1);

namespace Aero\Core\Console\Commands;

use Illuminate\Console\Command;

/**
 * Fail-closed gate for the Phase-4 migration-routing tier classification.
 *
 * Every aero package that ships migrations is routed to the right database
 * (central=platform+sharable, tenant=core+sharable+subscribed,
 * standalone=core+sharable+purchased) by its `extra.aero.tier` in composer.json
 * (platform|core|sharable|product). A missing or invalid tier is dangerous: an
 * unclassified package would default-route wrong (e.g. tenant data leaking into the
 * central landlord DB). This command FAILS (exit 1) if any discovered aero package
 * lacks a valid tier, so the installer/CI can refuse to proceed.
 *
 * Stub directories with no composer.json (e.g. config-only placeholders) are skipped.
 */
class VerifyTiersCommand extends Command
{
    protected $signature = 'aero:verify-tiers {--json : Output machine-readable JSON}';

    protected $description = 'Verify every aero package declares a valid extra.aero.tier (platform|core|sharable|product)';

    private const VALID_TIERS = ['platform', 'core', 'sharable', 'product'];

    public function handle(): int
    {
        $byTier = ['platform' => [], 'core' => [], 'sharable' => [], 'product' => []];
        $errors = [];
        $skipped = [];

        foreach ($this->discoverPackageComposers() as $name => $composerPath) {
            $data = json_decode((string) file_get_contents($composerPath), true);
            if (! is_array($data)) {
                $errors[$name] = 'composer.json is not valid JSON';
                continue;
            }

            $tier = $data['extra']['aero']['tier'] ?? null;
            if ($tier === null) {
                $errors[$name] = 'missing extra.aero.tier';
                continue;
            }
            if (! in_array($tier, self::VALID_TIERS, true)) {
                $errors[$name] = "invalid tier '{$tier}' (must be one of: ".implode('|', self::VALID_TIERS).')';
                continue;
            }

            $byTier[$tier][] = $name;
        }

        // Stub dirs (no composer.json) — recorded, not fatal.
        foreach ($this->discoverStubDirs() as $name) {
            $skipped[] = $name;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ok' => $errors === [],
                'by_tier' => array_map('count', $byTier),
                'errors' => $errors,
                'skipped_stubs' => $skipped,
            ], JSON_PRETTY_PRINT));

            return $errors === [] ? self::SUCCESS : self::FAILURE;
        }

        foreach (self::VALID_TIERS as $tier) {
            $this->line(sprintf('  %-9s : %d', $tier, count($byTier[$tier])));
        }
        if ($skipped !== []) {
            $this->warn('Skipped (no composer.json — stub): '.implode(', ', $skipped));
        }

        if ($errors !== []) {
            $this->newLine();
            $this->error('Tier verification FAILED — these packages are unclassified or invalid:');
            foreach ($errors as $name => $why) {
                $this->line("  ✗ {$name}: {$why}");
            }

            return self::FAILURE;
        }

        $this->info('✅ All aero packages declare a valid tier.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> package short-name => composer.json path
     */
    private function discoverPackageComposers(): array
    {
        $found = [];
        foreach ($this->aeroDirs() as $dir) {
            $composer = $dir.DIRECTORY_SEPARATOR.'composer.json';
            if (is_file($composer)) {
                $found[basename($dir)] = $composer;
            }
        }
        ksort($found);

        return $found;
    }

    /**
     * @return array<int, string> stub dir short-names (no composer.json)
     */
    private function discoverStubDirs(): array
    {
        $stubs = [];
        foreach ($this->aeroDirs() as $dir) {
            if (! is_file($dir.DIRECTORY_SEPARATOR.'composer.json')) {
                $stubs[] = basename($dir);
            }
        }
        sort($stubs);

        return $stubs;
    }

    /**
     * @return array<int, string> absolute paths of aero-* package dirs (packages/ + vendor/aero/)
     */
    private function aeroDirs(): array
    {
        // vendor/aero first, then packages/ — so a monorepo packages/ entry (the source of
        // truth) overwrites a vendored copy of the same package on de-dupe by name.
        $dirs = [];
        foreach ([base_path('vendor/aero/*'), base_path('packages/aero-*')] as $glob) {
            foreach ((array) glob($glob, GLOB_ONLYDIR) as $dir) {
                $dirs[basename($dir)] = $dir;
            }
        }

        return array_values($dirs);
    }
}
