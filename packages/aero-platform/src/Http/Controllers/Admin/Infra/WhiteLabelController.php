<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Infra;

use Aero\Kernel\Branding\BrandingPayload;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\Infra\AddCustomDomainRequest;
use Aero\Platform\Http\Requests\Admin\Infra\ConfigureDkimRequest;
use Aero\Platform\Models\Infra\TenantBranding;
use Aero\Platform\Models\Infra\TenantCustomDomain;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\Infra\CustomDomainService;
use Aero\Platform\Services\Infra\TenantBrandingService;
use Illuminate\Http\JsonResponse;
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
    // Command center
    // -------------------------------------------------------------------------

    public function overview(): Response
    {
        return Inertia::render('Platform/Admin/WhiteLabel/P2/WhiteLabel', [
            'overview' => $this->brandingSvc->overview(),
            'platformBranding' => $this->platformStudioPayload(),
        ]);
    }

    /** The platform's own brand for the Defaults tab (same shape Settings uses). */
    private function platformStudioPayload(): array
    {
        $setting = PlatformSetting::current();
        $layer = $setting->getBrandingPayload();
        $layer['name'] ??= $setting->site_name;
        $layer['tagline'] ??= $setting->tagline;

        return [
            'overrides' => $layer,
            'resolved' => BrandingPayload::merge($layer),
            'defaults' => BrandingPayload::defaults(),
            'entitled' => true,
            'customized' => BrandingPayload::isCustomized($layer),
        ];
    }

    // -------------------------------------------------------------------------
    // Legacy sub-pages — subsumed by the console
    // -------------------------------------------------------------------------

    public function domainsIndex(): RedirectResponse
    {
        return redirect('/white-label?tab=domains');
    }

    public function showBranding(string $tenantId): RedirectResponse
    {
        return redirect('/white-label?tab=branding&tenant='.urlencode($tenantId));
    }

    public function showCss(string $tenantId): RedirectResponse
    {
        return redirect('/white-label?tab=css&tenant='.urlencode($tenantId));
    }

    public function showEmailBranding(string $tenantId): RedirectResponse
    {
        return redirect('/white-label?tab=email&tenant='.urlencode($tenantId));
    }

    // -------------------------------------------------------------------------
    // Custom Domains
    // -------------------------------------------------------------------------

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
    // Tenant Branding (BrandStudio contract)
    // -------------------------------------------------------------------------

    /** Studio payload for the per-tenant drawer (JSON fetch). */
    public function studio(string $tenantId): JsonResponse
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);

        return response()->json($this->brandingSvc->studioPayload($branding));
    }

    public function updateBranding(Request $request, string $tenantId): RedirectResponse
    {
        $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'primary_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'email_from_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email_from_address' => ['sometimes', 'nullable', 'email', 'max:190'],
            'logo_light' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'logo_icon' => ['nullable', 'image', 'max:1024'],
            'favicon' => ['nullable', 'image', 'max:512'],
            'login_background' => ['nullable', 'image', 'max:4096'],
            'remove_logo_light' => ['sometimes', 'boolean'],
            'remove_logo_dark' => ['sometimes', 'boolean'],
            'remove_logo_icon' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'remove_login_background' => ['sometimes', 'boolean'],
        ]);

        $branding = $this->brandingSvc->getForTenant($tenantId);

        $scalars = collect(TenantBrandingService::SCALAR_COLUMNS)
            ->keys()
            ->filter(fn (string $key) => $request->has($key))
            ->mapWithKeys(fn (string $key) => [$key => $request->input($key)])
            ->all();

        $files = collect(TenantBrandingService::ASSET_COLUMNS)
            ->keys()
            ->filter(fn (string $key) => $request->hasFile($key))
            ->mapWithKeys(fn (string $key) => [$key => $request->file($key)])
            ->all();

        $removals = collect(TenantBrandingService::ASSET_COLUMNS)
            ->keys()
            ->filter(fn (string $key) => $request->boolean("remove_{$key}"))
            ->values()
            ->all();

        $this->brandingSvc->updateFromStudio($branding, $scalars, $files, $removals);

        return back()->with('success', 'Tenant branding saved.');
    }

    public function resetBranding(string $tenantId): RedirectResponse
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);
        $this->brandingSvc->resetBranding($branding);

        return back()->with('success', 'Tenant branding reset to platform defaults.');
    }

    // -------------------------------------------------------------------------
    // Custom CSS
    // -------------------------------------------------------------------------

    /** CSS content for the editor (JSON fetch). */
    public function cssContent(string $tenantId): JsonResponse
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);

        return response()->json([
            'css' => $this->brandingSvc->getCssContent($branding),
            'disabled' => (bool) $branding->css_disabled,
            'path' => $branding->custom_css_path,
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

    /** Kill switch — toggle a tenant's custom CSS without deleting it. */
    public function toggleCss(string $tenantId): RedirectResponse
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);
        $enabled = (bool) $branding->css_disabled; // disabled → enable, enabled → disable
        $this->brandingSvc->setCssEnabled($branding, $enabled);

        return back()->with('success', 'Custom CSS '.($enabled ? 'enabled' : 'disabled').'.');
    }

    public function destroyCss(string $tenantId): RedirectResponse
    {
        $branding = $this->brandingSvc->getForTenant($tenantId);
        $this->brandingSvc->removeCustomCss($branding);

        return back()->with('success', 'Custom CSS removed.');
    }

    // -------------------------------------------------------------------------
    // Email Branding / DKIM
    // -------------------------------------------------------------------------

    public function configureDkim(ConfigureDkimRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $branding = $this->brandingSvc->getForTenant($request->input('tenant_id'));

        // Sender identity travels with DKIM setup when provided
        $sender = array_filter([
            'email_from_name' => $request->input('email_from_name'),
            'email_from_address' => $request->input('email_from_address'),
        ], fn ($v) => $v !== null && $v !== '');
        if ($sender !== []) {
            $this->brandingSvc->update($branding, $sender);
        }

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

    public function destroyDkim(TenantBranding $branding): RedirectResponse
    {
        $this->brandingSvc->clearDkim($branding);

        return back()->with('success', 'DKIM configuration removed.');
    }
}
