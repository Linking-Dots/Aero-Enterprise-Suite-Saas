<?php

namespace Aero\Platform\Http\Controllers;

use Aero\Core\Models\User;
use Aero\Core\Services\Module\ModuleDiscoveryService;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\TenantInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TenantOnboardingController
 *
 * Handles the multi-step onboarding wizard for new tenants after admin setup.
 * This wizard guides new tenant admins through essential setup:
 * - Welcome & overview
 * - Company information
 * - Branding & appearance
 * - Team invitations
 * - Module configuration
 *
 * The onboarding status is tracked in the tenant's data JSON column in central DB.
 */
class TenantOnboardingController extends Controller
{
    /**
     * Module discovery service instance.
     */
    protected ModuleDiscoveryService $moduleDiscovery;

    /**
     * Create a new controller instance.
     */
    public function __construct(ModuleDiscoveryService $moduleDiscovery)
    {
        $this->moduleDiscovery = $moduleDiscovery;
    }

    /**
     * Onboarding steps configuration.
     */
    protected array $steps = [
        'welcome' => [
            'title' => 'Welcome',
            'description' => 'Let\'s get your organization set up',
            'order' => 1,
        ],
        'company' => [
            'title' => 'Company Info',
            'description' => 'Tell us about your organization',
            'order' => 2,
        ],
        'branding' => [
            'title' => 'Branding',
            'description' => 'Customize your appearance',
            'order' => 3,
        ],
        'team' => [
            'title' => 'Team',
            'description' => 'Invite your team members',
            'order' => 4,
        ],
        'modules' => [
            'title' => 'Modules',
            'description' => 'Configure your features',
            'order' => 5,
        ],
        'complete' => [
            'title' => 'Complete',
            'description' => 'You\'re all set!',
            'order' => 6,
        ],
    ];

