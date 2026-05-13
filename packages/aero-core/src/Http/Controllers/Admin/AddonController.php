<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Contracts\LicenseServiceInterface;
use Aero\Core\Models\InstalledAddon;
use Aero\Core\Services\AddonCatalogService;
use Aero\Core\Services\AddonInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AddonController extends Controller
{
    public function __construct(
        private readonly AddonCatalogService $catalog,
        private readonly AddonInstaller $installer,
        private readonly LicenseServiceInterface $license,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Addons/Index', [
            'installed' => InstalledAddon::orderBy('installed_at', 'desc')->get(),
            'available' => $this->catalog->getAvailableAddons(),
            'product' => config('product', []),
            'marketplace_url' => rtrim(config('license.server_url', ''), '/').'/marketplace',
        ]);
    }

    public function install(Request $request): RedirectResponse
    {
        $request->validate([
            'license_key' => ['required', 'string'],
            'product_code' => ['required', 'string'],
            'zip_file' => ['nullable', 'file', 'mimes:zip', 'max:102400'],
        ]);

        $licenseKey = strtoupper(trim($request->license_key));
        $productCode = $request->product_code;

        $validation = $this->validateLicenseKey($licenseKey, $productCode);
        if (! $validation['valid']) {
            return back()->withErrors(['license_key' => $validation['message']]);
        }

        $zipPath = null;
        $expectedChecksum = null;
        try {
            if ($request->hasFile('zip_file')) {
                $stored = $request->file('zip_file')->store('addon-uploads', 'local');
                $zipPath = storage_path("app/{$stored}");
            } else {
                $download = $this->autoDownload($licenseKey, $productCode);
                if ($download === null) {
                    return back()->withErrors([
                        'zip_file' => 'Auto-download failed. Please upload the ZIP file manually.',
                    ]);
                }
                if ($download['checksum'] === null) {
                    return back()->withErrors([
                        'zip_file' => 'This add-on cannot be auto-installed because the license server did not provide an integrity checksum. Please download and install the ZIP file manually from your account portal.',
                    ]);
                }
                $zipPath = $download['path'];
                $expectedChecksum = $download['checksum'];
            }

            if ($zipPath === null) {
                return back()->withErrors([
                    'zip_file' => 'Auto-download failed. Please upload the ZIP file manually.',
                ]);
            }

            $addon = $this->installer->install($zipPath, $licenseKey, $expectedChecksum);

            return redirect()->route('addons.index')
                ->with('success', "Add-on [{$addon->name}] installed successfully. Refresh to see it in the navigation.");

        } catch (\RuntimeException $e) {
            return back()->withErrors(['zip_file' => $e->getMessage()]);
        } finally {
            if ($zipPath && str_contains($zipPath, 'addon-downloads') && file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    private function validateLicenseKey(string $key, string $productCode): array
    {
        $serverUrl = config('license.server_url');
        $domainHash = hash('sha256', strtolower(request()->getHost()));

        try {
            $response = Http::timeout(10)->post("{$serverUrl}/api/license/validate", [
                'license_key' => $key,
                'product_id' => $productCode,
                'domain_hash' => $domainHash,
            ]);

            if (! $response->successful()) {
                return ['valid' => false, 'message' => 'Could not reach license server. Try again shortly.'];
            }

            return match ($response->json('status')) {
                'valid' => ['valid' => true,  'message' => ''],
                'expired' => ['valid' => false, 'message' => 'This license key has expired. Please renew.'],
                default => ['valid' => false, 'message' => 'Invalid license key. Please check your purchase email.'],
            };

        } catch (\Throwable $e) {
            Log::warning('AddonController: license validation failed', ['error' => $e->getMessage()]);

            return ['valid' => false, 'message' => 'Could not reach license server. Please try manual ZIP install.'];
        }
    }

    private function autoDownload(string $licenseKey, string $productCode): ?array
    {
        $serverUrl = config('license.server_url');
        try {
            $response = Http::timeout(15)->post("{$serverUrl}/api/license/download-url", [
                'license_key' => $licenseKey,
                'product_id' => $productCode,
            ]);

            if (! $response->successful() || ! $response->json('download_url')) {
                return null;
            }

            $downloadUrl = $response->json('download_url');
            $expectedChecksum = $response->json('expected_sha256');
            $zipPath = storage_path('app/addon-downloads/'.$productCode.'-'.time().'.zip');

            if (! is_dir(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $fileResponse = Http::timeout(120)->sink($zipPath)->get($downloadUrl);

            return $fileResponse->successful() ? ['path' => $zipPath, 'checksum' => $expectedChecksum] : null;

        } catch (\Throwable $e) {
            Log::warning('AddonController: auto-download failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
