<?php

namespace Aero\Core\Services;

use Aero\Contracts\NavigationRegistryInterface;
use Aero\Core\Models\User;
use Aero\Core\Support\TenantCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Navigation Registry Service
 *
 * Central registry for module navigation items.
 * Modules register their navigation, and core aggregates them.
 *
 * Dashboard Navigation:
 * - Dynamically builds dashboard menu from DashboardRegistry
 * - Single dashboard: Shows as "Dashboard" (no dropdown)
 * - Multiple dashboards: Shows as "Dashboards" with children
 *
 * Usage:
 *   $registry = app(NavigationRegistry::class);
 *   $registry->register('hrm', [...navigation items...]);
 *   $allNav = $registry->all();
 */
class NavigationRegistry implements NavigationRegistryInterface
{
    /**
     * Registered navigation items by module.
     *
     * @var array<string, array>
     */
    protected array $navigationItems = [];

    /**
     * Registered self-service navigation items by module.
     * These are employee-facing "My *" pages aggregated under a single menu.
     *
     * @var array<string, array>
     */
    protected array $selfServiceItems = [];

    /**
     * IA section catalogs by scope. Each package publishes its own sections
     * (label/icon/order) via registerSections(); core never hardcodes them.
     *
     * @var array<string, array<int, array{key:string,label:string,icon:?string,order:int}>>
     */
    protected array $sectionCatalog = [];

    /**
     * Cached set of registered GET route URIs (normalised with a leading slash),
     * used to prune nav links whose route does not resolve. Null until built.
     *
     * @var string[]|null
     */
    protected ?array $registeredGetPathsCache = null;

    /**
     * Cache key prefix.
     */
    protected const CACHE_KEY = 'aero.navigation';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Register navigation items for a module.
     *
     * @param  string  $moduleCode  Module identifier
     * @param  array  $items  Navigation items array
     * @param  int  $priority  Module priority for ordering
     * @param  string  $scope  Module scope: 'platform' for admin, 'tenant' for tenant users
     */
    public function register(string $moduleCode, array $items, int $priority = 100, string $scope = 'tenant'): void
    {
        $this->navigationItems[$moduleCode] = [
            'module' => $moduleCode,
            'priority' => $priority,
            'scope' => $scope,
            'items' => $items,
        ];

        // Clear cache when navigation changes
        $this->clearCache();
    }

    /**
     * Register self-service navigation items for a module.
     *
     * Self-service items are employee-facing pages like "My Attendance", "My Leaves", etc.
     * They are aggregated under a single "My Workspace" menu item.
     *
     * @param  string  $moduleCode  Module identifier
     * @param  array  $items  Self-service navigation items
     * @param  int  $priority  Priority within self-service menu
     */
    public function registerSelfService(string $moduleCode, array $items, int $priority = 100): void
    {
        $this->selfServiceItems[$moduleCode] = [
            'module' => $moduleCode,
            'priority' => $priority,
            'items' => $items,
        ];

        // Clear cache when navigation changes
        $this->clearCache();
    }

    /**
     * Publish a package's IA section catalog. Sections are merged across all
     * packages for a scope; a later registration for the same key overrides.
     *
     * @param  string  $scope  'platform' | 'tenant'
     * @param  array<int, array{key:string,label:string,icon?:string,order?:int}>  $sections
     */
    public function registerSections(string $scope, array $sections): void
    {
        foreach ($sections as $s) {
            if (empty($s['key'])) {
                continue;
            }
            $this->sectionCatalog[$scope][$s['key']] = [
                'key' => $s['key'],
                'label' => $s['label'] ?? ucfirst((string) $s['key']),
                'icon' => $s['icon'] ?? null,
                'order' => (int) ($s['order'] ?? 500),
            ];
        }
    }