    /**
     * Show the onboarding wizard.
     */
    public function index(Request $request): Response
    {
        $tenant = tenant();
        $user = Auth::user();

        // Get current onboarding progress
        $onboardingData = $this->getTenantData()['onboarding'] ?? [];
        $currentStep = $onboardingData['current_step'] ?? 'welcome';
        $completedSteps = $onboardingData['completed_steps'] ?? [];

        return Inertia::render('Onboarding/Index', [
            'title' => 'Setup Your Organization',
            'steps' => $this->steps,
            'currentStep' => $currentStep,
            'completedSteps' => $completedSteps,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
            ],
            'systemSettings' => $this->getTenantData(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => Role::all()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ]),
            'availableModules' => $this->getAvailableModules(),
        ]);
    }

    /**
     * Save company information step.
     */
    public function saveCompany(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:100',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'website_url' => 'nullable|url|max:255',
        ]);

        // Store in tenant data
        $tenant = tenant();
        $data = $this->getTenantData();
        $data['organization'] = array_merge($data['organization'] ?? [], $validated);
        $this->updateTenantData($data);

        // Update tenant name if changed
        if ($validated['company_name'] !== $tenant->name) {
            // Fix #19: Resolve the central DB connection name from config instead of
            // hardcoding 'mysql', which breaks setups that use a different driver name.
            DB::connection(config('tenancy.database.central_connection', 'mysql'))
                ->table('tenants')
                ->where('id', $tenant->id)
                ->update(['name' => $validated['company_name']]);
        }

        // Mark step as completed
        $this->markStepCompleted('company');

        return back()->with('success', 'Company information saved successfully.');
    }

    /**
     * Save branding step.
     */
    public function saveBranding(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'dark_mode' => 'nullable|boolean',
        ]);

        // Store in tenant data
        $data = $this->getTenantData();
        $data['branding'] = array_merge($data['branding'] ?? [], $validated);
        $this->updateTenantData($data);

        $this->markStepCompleted('branding');

        return back()->with('success', 'Branding settings saved successfully.');
    }

    /**
     * Save team invitations step.
     */
    public function saveTeam(Request $request)
    {
        $validated = $request->validate([
            'invitations' => 'nullable|array',
            'invitations.*.email' => 'required|email',
            'invitations.*.role' => 'required|string',
            'skip' => 'nullable|boolean',
        ]);

        $sentCount = 0;
        $skippedCount = 0;
        $errors = [];

        if (! ($validated['skip'] ?? false) && ! empty($validated['invitations'])) {
            foreach ($validated['invitations'] as $invitation) {
                // Skip if user already exists
                if (User::where('email', $invitation['email'])->exists()) {
                    $skippedCount++;
                    $errors[] = "{$invitation['email']} is already a team member.";

                    continue;
                }

                // Skip if invitation already pending
                if (TenantInvitation::where('email', $invitation['email'])
                    ->where('status', 'pending')
                    ->exists()) {
                    $skippedCount++;
                    $errors[] = "{$invitation['email']} was already invited.";

                    continue;
                }

                try {
                    // Create invitation
                    TenantInvitation::create([
                        'email' => $invitation['email'],
                        'role' => $invitation['role'],
                        'invited_by' => Auth::id(),
                        'status' => 'pending',
                        'metadata' => [
                            'source' => 'onboarding',
                        ],
                    ]);

                    $sentCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to invite {$invitation['email']}: {$e->getMessage()}";
                    Log::error('Onboarding team invite failed', [
                        'email' => $invitation['email'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->markStepCompleted('team');

        // Build user-friendly message
        if ($sentCount > 0 && $skippedCount > 0) {
            $message = "{$sentCount} invitation(s) sent, {$skippedCount} skipped.";
        } elseif ($sentCount > 0) {
            $message = "{$sentCount} invitation(s) sent successfully!";
        } elseif ($skippedCount > 0) {
            $message = "No new invitations sent. {$skippedCount} already exist.";
        } else {
            $message = 'Team invitations processed.';
        }

        if (! empty($errors)) {
            session()->flash('invitation_errors', $errors);
        }

        return back()->with('success', $message);
    }

    /**
     * Save modules configuration step.
     */
    public function saveModules(Request $request)
    {
        $validated = $request->validate([
            'enabled_modules' => 'nullable|array',
            'enabled_modules.*' => 'string',
        ]);

        // Store module preferences in tenant data
        $data = $this->getTenantData();
        $data['module_preferences'] = $validated['enabled_modules'] ?? [];
        $this->updateTenantData($data);

        $this->markStepCompleted('modules');

        return back()->with('success', 'Module configuration saved.');
    }

    /**
     * Complete the onboarding process.
     */
    public function complete(Request $request)
    {
        $data = $this->getTenantData();
        $data['onboarding'] = [
            'completed' => true,
            'completed_at' => now()->toIso8601String(),
            'completed_by' => Auth::id(),
            'completed_steps' => array_keys($this->steps),
        ];
        $this->updateTenantData($data);

        return redirect()->route('dashboard')->with('success', 'Welcome! Your organization is all set up.');
    }

    /**
     * Skip the onboarding process entirely.
     */
    public function skip(Request $request)
    {
        $data = $this->getTenantData();
        $data['onboarding'] = [
            'completed' => true,
            'completed_at' => now()->toIso8601String(),
            'completed_by' => Auth::id(),
            'skipped' => true,
        ];
        $this->updateTenantData($data);

        return redirect()->route('dashboard')->with('info', 'You can complete the setup later in Settings.');
    }

    /**
     * Update the current step.
     */
    public function updateStep(Request $request)
    {
        $validated = $request->validate([
            'step' => 'required|string|in:'.implode(',', array_keys($this->steps)),
        ]);

        $data = $this->getTenantData();
        $onboarding = $data['onboarding'] ?? [];
        $onboarding['current_step'] = $validated['step'];
        $data['onboarding'] = $onboarding;
        $this->updateTenantData($data);

        return back();
    }

    /**
     * Check if onboarding is completed.
     */
    public static function isOnboardingCompleted(): bool
    {
        if (! tenant()) {
            return true;
        }

        // Read directly from database to avoid cached data issues
        $tenantData = DB::connection(config('tenancy.database.central_connection', 'mysql'))
            ->table('tenants')
            ->where('id', tenant()->id)
            ->value('data');

        $data = json_decode($tenantData, true) ?? [];

        return ($data['onboarding']['completed'] ?? false) === true;
    }

    /**
     * Mark a step as completed.
     */
    protected function markStepCompleted(string $step): void
    {
        $data = $this->getTenantData();
        $onboarding = $data['onboarding'] ?? [];

        $completedSteps = $onboarding['completed_steps'] ?? [];
        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        // Find the next step
        $stepOrder = array_keys($this->steps);
        $currentIndex = array_search($step, $stepOrder);
        $nextStep = $stepOrder[$currentIndex + 1] ?? 'complete';

        $onboarding['completed_steps'] = $completedSteps;
        $onboarding['current_step'] = $nextStep;
        $data['onboarding'] = $onboarding;

        $this->updateTenantData($data);
    }

    /**
     * Get tenant data as array from central database.
     */
    protected function getTenantData(): array
    {
        $tenant = tenant();
        $tenantData = DB::connection(config('tenancy.database.central_connection', 'mysql'))
            ->table('tenants')
            ->where('id', $tenant->id)
            ->value('data');

        return json_decode($tenantData, true) ?? [];
    }

    /**
     * Update tenant data in central database.
     */
    protected function updateTenantData(array $data): void
    {
        $tenant = tenant();
        DB::connection(config('tenancy.database.central_connection', 'mysql'))
            ->table('tenants')
            ->where('id', $tenant->id)
            ->update(['data' => json_encode($data)]);
    }

    /**
     * Get available product modules from module_pricing table.
     * Enriches with metadata from ModuleDiscoveryService.
     * Foundation packages are excluded because they have no pricing entry.
     *
     * @return array<int, array{code: string, name: string, description: string, icon: string}>
     */
    protected function getAvailableModules(): array
    {
        $pricingCodes = DB::connection(config('tenancy.database.central_connection', 'mysql'))
            ->table('module_pricing')
            ->where('is_active', true)
            ->pluck('module_code');

        $allDefinitions = $this->moduleDiscovery->getModuleDefinitions()->keyBy('code');

        return $pricingCodes
            ->map(fn (string $code) => $allDefinitions->get($code))
            ->filter()
            ->map(function ($module) {
                return [
                    'code' => $module['code'] ?? '',
                    'name' => $module['name'] ?? ucfirst($module['code'] ?? ''),
                    'description' => $module['description'] ?? '',
                    'icon' => $module['icon'] ?? 'CubeIcon',
                    'category' => $module['category'] ?? 'general',
                    'is_core' => $module['is_core'] ?? false,
                ];
            })
            ->values()
            ->toArray();
    }
}
