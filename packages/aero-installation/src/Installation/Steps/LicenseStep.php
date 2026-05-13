<?php

namespace Aero\Installation\Installation\Steps;

use Aero\Contracts\LicenseServiceInterface;
use Aero\Core\Exceptions\LicenseException;

/**
 * LicenseStep
 *
 * Activates the product license during installation.
 * - In SaaS mode: skipped unconditionally.
 * - In Standalone mode: validates and activates the license key if provided.
 *   If the license server is unreachable, installation continues in grace mode.
 *   If no license key is provided, installation continues in not_activated state.
 */
class LicenseStep extends BaseInstallationStep
{
    public function name(): string
    {
        return 'license';
    }

    public function description(): string
    {
        return 'Activate product license';
    }

    public function order(): int
    {
        return 3;
    }

    public function dependencies(): array
    {
        return ['config', 'database'];
    }

    public function canSkip(): bool
    {
        return false;
    }

    public function isRetriable(): bool
    {
        return true;
    }

    public function execute(): array
    {
        if (is_saas_mode()) {
            $this->log('License step skipped — SaaS mode');

            return ['license_status' => 'saas', 'reason' => 'SaaS mode'];
        }

        $licenseKey = $this->getLicenseKey();
        $productId = config('product.id', 'aero-suite');

        if (empty($licenseKey)) {
            $this->log('No license key provided — installation will continue in trial/grace mode');

            return [
                'license_status' => 'not_activated',
                'message' => 'Activate your license from Settings > License Management after installation.',
            ];
        }

        try {
            /** @var LicenseServiceInterface $licenseService */
            $licenseService = app(LicenseServiceInterface::class);
            $licenseService->activate($licenseKey, $productId);

            $this->log('License activated successfully', ['product_id' => $productId]);

            return [
                'license_status' => 'activated',
                'product_id' => $productId,
            ];

        } catch (LicenseException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Throwable $e) {
            $this->log('License activation encountered an unexpected error: '.$e->getMessage());

            return [
                'license_status' => 'activation_error',
                'message' => 'License will be in grace mode. Check Settings > License after installation.',
            ];
        }
    }

    public function validate(): bool
    {
        if (is_saas_mode()) {
            return true;
        }

        $licenseFile = storage_path('app/aeos.license');

        return file_exists($licenseFile) || empty($this->getLicenseKey());
    }

    private function getLicenseKey(): string
    {
        return request()->input('license_key') ?? env('LICENSE_KEY', '');
    }
}
