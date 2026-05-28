<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\License\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LicenseManagementController extends Controller
{
    public function __construct(
        private LicenseService $licenseService,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        $license = $this->licenseService->getCurrent();

        return Inertia::render('Core/License/Index', [
            'license' => $license,
        ]);
    }

    public function activation(): Response
    {
        $license = $this->licenseService->getCurrent();

        return Inertia::render('Core/License/Activation', ['license' => $license]);
    }

    public function activate(Request $request): RedirectResponse
    {
        $request->validate(['license_key' => ['required', 'string', 'min:20']]);

        $result = $this->licenseService->activate($request->license_key);

        if (! $result['success']) {
            return back()->with('error', $result['message'] ?? 'Activation failed.');
        }

        $this->audit->log(AuditEventType::LICENSE_ACTIVATED->value, 'activated', null, 'License key activated');

        return redirect()->route('core.license.index')->with('success', 'License activated successfully.');
    }

    public function deactivate(Request $request): RedirectResponse
    {
        $this->licenseService->deactivate();
        $this->audit->log(AuditEventType::LICENSE_DEACTIVATED->value, 'deactivated', null, 'License key deactivated');

        return back()->with('success', 'License deactivated.');
    }

    public function features(): Response
    {
        return Inertia::render('Core/License/Features', [
            'features' => $this->licenseService->getFeatures(),
            'edition' => $this->licenseService->getEdition(),
        ]);
    }

    public function renewal(): Response
    {
        $license = $this->licenseService->getCurrent();

        return Inertia::render('Core/License/Renewal', [
            'license' => $license,
            'renewal_url' => 'https://aeos365.com/renew',
        ]);
    }

    public function updates(): Response
    {
        $current = config('app.version', '1.0.0');
        $latest = $this->licenseService->checkForUpdates();

        return Inertia::render('Core/License/Updates', [
            'current_version' => $current,
            'latest_version' => $latest['version'] ?? $current,
            'has_update' => isset($latest['version']) && version_compare($latest['version'], $current, '>'),
            'changelog' => $latest['changelog'] ?? [],
        ]);
    }
}
