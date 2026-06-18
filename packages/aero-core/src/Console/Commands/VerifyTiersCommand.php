<?php

declare(strict_types=1);

namespace Aero\Core\Console\Commands;

use Aero\Kernel\Migration\PackageTier;
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
        // Pure scan lives in the kernel so the install-time MigrationStep (a web
        // request, where console commands aren't registered) can gate on the same logic.
        $result = PackageTier::verifyAll();
        $byTier = $result['by_tier'];
        $errors = $result['errors'];
        $skipped = $result['skipped'];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ok' => $result['ok'],
                'by_tier' => $byTier,
                'errors' => $errors,
                'skipped_stubs' => $skipped,
            ], JSON_PRETTY_PRINT));

            return $result['ok'] ? self::SUCCESS : self::FAILURE;
        }

        foreach (self::VALID_TIERS as $tier) {
            $this->line(sprintf('  %-9s : %d', $tier, $byTier[$tier] ?? 0));
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
}