    /**
     * The ordered IA section catalog for a scope (for the frontend to render
     * section headers/order without hardcoding). Product sections are appended
     * dynamically by toFrontend/toFrontendGroups.
     *
     * @return array<int, array{key:string,label:string,icon:?string,order:int}>
     */
    public function getSectionCatalog(?string $scope = null): array
    {
        $sections = array_values($this->sectionCatalog[$scope] ?? []);
        usort($sections, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $sections;
    }

    /**
     * Get aggregated self-service navigation.
     *
     * Returns a single "My Workspace" menu item containing all self-service
     * items from all modules, sorted by priority.
     *
     * @return array|null Self-service navigation item or null if no items
     */
    public function getSelfServiceNavigation(): ?array
    {
        if (empty($this->selfServiceItems)) {
            return null;
        }

        // Aggregate all self-service items from all modules
        $allItems = collect($this->selfServiceItems)
            ->sortBy('priority')
            ->flatMap(function ($moduleData) {
                return collect($moduleData['items'])->map(function ($item) use ($moduleData) {
                    // Add module context to each item
                    return array_merge($item, [
                        'module' => $moduleData['module'],
                    ]);
                });
            })
            ->sortBy('priority')
            ->values()
            ->toArray();

        if (empty($allItems)) {
            return null;
        }

        return [
            'name' => 'My Workspace',
            'icon' => 'UserIcon',
            'priority' => 2,
            'access' => 'self-service',
            'children' => $allItems,
        ];
    }

    /**
     * Get self-service items for a specific module.
     */
    public function getSelfServiceForModule(string $moduleCode): array
    {
        return $this->selfServiceItems[$moduleCode]['items'] ?? [];
    }

    /**
     * Check if a module has registered self-service items.
     */
    public function hasSelfService(string $moduleCode): bool
    {
        return isset($this->selfServiceItems[$moduleCode]) && ! empty($this->selfServiceItems[$moduleCode]['items']);
    }

    /**
     * Get all navigation items sorted by priority.
     */
    public function all(): array
    {
        return collect($this->navigationItems)
            ->sortBy('priority')
            ->pluck('items')
            ->flatten(1)
            ->values()
            ->toArray();
    }

    /**
     * Get navigation items for a specific module.
     */
    public function forModule(string $moduleCode): array
    {
        return $this->navigationItems[$moduleCode]['items'] ?? [];
    }

    /**
     * Check if a module has registered navigation.
     */
    public function hasModule(string $moduleCode): bool
    {
        return isset($this->navigationItems[$moduleCode]);
    }

    /**
     * Get registered module codes.
     */
    public function getModuleCodes(): array
    {
        return array_keys($this->navigationItems);
    }

    /**
     * Get navigation items ready for frontend.
     *
     * Dashboard navigation is dynamically built from DashboardRegistry:
     * - Single dashboard: Shows as "Dashboard" (no children)
     * - Multiple dashboards: Shows as "Dashboards" parent with children
     *
     * Core/Platform modules: submodules are promoted to top level (Users, Roles, Settings)
     * Other modules: wrapped under module name (Human Resources → Employees, Attendance, etc.)
     *
     * @param  string|null  $scope  Filter by scope: 'platform' for admin, 'tenant' for tenant users, null for all
     * @param  User|null  $user  Optional user to filter dashboards by permissions
     * @param  array|null  $subscribedModules  Array of subscribed module codes (e.g., ['core', 'hrm']). Null = no filtering.
     */
    public function toFrontend(?string $scope = null, $user = null, ?array $subscribedModules = null): array
    {
        $navigationItems = [];

        // 1. Add dynamic dashboard navigation first (priority 1)
        $dashboardNav = $this->getDashboardNavigation($user, $scope);
        if ($dashboardNav) {
            // Filter dashboard children by subscription
            if ($subscribedModules !== null && ! empty($dashboardNav['children'])) {
                $dashboardNav['children'] = array_values(array_filter(
                    $dashboardNav['children'],
                    fn ($child) => ! isset($child['module']) || in_array($child['module'], array_merge(['core', 'platform'], $subscribedModules), true)
                ));
            }

            // Group dashboards: if multiple dashboards, add as a parent section
            if (! empty($dashboardNav['children']) && count($dashboardNav['children']) > 1) {
                $dashboardNav['section'] = 'dashboards';
                $navigationItems[] = $dashboardNav;
            } elseif (! empty($dashboardNav['children'])) {
                // Single dashboard - pull the first child up and add as is with section
                $singleDash = $dashboardNav['children'][0];
                $singleDash['section'] = 'dashboards';
                $navigationItems[] = $singleDash;
            } else {
                // No children? Just add the parent
                $dashboardNav['section'] = 'dashboards';
                $navigationItems[] = $dashboardNav;
            }
        }

        // 2. Add self-service navigation (priority 2) - "My Workspace" menu.
        // Self-service ("My *") pages are an employee/tenant concept; they must
        // never appear in the platform (landlord) admin nav.
        $selfServiceNav = $scope === 'platform' ? null : $this->getSelfServiceNavigation();
        if ($selfServiceNav) {
            // Filter self-service children by subscription
            if ($subscribedModules !== null && ! empty($selfServiceNav['children'])) {
                $selfServiceNav['children'] = array_values(array_filter(
                    $selfServiceNav['children'],
                    fn ($child) => ! isset($child['module']) || in_array($child['module'], array_merge(['core', 'platform'], $subscribedModules), true)
                ));
            }

            // Group self-service items into a single parent folder
            if (! empty($selfServiceNav['children'])) {
                $selfServiceNav['section'] = 'my-workspace';
                $navigationItems[] = $selfServiceNav;
            }
        }

        $sortedModules = collect($this->navigationItems)->sortBy('priority');

        // Count non-core, non-platform modules to determine if single module or multiple
        $nonCoreModules = [];
        foreach ($sortedModules as $moduleCode => $moduleData) {
            // Filter by scope if specified
            $moduleScope = $moduleData['scope'] ?? 'tenant';
            if ($scope !== null && $moduleScope !== 'all' && $moduleScope !== $scope) {
                continue;
            }

            // Filter by subscription: core/platform always allowed, others must be subscribed
            if ($subscribedModules !== null && ! in_array($moduleCode, ['core', 'platform', 'auth', 'hrmac'], true)) {
                if (! in_array($moduleCode, $subscribedModules, true)) {
                    continue;
                }
            }

            // Track non-core modules (auth + hrmac are shared infrastructure, treated
            // like core/platform — always shown, flattened, never subscription-gated).
            if (! in_array($moduleCode, ['core', 'platform', 'auth', 'hrmac'], true)) {
                $nonCoreModules[] = $moduleCode;
            }
        }

        $isSingleModule = count($nonCoreModules) === 1;
        $singleModuleCode = $isSingleModule ? $nonCoreModules[0] : null;

        foreach ($sortedModules as $moduleCode => $moduleData) {
            // Filter by scope if specified
            $moduleScope = $moduleData['scope'] ?? 'tenant';
            if ($scope !== null && $moduleScope !== 'all' && $moduleScope !== $scope) {
                continue;
            }

            // Filter by subscription: core/platform always allowed, others must be subscribed
            if ($subscribedModules !== null && ! in_array($moduleCode, ['core', 'platform', 'auth', 'hrmac'], true)) {
                if (! in_array($moduleCode, $subscribedModules, true)) {
                    continue;
                }
            }

            foreach ($moduleData['items'] as $item) {
                // Core/Platform/Auth/HRMAC (infrastructure) modules: flatten children
                // (submodules) to top level BUT skip Dashboard and Self-Service submodules.
                if (in_array($moduleCode, ['core', 'platform', 'auth', 'hrmac'], true)) {
                    if (! empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            // Skip dashboard items - they come from DashboardRegistry
                            if ($this->isDashboardItem($child)) {
                                continue;
                            }
                            // Skip self-service items - they appear under My Workspace
                            if ($this->isSelfServiceItem($child)) {
                                continue;
                            }
                            // Section comes from the package's own config
                            // (nav_section); 'administration' is the safe default.
                            $child['section'] = $child['nav_section'] ?? 'administration';
                            $navigationItems[] = $child;
                        }
                    } else {
                        // Skip dashboard items
                        if ($this->isDashboardItem($item)) {
                            continue;
                        }
                        // Skip self-service items
                        if ($this->isSelfServiceItem($item)) {
                            continue;
                        }
                        $item['section'] = $item['nav_section'] ?? 'administration';
                        $navigationItems[] = $item;
                    }
                } else {
                    // Products: each product IS a section header (its moduleCode).
                    // Flatten the product's features under that header — whether the
                    // tenant has one product or several — so every product reads as
                    // its own titled group. A submodule may override via nav_section
                    // (used for the product's own semantic sub-groups).
                    if (! empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            $child['section'] = $child['nav_section'] ?? $moduleCode;
                            $navigationItems[] = $child;
                        }
                    } else {
                        $item['section'] = $item['nav_section'] ?? $moduleCode;
                        $navigationItems[] = $item;
                    }
                }
            }
        }

