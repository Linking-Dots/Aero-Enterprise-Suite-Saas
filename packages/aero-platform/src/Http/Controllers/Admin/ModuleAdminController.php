<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Module;
use Aero\Platform\Services\ModuleAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P-4 Module Admin Controller
 *
 * Manages the platform-wide product catalog (which modules are sellable,
 * their default config, and pricing).
 *
 * ARCH NOTE: This controller manages catalog availability only.
 * Toggling a module inactive does NOT cancel existing tenant ProductSubscription
 * rows — that is handled by the Subscriptions admin (P-2).
 *
 * Route prefix: /admin/p4/modules
 * Route names:  platform.admin.p4modules.*
 */
class ModuleAdminController extends Controller
{
    public function __construct(private ModuleAdminService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Modules/Index', [
            'modules' => $this->svc->list(),
        ]);
    }

    public function toggle(Module $module): RedirectResponse
    {
        $this->svc->toggleActive($module);

        return back()->with('success', 'Module toggled.');
    }

    public function configure(Request $request, Module $module): RedirectResponse
    {
        $data = $request->validate([
            'config' => ['required', 'array'],
        ]);

        $this->svc->configure($module, $data['config']);

        return back()->with('success', 'Module configured.');
    }

    public function updatePricing(Request $request, Module $module): RedirectResponse
    {
        $data = $request->validate([
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['required', 'numeric', 'min:0'],
        ]);

        $this->svc->updatePricing($module, (float) $data['price_monthly'], (float) $data['price_annual']);

        return back()->with('success', 'Pricing updated.');
    }
}
