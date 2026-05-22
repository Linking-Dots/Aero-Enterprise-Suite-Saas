<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Infra;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\Infra\AddCustomDomainRequest;
use Aero\Platform\Http\Requests\Admin\Infra\ConfigureDkimRequest;
use Aero\Platform\Http\Requests\Admin\Infra\UpdateTenantBrandingRequest;
use Aero\Platform\Models\Infra\TenantBranding;
use Aero\Platform\Models\Infra\TenantCustomDomain;
use Aero\Platform\Services\Infra\CustomDomainService;
use Aero\Platform\Services\Infra\TenantBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhiteLabelController extends Controller
{
    public function __construct(
        private readonly CustomDomainService $domainSvc,
        private readonly TenantBrandingService $brandingSvc,
    ) {}

    // -------------------------------------------------------------------------
    // Custom Domains
    // -------------------------------------------------------------------------

    public function domainsIndex(): Response
    {
        return Inertia::render('Platform/Admin/WhiteLabel/Domains', [
            'domains' => TenantCustomDomain::orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function storeDomain(AddCustomDomainRequest $request): RedirectResponse
    {
        $this->domainSvc->addDomain(
            $request->validated('tenant_id'),
            $request->validated('domain'),
        );

        return back()->with('success', 'Domain added. DNS verification required.');
    }

    public function verifyDomain(TenantCustomDomain $domain): RedirectResponse
    {
        $verified = $this->domainSvc->verifyDns($domain);

        return back()->with(
            $verified ? 'success' : 'error',
            $verified ? 'Domain DNS verified.' : 'DNS verification failed — TXT record not found.'
        );
    }

    public function destroyDomain(TenantCustomDomain $domain): RedirectResponse
    {
        $this->domainSvc->removeDomain($domain);

        return back()->with('success', 'Domain removed.');
    }

    // -------------------------------------------------------------------------
    // SSL Provisioning
    // -------------------------------------------------------------------------

    public function provisionSsl(TenantCustomDomain $domain): RedirectResponse
    {
        $this->domainSvc->provisionSsl($domain);

        return back()->with('success', 'SSL provisioning queued.');
    }

    public function renewSsl(TenantCustomDomain $domain): RedirectResponse
    {
        $this->domainSvc->renewSsl($domain);

        return back()->with('success', 'SSL renewal queued.');
    }

    // -------------------------------------------------------------------------
    // Tenant Branding
    // -------------------------------------------------------------------------

    public function showBranding(string $tenantId): Response
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);

        return Inertia::render('Platform/Admin/WhiteLabel/Branding', [
            'branding' => $branding,
            'tenantId' => $tenantId,
        ]);
    }

    public function updateBranding(UpdateTenantBrandingRequest $request): RedirectResponse
    {
        $branding = $this->brandingSvc->getForTenant($request->validated('tenant_id'));

        $data = $request->safe()->except(['logo', 'favicon', 'tenant_id']);
        if (! empty($data)) {
            $this->brandingSvc->update($branding, $data);
        }

        if ($request->hasFile('logo')) {
            $this->brandingSvc->uploadLogo($branding, $request->file('logo'), 'logo');
        }

        if ($request->hasFile('favicon')) {
            $this->brandingSvc->uploadLogo($branding, $request->file('favicon'), 'favicon');
        }

        return back()->with('success', 'Branding updated.');
    }

    // -------------------------------------------------------------------------
    // Custom CSS
    // -------------------------------------------------------------------------

    public function showCss(string $tenantId): Response
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);

        return Inertia::render('Platform/Admin/WhiteLabel/Css', [
            'tenantId' => $tenantId,
            'cssPath' => $branding->custom_css_path,
        ]);
    }

    public function updateCss(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'string'],
            'css' => ['required', 'string', 'max:100000'],
        ]);

        $branding = $this->brandingSvc->getForTenant($data['tenant_id']);
        $this->brandingSvc->updateCustomCss($branding, $data['css']);

        return back()->with('success', 'Custom CSS saved.');
    }

    // -------------------------------------------------------------------------
    // Email Branding / DKIM
    // -------------------------------------------------------------------------

    public function showEmailBranding(string $tenantId): Response
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);

        return Inertia::render('Platform/Admin/WhiteLabel/EmailBranding', [
            'tenantId' => $tenantId,
            'emailFromName' => $branding->email_from_name,
            'emailFromAddress' => $branding->email_from_address,
            'dkimSelector' => $branding->dkim_selector,
            'dkimConfigured' => (bool) $branding->dkim_private_key,
        ]);
    }

    public function configureDkim(ConfigureDkimRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $branding = $this->brandingSvc->getForTenant($request->input('tenant_id'));

        $this->brandingSvc->configureDkim(
            $branding,
            $validated['dkim_selector'],
            $validated['dkim_private_key'],
        );

        return back()->with('success', 'DKIM configured.');
    }

    public function verifyDkim(TenantBranding $branding): RedirectResponse
    {
        $verified = $this->brandingSvc->verifyDkim($branding);

        return back()->with(
            $verified ? 'success' : 'error',
            $verified ? 'DKIM verified.' : 'DKIM DNS record not found.'
        );
    }
}
