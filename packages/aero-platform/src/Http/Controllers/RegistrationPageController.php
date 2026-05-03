<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers;

use Aero\Core\Services\Module\ModuleDiscoveryService;
use Aero\Core\Support\SafeRedirect;
use Aero\Platform\Models\Module;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Monitoring\Tenant\TenantRegistrationSession;
use Aero\Platform\Services\PlanCanonicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationPageController extends Controller
{
    /**
     * Registration flow steps (admin setup moved to after provisioning on tenant domain).
     */
    private array $steps = [
        ['key' => 'account', 'label' => 'Account Type', 'route' => 'platform.register.index'],
        ['key' => 'details', 'label' => 'Company Details', 'route' => 'platform.register.details'],
        ['key' => 'verify-email', 'label' => 'Verify Email', 'route' => 'platform.register.verify-email'],
        ['key' => 'verify-phone', 'label' => 'Verify Phone', 'route' => 'platform.register.verify-phone'],
        ['key' => 'plan', 'label' => 'Modules & Plan', 'route' => 'platform.register.plan'],
        ['key' => 'payment', 'label' => 'Review', 'route' => 'platform.register.payment'],
        ['key' => 'provisioning', 'label' => 'Setting Up', 'route' => 'platform.register.provisioning'],
        // Admin setup happens on tenant domain after provisioning completes
    ];

    public function __construct(
        private TenantRegistrationSession $registrationSession,
        private ModuleDiscoveryService $moduleDiscovery,
        private PlanCanonicalService $planCanonicalService,
    ) {}

    public function accountType(): Response
    {
        return $this->renderStep('account', [
            'trialDays' => (int) config('platform.trial_days', 14),
        ]);
    }

    public function details(): Response|RedirectResponse
    {
        if (! $this->registrationSession->hasStep('account')) {
            // Use SafeRedirect for safe navigation in public registration flow
            return SafeRedirect::toRoute('platform.register.index', [], 'platform.register.index');
        }

        $account = $this->registrationSession->get()['account'] ?? [];
        $details = $this->registrationSession->get()['details'] ?? [];

        return $this->renderStep('details', [
            'accountType' => $account['type'] ?? null,
            'baseDomain' => config('platform.central_domain'),
            'existingSubdomain' => $details['subdomain'] ?? null, // Pass existing subdomain to skip check
        ]);
    }

    /**
     * Email verification page (verify company email from details step).
     */
    public function verifyEmail(): Response|RedirectResponse
    {
        // Only require account and details (admin setup moved to after provisioning)
        if (! $this->registrationSession->ensureSteps(['account', 'details'])) {
            return SafeRedirect::toRoute('platform.register.index', [], 'platform.register.index');
        }

        // In debug mode, skip email & phone verification entirely
        if (config('app.debug')) {
            return SafeRedirect::toRoute('platform.register.plan', [], 'platform.register.plan');
        }

        $details = $this->registrationSession->get()['details'] ?? [];

        return $this->renderStep('verify-email', [
            'email' => $details['email'] ?? '',
            'companyName' => $details['name'] ?? '',
        ]);
    }

    /**
     * Phone verification page (verify company phone from details step).
     */
    public function verifyPhone(): Response|RedirectResponse
    {
        // Require verification step to have been started
        if (! $this->registrationSession->ensureSteps(['account', 'details', 'verification'])) {
            return SafeRedirect::toRoute('platform.register.index', [], 'platform.register.index');
        }

        // In debug mode, skip phone verification entirely
        if (config('app.debug')) {
            return SafeRedirect::toRoute('platform.register.plan', [], 'platform.register.plan');
        }

        $details = $this->registrationSession->get()['details'] ?? [];

        return $this->renderStep('verify-phone', [
            'phone' => $details['phone'] ?? '',
            'companyName' => $details['name'] ?? '',
        ]);
    }

    public function plan(): Response|RedirectResponse
    {
        // Only require account and details (admin setup moved to after provisioning)
        if (! $this->registrationSession->ensureSteps(['account', 'details'])) {
            return SafeRedirect::toRoute('platform.register.index', [], 'platform.register.index');
        }

        // Fetch sellable modules from module_pricing table (authoritative source for products)
        // Enrich with metadata from ModuleDiscoveryService for names, descriptions, icons
        $discoveredModules = $this->getSellableModules();

        // Fetch plans and enrich with discovered module data
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->with('planQuotas')
            ->get()
            ->map(function ($plan) use ($discoveredModules) {
                $dto = $this->planCanonicalService->toDto($plan);
                $limits = $dto['limits'] ?? [];
                $moduleCodes = $plan->module_codes ?? [];

                // Enrich module codes with full module info from discovered modules
                $enrichedModules = collect($moduleCodes)
                    ->map(function ($code) use ($discoveredModules) {
                        $moduleConfig = $discoveredModules->get($code);
                        if (! $moduleConfig) {
                            return null;
                        }

                        return [
                            'id' => $code,
                            'code' => $code,
                            'name' => $moduleConfig['name'] ?? ucfirst($code),
                            'description' => $moduleConfig['description'] ?? '',
                            'icon' => $moduleConfig['icon'] ?? null,
                            'category' => $moduleConfig['category'] ?? 'General',
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    ...$dto,
                    'limits' => $limits,
                    'badge' => $limits['badge'] ?? null,
                    'modules' => $enrichedModules,
                ];
            });

        // Format discovered modules for individual selection
        $modules = $discoveredModules
            ->map(function ($module) {
                return [
                    'id' => $module['code'],
                    'code' => $module['code'],
                    'name' => $module['name'],
                    'description' => $module['description'] ?? '',
                    'category' => $module['category'] ?? 'General',
                    'icon' => $module['icon'] ?? null,
                ];
            })
            ->sortBy('priority')
            ->values();

        return $this->renderStep('plan', [
            'plans' => $plans,
            'modules' => $modules,
            'modulePricing' => $this->getModulePricing(),
        ]);
    }

    public function payment(): Response|RedirectResponse
    {
        // Only require account, details, and plan (admin setup moved to after provisioning)
        if (! $this->registrationSession->ensureSteps(['account', 'details', 'plan'])) {
            return SafeRedirect::toRoute('platform.register.index', [], 'platform.register.index');
        }

        // Get plan data and validate that user has selected something
        $planData = $this->registrationSession->get()['plan'] ?? [];
        $hasSelection = ! empty($planData['plan_id']) || ! empty($planData['modules']);

        if (! $hasSelection) {
            return SafeRedirect::withError(
                'platform.register.plan',
                'Please select a plan or modules before continuing.',
                []
            );
        }

        // Fetch sellable modules from module_pricing table (authoritative source for products)
        $discoveredModules = $this->getSellableModules();

        // Fetch plans and enrich with discovered module data
        $plans = Plan::where('is_active', true)
            ->with('planQuotas')
            ->get()
            ->map(function ($plan) use ($discoveredModules) {
                $dto = $this->planCanonicalService->toDto($plan);
                $moduleCodes = $plan->module_codes ?? [];

                // Enrich module codes with full module info from discovered modules
                $enrichedModules = collect($moduleCodes)
                    ->map(function ($code) use ($discoveredModules) {
                        $moduleConfig = $discoveredModules->get($code);
                        if (! $moduleConfig) {
                            return null;
                        }

                        return [
                            'id' => $code,
                            'code' => $code,
                            'name' => $moduleConfig['name'] ?? ucfirst($code),
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    ...$dto,
                    'modules' => $enrichedModules,
                ];
            });

        // Format discovered modules for display
        $modules = $discoveredModules
            ->map(function ($module) {
                return [
                    'id' => $module['code'],
                    'code' => $module['code'],
                    'name' => $module['name'],
                ];
            })
            ->values();

        return $this->renderStep('payment', [
            'trialDays' => (int) config('platform.trial_days', 14),
            'baseDomain' => config('platform.central_domain'),
            'plans' => $plans,
            'modules' => $modules,
            'modulePricing' => $this->getModulePricing(),
        ]);
    }

    /**
     * Provisioning status "waiting room" page.
     *
     * Shows the user real-time status of their workspace provisioning.
     */
    public function provisioning(Tenant $tenant): Response
    {
        return $this->renderStep('provisioning', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
                'provisioning_step' => $tenant->provisioning_step,
            ],
            'baseDomain' => config('platform.central_domain'),
        ]);
    }

    /**
     * API endpoint to check provisioning status.
     *
     * Called by the frontend to poll for status updates.
     * Redirects to admin-setup page instead of login when ready.
     */
    public function provisioningStatus(Tenant $tenant): JsonResponse
    {
        $baseDomain = config('platform.central_domain');
        $domain = sprintf('%s.%s', $tenant->subdomain, $baseDomain);

        // Check if tenant has already completed admin setup
        $adminSetupCompleted = $tenant->isAdminSetupComplete();

        // Determine redirect URL based on admin setup status
        $redirectUrl = null;
        if ($tenant->status === Tenant::STATUS_ACTIVE) {
            $scheme = request()->secure() ? 'https' : 'http';
            // If admin setup is not complete, redirect to admin-setup page
            // Otherwise redirect to login
            $redirectUrl = $adminSetupCompleted
                ? sprintf('%s://%s/login', $scheme, $domain)
                : sprintf('%s://%s/admin-setup', $scheme, $domain);
        }

        return response()->json([
            'id' => $tenant->id,
            'status' => $tenant->status,
            'step' => $tenant->provisioning_step,
            'provisioning_step' => $tenant->provisioning_step, // Alias for backward compatibility
            'domain' => $domain,
            'is_ready' => $tenant->status === Tenant::STATUS_ACTIVE,
            'has_failed' => $tenant->status === Tenant::STATUS_FAILED,
            'error' => $tenant->status === Tenant::STATUS_FAILED
                ? ($tenant->data['provisioning_error'] ?? 'Provisioning failed')
                : null,
            'login_url' => $redirectUrl,
            'needs_admin_setup' => ! $adminSetupCompleted,
        ]);
    }

    public function success(): Response|RedirectResponse
    {
        $result = $this->registrationSession->pullSuccess();

        if ($result === null) {
            return SafeRedirect::toRoute('platform.register.index', [], 'platform.register.index');
        }

        return $this->renderStep('success', [
            'result' => $result,
            'baseDomain' => config('platform.central_domain'),
        ]);
    }

    /**
     * Get sellable modules from module_pricing table, enriched with metadata
     * from ModuleDiscoveryService. This is the authoritative source for products
     * displayed in the registration plan step.
     */
    private function getSellableModules(): \Illuminate\Support\Collection
    {
        $pricingCodes = \DB::table('module_pricing')
            ->where('is_active', true)
            ->pluck('module_code');

        $allDefinitions = $this->moduleDiscovery->getModuleDefinitions()->keyBy('code');

        return $pricingCodes
            ->map(fn (string $code) => $allDefinitions->get($code))
            ->filter()
            ->keyBy('code');
    }

    /**
     * Get per-module pricing keyed by module code from the module_pricing table.
     */
    private function getModulePricing(): array
    {
        return \DB::table('module_pricing')
            ->where('is_active', true)
            ->get(['module_code', 'monthly_price', 'yearly_price'])
            ->keyBy('module_code')
            ->map(fn ($row) => [
                'monthly' => (float) $row->monthly_price,
                'yearly'  => (float) $row->yearly_price,
            ])
            ->all();
    }

    private function renderStep(string $currentStep, array $props = []): Response
    {
        return $this->render('Platform/Public/Register/RegistrationPage', $currentStep, $props);
    }

    private function render(string $component, string $currentStep, array $props = []): Response
    {
        return Inertia::render($component, [
            ...$props,
            'steps' => $this->steps,
            'currentStep' => $currentStep,
            'savedData' => $this->registrationSession->get(),
        ]);
    }
}
