<?php

namespace Aero\Core\Services;

use Aero\Core\Support\TenantCache;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseValidationService
{
    /**
     * License provider constants
     */
    const PROVIDER_AERO = 'aero';

    const PROVIDER_CODECANYON = 'codecanyon';

    const PROVIDER_ENTERPRISE = 'enterprise';

    /**
     * License types
     */
    const TYPE_REGULAR = 'regular';

    const TYPE_EXTENDED = 'extended';

    const TYPE_ENTERPRISE = 'enterprise';

    /**
     * Validate a license key
     */
    public function validate(string $licenseKey, ?string $email = null, ?string $domain = null, array $additionalData = []): array
    {
        try {
            // Detect provider from license key format
            $provider = $this->detectProvider($licenseKey);

            // Validate based on provider
            switch ($provider) {
                case self::PROVIDER_AERO:
                    return $this->validateAeroPlatform($licenseKey, $email, $domain, $additionalData);

                case self::PROVIDER_CODECANYON:
                    return $this->validateCodeCanyon($licenseKey, $email, $domain, $additionalData);

                case self::PROVIDER_ENTERPRISE:
                    return $this->validateEnterprise($licenseKey, $email, $domain, $additionalData);

                default:
                    return $this->errorResponse('Invalid license key format');
            }
        } catch (Exception $e) {
            Log::error('License validation error: '.$e->getMessage(), [
                'license' => substr($licenseKey, 0, 10).'...',
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('License validation failed: '.$e->getMessage());
        }
    }

    /**
     * Validate a module license
     */
    public function validateModule(string $moduleCode, string $licenseKey, ?string $email = null, ?string $domain = null): array
    {
        try {
            $provider = $this->detectProvider($licenseKey);

            // Module-specific validation
            $result = $this->validate($licenseKey, $email, $domain, ['module' => $moduleCode]);

            // Check if license covers the requested module
            if ($result['success']) {
                $allowedModules = $result['data']['allowed_modules'] ?? [];

                if (! in_array($moduleCode, $allowedModules) && ! in_array('all', $allowedModules)) {
                    return $this->errorResponse("License does not cover module: {$moduleCode}");
                }
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Module license validation error: '.$e->getMessage(), [
                'module' => $moduleCode,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Module license validation failed: '.$e->getMessage());
        }
    }

    /**
     * Detect license provider from key format
     */
    protected function detectProvider(string $licenseKey): string
    {
        // Format: [Provider]-[Product]-[Module]-[Key]-[Checksum]
        // Examples:
        // AP-AES-HRM-XXXXX-XXXXX (Aero Platform)
        // CC-AES-HRM-XXXXX-XXXXX (CodeCanyon)
        // EP-AES-ALL-XXXXX-XXXXX (Enterprise)

        $parts = explode('-', $licenseKey);

        if (count($parts) < 3) {
            return 'unknown';
        }

        $prefix = strtoupper($parts[0]);

        return match ($prefix) {
            'AP' => self::PROVIDER_AERO,
            'CC' => self::PROVIDER_CODECANYON,
            'EP', 'ENT' => self::PROVIDER_ENTERPRISE,
            default => 'unknown'
        };
    }

    /**
     * Aero Platform API URL (hardcoded for security)
     */
    const AERO_PLATFORM_API_URL = 'https://aeos365.com/api';

    /**
     * Validate license through Aero Platform API
     */
    protected function validateAeroPlatform(string $licenseKey, ?string $email, ?string $domain, array $additionalData = []): array
    {
        // Use hardcoded URL for security - cannot be overridden via config
        $apiUrl = self::AERO_PLATFORM_API_URL;
        $timeout = config('license.providers.aero.timeout', 10);

        try {
            $response = Http::timeout($timeout)
                ->post($apiUrl.'/v1/licenses/validate', [
                    'license_key' => $licenseKey,
                    'email' => $email,
                    'domain' => $domain,
                    'ip' => request()->ip(),
                    'server_hostname' => gethostname(),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'additional_data' => $additionalData,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    // Cache the validation result
                    $this->cacheValidation($licenseKey, $data);

                    return $this->successResponse($data['data'], $data['message'] ?? 'License validated successfully');
                }

                return $this->errorResponse($data['message'] ?? 'License validation failed');
            }

            // Network error - check cache
            return $this->checkCachedValidation($licenseKey);

        } catch (Exception $e) {
            Log::warning('Aero Platform API unreachable, checking cache', [
                'error' => $e->getMessage(),
            ]);

            return $this->checkCachedValidation($licenseKey);
        }
    }

    /**
     * Validate CodeCanyon/Envato license
     */
    protected function validateCodeCanyon(string $licenseKey, ?string $email, ?string $domain, array $additionalData = []): array
    {
        $apiUrl = config('license.providers.codecanyon.api_url');
        $personalToken = config('license.providers.codecanyon.personal_token');

        try {
            // First, verify purchase code with Envato API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$personalToken,
                'User-Agent' => 'aeos365',
            ])->get($apiUrl.'/v3/market/author/sale', [
                'code' => $licenseKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract purchase information
                $purchaseData = [
                    'license_key' => $licenseKey,
                    'provider' => self::PROVIDER_CODECANYON,
                    'customer_name' => $data['buyer'] ?? 'Unknown',
                    'customer_email' => $email ?? $data['email'] ?? null,
                    'purchase_date' => $data['sold_at'] ?? now(),
                    'license_type' => $data['license'] ?? self::TYPE_REGULAR,
                    'support_until' => $data['supported_until'] ?? null,
                    'item_id' => $data['item']['id'] ?? null,
                    'item_name' => $data['item']['name'] ?? null,
                    'allowed_modules' => $this->extractModulesFromPurchase($data),
                ];

                // Cache validation
                $this->cacheValidation($licenseKey, $purchaseData);

                return $this->successResponse($purchaseData, 'CodeCanyon license validated successfully');
            }

            // Check cache if API fails
            return $this->checkCachedValidation($licenseKey);

        } catch (Exception $e) {
            Log::warning('CodeCanyon API error, checking cache', [
                'error' => $e->getMessage(),
            ]);

            return $this->checkCachedValidation($licenseKey);
        }
    }

    /**
     * Validate Enterprise license (offline validation)
     */
    protected function validateEnterprise(string $licenseKey, ?string $email, ?string $domain, array $additionalData = []): array
    {
        // Enterprise licenses are validated offline using cryptographic signature
        try {
            $parts = explode('-', $licenseKey);

            if (count($parts) < 5) {
                return $this->errorResponse('Invalid enterprise license format');
            }

            // Extract components
            [, $product, $modules, $key, $checksum] = $parts;

            // Verify checksum
            $expectedChecksum = $this->generateEnterpriseChecksum($product, $modules, $key, $domain);

            if ($checksum !== $expectedChecksum) {
                return $this->errorResponse('Invalid enterprise license checksum');
            }

            // Decode modules
            $allowedModules = $this->decodeEnterpriseModules($modules);

            $licenseData = [
                'license_key' => $licenseKey,
                'provider' => self::PROVIDER_ENTERPRISE,
                'license_type' => self::TYPE_ENTERPRISE,
                'customer_email' => $email,
                'domain' => $domain,
                'allowed_modules' => $allowedModules,
                'expires_at' => null, // Enterprise licenses don't expire
                'support_until' => null,
            ];

            // Cache validation
            $this->cacheValidation($licenseKey, $licenseData);

            return $this->successResponse($licenseData, 'Enterprise license validated successfully');

        } catch (Exception $e) {
            Log::error('Enterprise license validation error: '.$e->getMessage());

            return $this->errorResponse('Invalid enterprise license');
        }
    }

    /**
     * Cache validation result
     */
    protected function cacheValidation(string $licenseKey, array $data): void
    {
        $cacheKey = 'license_validation:'.md5($licenseKey);
        $cacheDuration = config('license.cache_duration', 86400); // 24 hours default

        TenantCache::put($cacheKey, $data, $cacheDuration);
    }

    /**
     * Check cached validation (offline/grace period mode)
     */
    protected function checkCachedValidation(string $licenseKey): array
    {
        $cacheKey = 'license_validation:'.md5($licenseKey);

        if (TenantCache::has($cacheKey)) {
            $cachedData = TenantCache::get($cacheKey);

            return $this->successResponse(
                $cachedData,
                'License validated from cache (offline mode)'
            );
        }

        return $this->errorResponse('License validation failed and no cached validation found');
    }

    /**
     * Extract allowed modules from CodeCanyon purchase
     */
    protected function extractModulesFromPurchase(array $purchaseData): array
    {
        $itemName = $purchaseData['item']['name'] ?? '';

        // Map item names to module codes
        $moduleMap = [
            'Aero HRM' => ['core', 'hrm'],
            'Aero CRM' => ['core', 'crm'],
            'Aero RFI' => ['core', 'rfi'],
            'Aero Finance' => ['core', 'finance'],
            'Aero Project' => ['core', 'project'],
            'aeos365' => ['all'], // Full suite
        ];

        foreach ($moduleMap as $name => $modules) {
            if (stripos($itemName, $name) !== false) {
                return $modules;
            }
        }

        return ['core']; // Default to core only
    }

    /**
     * Decode enterprise license modules
     */
    protected function decodeEnterpriseModules(string $encoded): array
    {
        if ($encoded === 'ALL') {
            return ['all'];
        }

        // Decode base64 encoded module list
        try {
            $decoded = base64_decode($encoded);

            return explode(',', $decoded);
        } catch (Exception $e) {
            return ['core'];
        }
    }

    /**
     * Generate enterprise license checksum
     */
    protected function generateEnterpriseChecksum(string $product, string $modules, string $key, ?string $domain): string
    {
        $secret = config('license.providers.enterprise.secret_key');
        $data = $product.$modules.$key.($domain ?? '');

        return strtoupper(substr(hash_hmac('sha256', $data, $secret), 0, 5));
    }

    /**
     * Success response format
     */
    protected function successResponse(array $data, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Error response format
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];
    }

    /**
     * Store validated license to file
     */
    public function storeLicense(string $licenseKey, array $licenseData): bool
    {
        try {
            $storagePath = storage_path('framework/.license');

            $data = [
                'license_key' => $licenseKey,
                'validated_at' => now()->toIso8601String(),
                'data' => $licenseData,
            ];

            // Encrypt and store
            $encrypted = encrypt(json_encode($data));
            file_put_contents($storagePath, $encrypted);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to store license: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Load stored license
     */
    public function loadStoredLicense(): ?array
    {
        try {
            $storagePath = storage_path('framework/.license');

            if (! file_exists($storagePath)) {
                return null;
            }

            $encrypted = file_get_contents($storagePath);
            $decrypted = decrypt($encrypted);

            return json_decode($decrypted, true);
        } catch (Exception $e) {
            Log::error('Failed to load stored license: '.$e->getMessage());

            return null;
        }
    }
}
