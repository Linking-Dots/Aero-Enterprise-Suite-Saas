<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Module;
use Aero\Platform\Services\ModuleAdminService;
use Aero\Platform\Services\ModuleRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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

    /**
     * Re-run the module-registry sync (aero:sync-module) so the HRMAC hierarchy
     * matches the packages' config/module.php after a deploy or config change.
     * Synchronous — the sync completes in ~2s and the admin expects a fresh page.
     */
    public function resync(): RedirectResponse
    {
        try {
            $exit = Artisan::call('aero:sync-module', ['--scope' => 'platform']);
        } catch (Throwable $e) {
            return back()->with('error', 'Registry sync failed: '.$e->getMessage());
        }

        return $exit === 0
            ? back()->with('success', 'Module registry re-synced.')
            : back()->with('error', "Registry sync returned exit code {$exit}.");
    }
}
