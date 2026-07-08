<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Module;
use Aero\Platform\Services\ModuleAdminService;
use Aero\Platform\Services\ModuleRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Modules (registry) controller — the TECHNICAL module surface.
 *
 * Presents the shipped module registry: HRMAC hierarchy depth (sub-modules /
 * components / actions), core-vs-sellable, dependencies and sync health. Pricing
 * and productisation live on the Products (Catalog) page, not here.
 *
 * ARCH NOTE: Toggling a module inactive is a registry flag only; it does NOT
 * cancel existing tenant ProductSubscription rows (handled by Subscriptions).
 */
class ModuleAdminController extends Controller
{
    public function __construct(
        private ModuleAdminService $svc,
        private ModuleRegistryService $registry,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Modules/Index', $this->registry->overview());
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
}
