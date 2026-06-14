<?php

// packages/aero-core/src/Services/License/LicenseService.php

namespace Aero\Core\Services\License;

use Aero\Contracts\LicenseServiceInterface;
use Aero\Core\Exceptions\LicenseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService implements LicenseServiceInterface
{
    public function __construct(
        private readonly LicenseValidator $validator,
        private readonly DomainBinding $domainBinding,
        private readonly LicenseCache $cache,
    ) {}

    public function validateFormat(string $licenseKey): void
    {
        $this->validator->validateFormat($licenseKey);
    }

    public function activate(string $licenseKey, string $productId): void
    {
        $this->validator->validateFormat($licenseKey);

        if (! $this->validator->verifyChecksum($licenseKey)) {
            throw LicenseException::invalidFormat();
        }

        $serverUrl = config('license.server_url');
        try {
            $response = Http::timeout(15)->post("{$serverUrl}/api/license/activate", [
                'license_key' => $licenseKey,
                'product_id' => $productId,
                'domain' => request()->getHost(),
                'php_version' => PHP_VERSION,
                'app_version' => config('app.version', '1.0.0'),
            ]);

            if (! $response->successful()) {
                $message = $response->json('message', 'Unknown error from license server');
                throw LicenseException::activationFailed($message);
            }

            $data = $response->json();

        } catch (LicenseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('License activation network failure', ['error' => $e->getMessage()]);
            $data = ['status' => 'grace', 'message' => 'Activated offline — please verify connectivity'];
        }

        $this->storeActivation($licenseKey, $productId);
        $this->domainBinding->bind();
        $this->cache->store(['status' => $data['status'] ?? 'valid', 'product_id' => $productId]);
    }

    public function isValid(): bool
    {
        return in_array($this->status(), ['valid', 'grace', 'saas'], true);
    }

    public function status(): string
    {
        if (is_saas_mode()) {
            return 'saas';
        }

        if (config('license.bypass', false)) {
            return 'valid';
        }

        if (! $this->domainBinding->matches()) {
            return 'invalid';
        }

        $cached = $this->cache->get(config('license.check_ttl_seconds', 86400));
        if ($cached !== null) {
            return $cached['status'];
        }

        return $this->performOnlineCheck();
    }

    public function graceSecondsRemaining(): int
    {
        $lastSuccess = $this->cache->lastSuccessAt();
        if ($lastSuccess === null) {
            return 0;
        }

        $gracePeriod = config('license.grace_period_seconds', 72 * 3600);
        $elapsed = time() - $lastSuccess;

        return max(0, $gracePeriod - $elapsed);
    }

    /**
     * Current license summary for the License management UI.
     * Shape matches Core/License/Index (key_preview, edition, status, expires_at,
     * domain, activated_at). In SaaS the platform owns licensing, so this reports
     * the managed 'saas' status with no local key.
     */
    public function getCurrent(): array
    {
        $status = $this->status();
        $activation = $this->loadActivation();
        $key = $activation['license_key'] ?? null;

        return [
            'status' => $status,
            'is_active' => $this->isValid(),
            'edition' => is_saas_mode() ? 'Cloud' : ($activation['product_id'] ?? null),
            'key_preview' => $key ? substr($key, 0, 4).'••••••'.substr($key, -4) : null,
            'expires_at' => null,
            'domain' => request()?->getHost(),
            'activated_at' => $activation['activated_at'] ?? null,
            'grace_seconds_remaining' => $this->graceSecondsRemaining(),
        ];
    }

    private function performOnlineCheck(): string
    {
        $activation = $this->loadActivation();
        if (! $activation) {
            return 'not_activated';
        }

        $serverUrl = config('license.server_url');
        try {
            $response = Http::timeout(10)->post("{$serverUrl}/api/license/validate", [
                'license_key' => $activation['license_key'],
                'product_id' => $activation['product_id'],
                'domain_hash' => $this->domainBinding->currentDomainHash(),
            ]);

            $status = $response->successful()
                ? ($response->json('status', 'invalid'))
                : 'invalid';

        } catch (\Throwable $e) {
            Log::info('License server unreachable, entering grace period', ['error' => $e->getMessage()]);
            $gracePeriod = config('license.grace_period_seconds', 72 * 3600);
            $lastSuccess = $this->cache->lastSuccessAt();
            $status = ($lastSuccess && (time() - $lastSuccess) < $gracePeriod) ? 'grace' : 'invalid';
        }

        $this->cache->store([
            'status' => $status,
            'product_id' => $activation['product_id'] ?? null,
        ]);

        return $status;
    }

    private function storeActivation(string $licenseKey, string $productId): void
    {
        file_put_contents(storage_path('app/aeos.license'), json_encode([
            'license_key' => $licenseKey,
            'product_id' => $productId,
            'activated_at' => now()->toIso8601String(),
        ]));
    }

    private function loadActivation(): ?array
    {
        $path = storage_path('app/aeos.license');
        if (! file_exists($path)) {
            return null;
        }

        return json_decode(file_get_contents($path), true);
    }
}