        // Drop dead links: embedded features (no standalone page) and any leaf
        // whose path maps to no registered GET route — e.g. modules delegated to
        // packages that aren't installed in this deployment. Items reappear
        // automatically once their route is registered.
        $navigationItems = $this->pruneUnnavigable($navigationItems);

        // Collapse any group that, after pruning, leads to a single real page into
        // a direct link — a parent that expands to reveal one child pointing at the
        // same destination is redundant (e.g. Tenants → All Tenants, Plans → All
        // Plans). Matches the "single product flat" rule; multi-page groups are
        // untouched.
        $navigationItems = $this->collapseSingleChildGroups($navigationItems);

        // Collapse duplicate top-level entries. A config authoring slip (the
        // same submodule declared twice — e.g. two "Security Center" or two
        // "Access Logs" blocks) would otherwise render the group twice in the
        // sidebar. Key on the stable access code, falling back to path/name, and
        // keep the first occurrence. No-op when there are no duplicates.
        $navigationItems = $this->dedupeTopLevel($navigationItems);

        // Sort by priority
        usort($navigationItems, fn ($a, $b) => ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999));

        return $navigationItems;
    }

    /**
     * Section keys for the 8-part platform-admin IA, keyed by the first segment
     * of a module's path. Order/titles live in {@see toFrontendGroups()} and the
     * frontend nav adapter. Modules not listed keep their existing section.
     */
    /**
     * Remove duplicate top-level navigation entries, keyed by access code
     * (falling back to path, then name). Section dividers / spacers and items
     * with no identifying key are always kept. Children are left untouched —
     * duplication only occurs at the module level.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function dedupeTopLevel(array $items): array
    {
        $seen = [];
        $kept = [];

        foreach ($items as $item) {
            $key = $item['access'] ?? $item['path'] ?? $item['name'] ?? null;

            if ($key === null || $key === '') {
                $kept[] = $item;

                continue;
            }

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $kept[] = $item;
        }

        return array_values($kept);
    }

    /**
     * Collapse groups that resolve to a single navigable child into a direct
     * link. The group keeps its own identity (name / icon / section / priority)
     * but adopts the child's destination and drops the now-redundant sublevel, so
     * a one-page module reads as "Tenants" rather than "Tenants → All Tenants".
     *
     * The special aggregate folders (Dashboards, My Workspace) are preserved even
     * when momentarily single — they are intentional containers, not modules.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function collapseSingleChildGroups(array $items): array
    {
        foreach ($items as $i => $item) {
            if (empty($item['children']) || ! is_array($item['children'])) {
                continue;
            }

            $item['children'] = $this->collapseSingleChildGroups($item['children']);

            $section = $item['section'] ?? '';
            if (count($item['children']) === 1 && ! in_array($section, ['dashboards', 'my-workspace'], true)) {
                $only = $item['children'][0];
                $item['path'] = $only['path'] ?? ($item['path'] ?? null);
                $item['access'] = $item['access'] ?? ($only['access'] ?? null);
                unset($item['children']);
            }

            $items[$i] = $item;
        }

        return array_values($items);
    }

    /**
     * Recursively drop navigation items that don't lead to a real page:
     *  - type === 'feature' with no children (embedded, no standalone route)
     *  - a leaf whose non-empty path matches no registered GET route
     * A parent is kept when it still has resolvable children even if its own
     * path is a non-navigable group anchor.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function pruneUnnavigable(array $items): array
    {
        $kept = [];

        foreach ($items as $item) {
            if (! empty($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->pruneUnnavigable($item['children']);
            }

            $hasChildren = ! empty($item['children']);

            if ($hasChildren) {
                // Group anchor: if its own path is a dead link (e.g. the group has
                // no index route, only sub-pages), retarget it to the first
                // surviving child so the label never 404s.
                if (! $this->pathResolves($item['path'] ?? null)) {
                    $item['path'] = $item['children'][0]['path'] ?? ($item['path'] ?? null);
                }

                $kept[] = $item;

                continue;
            }

            // Embedded features are surfaced inline on record pages, not as menu pages.
            if (($item['type'] ?? 'page') === 'feature') {
                continue;
            }

            // Detail / parameterised pages (e.g. /plans/{id}) are opened from their
            // list, never reached from the nav. Drop them as nav leaves — their
            // HRMAC actions live in the module hierarchy, not here, so access
            // control is unaffected.
            if (str_contains((string) ($item['path'] ?? ''), '{')) {
                continue;
            }

            // Leaf with an unresolvable path → dead link.
            if (! $this->pathResolves($item['path'] ?? null)) {
                continue;
            }

            $kept[] = $item;
        }

        return array_values($kept);
    }

    /**
     * Whether a navigation path maps to a registered GET route. External URLs
     * and parameterised paths can't be verified statically and are kept.
     */
    protected function pathResolves(?string $path): bool
    {
        if ($path === null || $path === '' || $path === '#') {
            return false;
        }

        if (preg_match('#^https?://#i', $path)) {
            return true;
        }

        $normalized = '/'.ltrim((string) strtok($path, '?'), '/');

        if (str_contains($normalized, '{')) {
            return true;
        }

        return in_array($normalized, $this->registeredGetPaths(), true);
    }

    /**
     * Cached set of registered GET route URIs, normalised with a leading slash.
     *
     * @return string[]
     */
    protected function registeredGetPaths(): array
    {
        if ($this->registeredGetPathsCache !== null) {
            return $this->registeredGetPathsCache;
        }

        $paths = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $paths[] = '/'.ltrim($route->uri(), '/');
        }

        return $this->registeredGetPathsCache = array_values(array_unique($paths));
    }

    /**
     * Get navigation items grouped by section for shell-aware rendering.
     *
     * Returns an array of groups where each group has a title and items array.
     * Suitable for CommandShell and other grouped navigation UIs.
     *
     * @param  string|null  $scope  Filter by scope: 'platform' for admin, 'tenant' for tenant users
     * @param  User|null  $user  Optional user to filter dashboards by permissions
     * @param  array|null  $subscribedModules  Array of subscribed module codes
     * @return array{title: string, items: array}[]
     */
    public function toFrontendGroups(?string $scope = null, $user = null, ?array $subscribedModules = null): array
    {
        $flat = $this->toFrontend($scope, $user, $subscribedModules);

        // Titles + order come from the aggregated (package-owned) catalog. Special
        // sections framing the catalog: dashboards/my-workspace on top; product
        // (moduleCode) sections and the administration/modules fallbacks after the
        // core catalog. Product sections that declared a catalog entry use it.
        $titles = ['dashboards' => 'Dashboards', 'my-workspace' => 'My Workspace', 'administration' => 'Administration', 'modules' => 'Modules'];
        $order = ['dashboards' => -20, 'my-workspace' => -10, 'administration' => 9000, 'modules' => 9100];
        foreach ($this->getSectionCatalog($scope) as $s) {
            $titles[$s['key']] = $s['label'];
            $order[$s['key']] = $s['order'];
        }

        $grouped = collect($flat)->groupBy(fn ($item) => $item['section'] ?? 'others');

        // Unknown keys (product moduleCode sections with no declared catalog entry)
        // sort just after the core catalog (5000+), alphabetically, so products
        // always render below core.
        $sortedKeys = $grouped->keys()->sortBy(fn ($key) => $order[$key] ?? (5000 + ord($key[0] ?? 'z')))->values();

        return $sortedKeys->map(function ($section) use ($grouped, $titles) {
            $items = $grouped->get($section);
            if (empty($items)) {
                return null;
            }

            return [
                'key'   => $section,
                'title' => $titles[$section] ?? ucfirst(str_replace(['-', '_'], ' ', $section)),
                'items' => $items->values()->toArray(),
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Check if a navigation item is a dashboard item.
     *
     * Dashboard items should be excluded from regular navigation
     * as they are dynamically built from DashboardRegistry.
     */
    protected function isDashboardItem(array $item): bool
    {
        $name = strtolower($item['name'] ?? '');
        $path = strtolower($item['path'] ?? '');
        $access = strtolower($item['access'] ?? '');

        // Check if it's a dashboard item by name, path, or access code
        if (str_contains($name, 'dashboard')) {
            return true;
        }

        // Check if path is exactly /dashboard or ends with /dashboard
        if ($path === '/dashboard' || preg_match('#/dashboard$#', $path)) {
            return true;
        }

        // Check access code for dashboard
        if (str_contains($access, '.dashboard') || $access === 'core.dashboard') {
            return true;
        }

        return false;
    }

    /**
     * Check if a navigation item is a self-service item.
     *
     * Self-service items are excluded from regular navigation
     * as they are aggregated under "My Workspace" via getSelfServiceNavigation().
     */
    protected function isSelfServiceItem(array $item): bool
    {
        $access = strtolower($item['access'] ?? '');

        // Match access codes like core.self_service, hrm.employee-self-service
        return str_contains($access, 'self_service') || str_contains($access, 'self-service');
    }

    /**
     * Get dynamic dashboard navigation from DashboardRegistry.
     *
     * - If user has access to only 1 dashboard: Returns single "Dashboard" item
     * - If user has access to 2+ dashboards: Returns "Dashboards" parent with children
     *
     * @param  User|null  $user  User to filter by permissions
     * @param  string|null  $scope  Nav scope: 'platform' (landlord admin) or 'tenant'. In
     *                              the platform scope only platform/core dashboards may
     *                              surface — tenant module dashboards (HRM, Employee,
     *                              Finance, …) must never appear in the landlord nav.
     * @return array|null Navigation item or null if no dashboards available
     */
    public function getDashboardNavigation($user = null, ?string $scope = null): ?array
    {
        // Check if DashboardRegistry is available
        if (! app()->bound(DashboardRegistry::class)) {
            // Fallback to a simple dashboard link if registry not available
            return [
                'name' => 'Dashboard',
                'path' => '/dashboard',
                'icon' => 'HomeIcon',
                'priority' => 1,
                'children' => [],
            ];
        }

        $dashboardRegistry = app(DashboardRegistry::class);
        $user = $user ?? Auth::user();

        // Get all available dashboards filtered by user permissions
        $availableDashboards = $dashboardRegistry->getDashboardOptions($user);

        // Scope guard: the platform (landlord) admin is not a tenant and never
        // subscribes to tenant products, so its nav must only surface
        // platform/core dashboards. Without this a platform super-admin — who
        // bypasses every permission check — sees HRM, Employee and every other
        // tenant module dashboard leak into the landlord sidebar.
        if ($scope === 'platform') {
            $availableDashboards = array_filter(
                $availableDashboards,
                fn ($dashboard) => in_array($dashboard['module'] ?? '', ['core', 'platform'], true)
            );
        }

        // Filter to only include dashboards with valid routes
        $validDashboards = array_filter($availableDashboards, function ($dashboard) {
            return Route::has($dashboard['key']);
        });

        if (empty($validDashboards)) {
            // No valid dashboards - show default
            return [
                'name' => 'Dashboard',
                'path' => '/dashboard',
                'icon' => 'HomeIcon',
                'priority' => 1,
                'children' => [],
            ];
        }

        $dashboardCount = count($validDashboards);

        if ($dashboardCount === 1) {
            // Single dashboard - show as "Dashboard" without children
            $dashboard = reset($validDashboards);

            return [
                'name' => 'Dashboard',
                'path' => $this->getRouteUrl($dashboard['key']),
                'icon' => 'HomeIcon',
                'priority' => 1,
                'access' => $dashboard['key'],
                'children' => [],
            ];
        }

        // Multiple dashboards - show as "Dashboards" with children
        $children = [];
        foreach ($validDashboards as $dashboard) {
            $children[] = [
                'name' => $dashboard['label'],
                'path' => $this->getRouteUrl($dashboard['key']),
                'icon' => $this->getDashboardIcon($dashboard['module']),
                'access' => $dashboard['key'],
                'module' => $dashboard['module'],
            ];
        }

        return [
            'name' => 'Dashboards',
            'path' => '/dashboard',
            'icon' => 'HomeIcon',
            'priority' => 1,
            'children' => $children,
        ];
    }

    /**
     * Get URL for a route name.
     */
    protected function getRouteUrl(string $routeName): string
    {
        try {
            return route($routeName, [], false); // Get relative URL
        } catch (\Exception $e) {
            return '/dashboard'; // Fallback
        }
    }

    /**
     * Get icon for a dashboard based on module.
     */
    protected function getDashboardIcon(string $module): string
    {
        return match ($module) {
            'core' => 'HomeIcon',
            'hrm' => 'UserGroupIcon',
            'finance' => 'BanknotesIcon',
            'project' => 'ClipboardDocumentListIcon',
            'dms' => 'DocumentDuplicateIcon',
            'quality' => 'BeakerIcon',
            'rfi' => 'ClipboardDocumentCheckIcon',
            'compliance' => 'ShieldCheckIcon',
            'crm' => 'UsersIcon',
            'ims' => 'CubeIcon',
            'pos' => 'ShoppingCartIcon',
            'scm' => 'TruckIcon',
            default => 'Squares2X2Icon',
        };
    }

    /**
     * Clear navigation cache.
     */
    public function clearCache(): void
    {
        try {
            TenantCache::forget(self::CACHE_KEY);
            TenantCache::forget(self::CACHE_KEY.'.frontend');
        } catch (\Throwable $e) {
            // Cache not available (e.g., outside Laravel context)
        }
    }

    /**
     * Get cached navigation for frontend.
     *
     * Note: Dashboard navigation is user-specific (based on permissions),
     * so we cache per-user using their ID as part of the cache key.
     *
     * @param  User|null  $user  User for permission-based filtering
     */
    public function getCachedFrontend($user = null): array
    {
        $user = $user ?? Auth::user();
        $userId = $user?->id ?? 'guest';
        $cacheKey = self::CACHE_KEY.'.frontend.'.$userId;

        try {
            return TenantCache::remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => $this->toFrontend(null, $user)
            );
        } catch (\Throwable $e) {
            // Cache not available, return without caching
            return $this->toFrontend(null, $user);
        }
    }

    /**
     * Unregister a module's navigation (for testing).
     */
    public function unregister(string $moduleCode): void
    {
        unset($this->navigationItems[$moduleCode]);
        unset($this->selfServiceItems[$moduleCode]);
        $this->clearCache();
    }

    /**
     * Clear all registrations (for testing).
     */
    public function clear(): void
    {
        $this->navigationItems = [];
        $this->selfServiceItems = [];
        $this->clearCache();
    }

    /**
     * Get user-specific navigation metadata (usage stats, preferences) for the frontend.
     *
     * Returns the lightweight metadata payload injected into every Inertia page
     * so the sidebar can show usage badges and quick-access items without a
     * separate API call.
     *
     * @param  User  $user
     * @return array<string, mixed>
     */
    public function getUserNavigationMetadata($user): array
    {
        try {
            /** @var AINavigationSuggestionService $service */
            $service = app(AINavigationSuggestionService::class);

            return $service->getUserMetadata($user->id);
        } catch (\Throwable $e) {
            return [
                'topPaths' => [],
                'recentPaths' => [],
                'quickActions' => [],
                'pinnedItems' => [],
                'hiddenItems' => [],
                'compactMode' => false,
                'showLabels' => true,
            ];
        }
    }

    /**
     * Get context-aware navigation suggestions for the current page.
     *
     * @param  User  $user
     * @return array{pinned: array, frequent: array, recent: array, contextual: array}
     */
    public function getContextAwareSuggestions($user, ?string $currentPath = null): array
    {
        try {
            /** @var AINavigationSuggestionService $service */
            $service = app(AINavigationSuggestionService::class);
            $allNav = $this->toFrontend(null, $user);

            return $service->getSuggestions($user->id, $currentPath, $allNav);
        } catch (\Throwable $e) {
            return ['pinned' => [], 'frequent' => [], 'recent' => [], 'contextual' => []];
        }
    }
}
