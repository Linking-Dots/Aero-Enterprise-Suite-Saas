<?php

namespace Aero\Installation;

/**
 * ModeDetector
 *
 * Detects installation mode using the canonical aeos.mode file.
 * Falls back to class_exists check only during initial installation
 * before the mode file has been written.
 *
 * The file is written by FinalizeStep and is thereafter immutable.
 * Do NOT change mode detection logic in the running application —
 * use the helpers.php functions (aero_mode(), is_saas_mode(), is_standalone_mode()).
 */
class ModeDetector
{
    private string $modeFilePath;

    public function __construct(?string $modeFilePath = null)
    {
        $this->modeFilePath = $modeFilePath ?? storage_path('app/aeos.mode');
    }

    public function detect(): string
    {
        // 1. Canonical source: the mode file (set during installation)
        if (file_exists($this->modeFilePath)) {
            $mode = trim(file_get_contents($this->modeFilePath));
            if (in_array($mode, ['saas', 'standalone'], true)) {
                return $mode;
            }
        }

        // 2. Pre-install fallback: infer from package presence
        // Only reaches here during the very first run of the installer
        if (class_exists('Aero\\Platform\\AeroPlatformServiceProvider')) {
            return 'saas';
        }

        return 'standalone';
    }

    public function isSaaS(): bool
    {
        return $this->detect() === 'saas';
    }

    public function isStandalone(): bool
    {
        return $this->detect() === 'standalone';
    }
}
