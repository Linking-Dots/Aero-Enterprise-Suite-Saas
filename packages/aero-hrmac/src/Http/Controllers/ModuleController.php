<?php

namespace Aero\HRMAC\Http\Controllers;

use Aero\Contracts\AuditServiceInterface;
use Aero\Contracts\PlanCatalogInterface;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\Role;
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\HRMAC\Models\SubModule;
use Aero\Contracts\RoleModuleAccessInterface;
use Aero\Kernel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

/**
 * Admin Module Controller
 *
 * Handles module management and role-module access assignment.
 * Implements comprehensive module and permission management.
 */
class ModuleController extends Controller
{
    private RoleModuleAccessInterface $roleModuleAccessService;

    public function __construct(RoleModuleAccessInterface $roleModuleAccessService)
    {
        $this->roleModuleAccessService = $roleModuleAccessService;
    }

    /**
     * Get the current authenticated user
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * Determine the Inertia page path
     */
    protected function getViewPath(): string
    {
        return 'Core/Modules/Index';
    }

    /**
     * Check if user is a super administrator
     */
    protected function isSuperAdmin(): bool
    {
        $user = $this->getCurrentUser();

        if (! $user || ! method_exists($user, 'roles')) {
            return false;
        }

        // Context-free: honor every configured super-admin role name (tenant AND
        // platform — e.g. "Super Administrator", "Super Platform Admin"), not a
        // single hardcoded tenant role. Mirrors RoleController::isSuperAdmin.
        $rolesConfig = config('hrmac.super_admin_roles', []);
        $superRoles = [];
        array_walk_recursive($rolesConfig, function ($role) use (&$superRoles) {
            if (is_string($role) && $role !== '') {
                $superRoles[] = $role;
            }
        });

        return $user->roles()->whereIn('name', $superRoles)->exists();
    }

    /**
     * Display the module management interface
     */
    public function index()
    {
        $user = $this->getCurrentUser();
        $isSuperAdmin = $this->isSuperAdmin();

        // Load modules from database (tenant scope only for Core package)
        $moduleScope = 'tenant';

        // SECURITY FIX: Filter modules by user's role access
        if ($isSuperAdmin) {
            // Super admins see all modules
            $modules = $this->getModulesFromDatabase($moduleScope);
        } else {
            // Regular users only see modules they have access to
            $modules = $this->getModulesFromDatabaseFiltered($moduleScope, $user);
        }

        // Get roles (web guard only)
        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name', 'is_protected']);

        $statistics = $this->getDatabaseStatistics($moduleScope);

        // Get access scopes for dropdown
        $accessScopes = RoleModuleAccess::ACCESS_SCOPES;

        return Inertia::render($this->getViewPath(), [
            'title' => 'Module Permission Management',
            'modules' => $modules,
            'roles' => $roles,
            'statistics' => $statistics,
            'accessScopes' => $accessScopes,
            'categories' => config('modules.categories', []),
            'componentTypes' => config('modules.component_types', []),
            'is_platform_context' => false,
            'can_manage_structure' => false, // Modules are defined in config/modules.php - read-only
            'readonly' => false, // Permissions can still be assigned
        ]);
    }

    /**
     * Get modules from config with proper structure for frontend
     */
    protected function getModulesFromConfig(string $configKey): array
    {
        $configModules = config($configKey, []);
        $modules = [];

        foreach ($configModules as $index => $module) {
            $moduleData = [
                'id' => $module['code'],
                'code' => $module['code'],
                'name' => $module['name'],
                'description' => $module['description'] ?? '',
                'icon' => $module['icon'] ?? 'CubeIcon',
                'route_prefix' => $module['route_prefix'] ?? '',
                'category' => $module['category'] ?? 'core_system',
                'priority' => $module['priority'] ?? ($index + 1),
                'is_core' => $module['is_core'] ?? false,
                'is_active' => $module['is_active'] ?? true,
                'sub_modules' => [], // Frontend expects sub_modules not submodules
            ];

            // Process submodules
            foreach ($module['submodules'] ?? [] as $subIndex => $submodule) {
                $submoduleData = [
                    'id' => $module['code'].'.'.$submodule['code'],
                    'code' => $submodule['code'],
                    'name' => $submodule['name'],
                    'description' => $submodule['description'] ?? '',
                    'icon' => $submodule['icon'] ?? 'FolderIcon',
                    'route' => $submodule['route'] ?? '',
                    'priority' => $submodule['priority'] ?? ($subIndex + 1),
                    'is_active' => true,
                    'components' => [],
                ];

                // Process components
                foreach ($submodule['components'] ?? [] as $compIndex => $component) {
                    $componentData = [
                        'id' => $module['code'].'.'.$submodule['code'].'.'.$component['code'],
                        'code' => $component['code'],
                        'name' => $component['name'],
                        'description' => $component['description'] ?? '',
                        'type' => $component['type'] ?? 'page',
                        'route' => $component['route'] ?? '',
                        'is_active' => true,
                        'actions' => [],
                    ];

                    // Process actions
                    foreach ($component['actions'] ?? [] as $action) {
                        $componentData['actions'][] = [
                            $componentData['actions'][] = [
                                'code' => $action['code'],
                                'name' => $action['name'],
                            ],
                        ];
                    }

                    $submoduleData['components'][] = $componentData;
                }

                $moduleData['sub_modules'][] = $submoduleData;
            }

            $modules[] = $moduleData;
        }

        return $modules;
    }

    /**
     * Get statistics from config-based modules
     */
    protected function getConfigStatistics(array $modules): array
    {
        $totalSubmodules = 0;
        $totalComponents = 0;
        $totalActions = 0;

        foreach ($modules as $module) {
            foreach ($module['sub_modules'] ?? [] as $submodule) {
                $totalSubmodules++;

                foreach ($submodule['components'] ?? [] as $component) {
                    $totalComponents++;

                    foreach ($component['actions'] ?? [] as $action) {
                        $totalActions++;
                    }
                }
            }
        }

        return [
            'total_modules' => count($modules),
            'total_sub_modules' => $totalSubmodules,
            'total_components' => $totalComponents,
            'total_actions' => $totalActions,
            'active_modules' => collect($modules)->where('is_active', true)->count(),
        ];
    }

    /**
     * Get modules from database with hierarchical structure for frontend
     */
    protected function getModulesFromDatabase(string $scope): array
    {
        $modules = Module::where('scope', $scope)
            ->where('is_active', true)
            ->with([
                'subModules' => function ($query) {
                    $query->orderBy('priority');
                },
                'subModules.components' => function ($query) {
                    $query->orderBy('id');
                },
                'subModules.components.actions' => function ($query) {
                    $query->orderBy('id');
                },
            ])
            ->orderBy('priority')
            ->get();

        return $modules->map(function ($module) {
            return [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'description' => $module->description ?? '',
                'icon' => $module->icon ?? 'CubeIcon',
                'route_prefix' => $module->route_prefix ?? '',
                'category' => $module->category ?? 'core_system',
                'priority' => $module->priority,
                'is_core' => $module->is_core,
                'is_active' => $module->is_active,
                'sub_modules' => $module->subModules->map(function ($subModule) {
                    return [
                        'id' => $subModule->id,
                        'code' => $subModule->code,
                        'name' => $subModule->name,
                        'description' => $subModule->description ?? '',
                        'icon' => $subModule->icon ?? 'FolderIcon',
                        'route' => $subModule->route ?? '',
                        'priority' => $subModule->priority,
                        'is_active' => $subModule->is_active ?? true,
                        'components' => $subModule->components->map(function ($component) {
                            return [
                                'id' => $component->id,
                                'code' => $component->code,
                                'name' => $component->name,
                                'description' => $component->description ?? '',
                                'type' => $component->type ?? 'page',
                                'route' => $component->route ?? '',
                                'is_active' => $component->is_active ?? true,
                                'actions' => $component->actions->map(function ($action) {
                                    return [
                                        'id' => $action->id,
                                        'code' => $action->code,
                                        'name' => $action->name,
                                        'description' => $action->description ?? '',
                                    ];
                                })->toArray(),
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get statistics from database-based modules
     */
    protected function getDatabaseStatistics(string $scope): array
    {
        $moduleIds = Module::where('scope', $scope)->where('is_active', true)->pluck('id');
        $subModuleIds = SubModule::whereIn('module_id', $moduleIds)->pluck('id');

        return [
            'total_modules' => $moduleIds->count(),
            'total_sub_modules' => $subModuleIds->count(),
            'total_components' => ModuleComponent::whereIn('sub_module_id', $subModuleIds)->count(),
            'total_actions' => ModuleComponentAction::whereHas('component', function ($q) use ($subModuleIds) {
                $q->whereIn('sub_module_id', $subModuleIds);
            })->count(),
        ];
    }

    /**
     * Get modules from database filtered by user's role access.
     * SECURITY FIX: Only return modules user has access to.
     */
    protected function getModulesFromDatabaseFiltered(string $scope, $user): array
    {
        // Get accessible module IDs from user's roles
        $accessibleModuleIds = collect();
        foreach ($user->roles as $role) {
            $roleModuleIds = $role->moduleAccess()
                ->whereNotNull('module_id')
                ->whereNull('sub_module_id')
                ->pluck('module_id');
            $accessibleModuleIds = $accessibleModuleIds->merge($roleModuleIds);
        }
        $accessibleModuleIds = $accessibleModuleIds->unique()->toArray();

        if (empty($accessibleModuleIds)) {
            return []; // No access to any modules
        }

        // Load only accessible modules with hierarchy
        $modules = Module::where('scope', $scope)
            ->where('is_active', true)
            ->whereIn('id', $accessibleModuleIds)
            ->with([
                'subModules' => function ($query) {
                    $query->orderBy('priority');
                },
                'subModules.components' => function ($query) {
                    $query->orderBy('id');
                },
                'subModules.components.actions' => function ($query) {
                    $query->orderBy('id');
                },
            ])
            ->orderBy('priority')
            ->get();

        return $modules->map(function ($module) {
            return [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'description' => $module->description ?? '',
                'icon' => $module->icon ?? 'CubeIcon',
                'route_prefix' => $module->route_prefix ?? '',
                'category' => $module->category ?? 'core_system',
                'priority' => $module->priority,
                'is_core' => $module->is_core,
                'is_active' => $module->is_active,
                'sub_modules' => $module->subModules->map(function ($subModule) {
                    return [
                        'id' => $subModule->id,
                        'code' => $subModule->code,
                        'name' => $subModule->name,
                        'description' => $subModule->description ?? '',
                        'icon' => $subModule->icon ?? 'FolderIcon',
                        'route' => $subModule->route ?? '',
                        'priority' => $subModule->priority,
                        'is_active' => $subModule->is_active ?? true,
                        'components' => $subModule->components->map(function ($component) {
                            return [
                                'id' => $component->id,
                                'code' => $component->code,
                                'name' => $component->name,
                                'description' => $component->description ?? '',
                                'type' => $component->type ?? 'page',
                                'route' => $component->route ?? '',
                                'is_active' => $component->is_active ?? true,
                                'actions' => $component->actions->map(function ($action) {
                                    return [
                                        'id' => $action->id,
                                        'code' => $action->code,
                                        'name' => $action->name,
                                        'description' => $action->description ?? '',
                                    ];
                                })->toArray(),
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get all modules via API
     */
    public function apiIndex()
    {
        try {
            $isPlatform = false;
            $configKey = $isPlatform ? 'modules.platform_hierarchy' : 'modules.hierarchy';
            $modules = $this->getModulesFromConfig($configKey);

            return response()->json([
                'modules' => $modules,
                'statistics' => $this->getConfigStatistics($modules),
                'is_platform_context' => $isPlatform,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get modules: '.$e->getMessage());

            return response()->json(['error' => 'Failed to retrieve modules'], 500);
        }
    }

    /**
     * Store a new module (Platform Admin Only)
     */
    public function storeModule(Request $request)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:modules,code',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'category' => 'required|string|in:'.implode(',', array_keys(Module::categories())),
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $module = Module::create([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'icon' => $request->icon,
                'category' => $request->category,
                'is_active' => $request->is_active ?? true,
                'is_premium' => $request->is_premium ?? false,
                'display_order' => $request->display_order ?? 0,
            ]);

            Log::info('Module created', [
                'module_id' => $module->id,
                'created_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Module created successfully',
                'module' => $module->load(['subModules', 'components']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create module: '.$e->getMessage());

            return response()->json(['error' => 'Failed to create module'], 500);
        }
    }

    /**
     * Update a module (Platform Admin Only)
     */
    public function updateModule(Request $request, $moduleId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:modules,code,'.$moduleId,
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'category' => 'required|string|in:'.implode(',', array_keys(Module::categories())),
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $module = Module::findOrFail($moduleId);
            $module->update($request->only([
                'name', 'code', 'description', 'icon', 'category',
                'is_active', 'is_premium', 'display_order',
            ]));

            Log::info('Module updated', [
                'module_id' => $module->id,
                'updated_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Module updated successfully',
                'module' => $module->fresh(['subModules', 'components']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update module: '.$e->getMessage());

            return response()->json(['error' => 'Failed to update module'], 500);
        }
    }

    /**
     * Delete a module (Platform Admin Only)
     */
    public function destroyModule($moduleId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $module = Module::findOrFail($moduleId);

            // Check if module is in use by any plans (uses PlanCatalogInterface to avoid cross-DB join)
            if (app()->bound(PlanCatalogInterface::class) && app(PlanCatalogInterface::class)->isModuleInAnyPlan($module->code)) {
                return response()->json([
                    'error' => 'Cannot delete module that is assigned to plans. Remove from plans first.',
                ], 422);
            }

            $moduleName = $module->name;
            $module->delete();

            Log::info('Module deleted', [
                'module_id' => $moduleId,
                'module_name' => $moduleName,
                'deleted_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Module deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete module: '.$e->getMessage());

            return response()->json(['error' => 'Failed to delete module'], 500);
        }
    }

    /**
     * Store a new sub-module (Platform Admin Only)
     */
    public function storeSubModule(Request $request, $moduleId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $module = Module::findOrFail($moduleId);

            $subModule = $module->subModules()->create([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'icon' => $request->icon,
                'is_active' => $request->is_active ?? true,
                'display_order' => $request->display_order ?? 0,
            ]);

            Log::info('Sub-module created', [
                'sub_module_id' => $subModule->id,
                'module_id' => $moduleId,
                'created_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Sub-module created successfully',
                'sub_module' => $subModule->load('components'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create sub-module: '.$e->getMessage());

            return response()->json(['error' => 'Failed to create sub-module'], 500);
        }
    }

    /**
     * Update a sub-module (Platform Admin Only)
     */
    public function updateSubModule(Request $request, $subModuleId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $subModule = SubModule::findOrFail($subModuleId);
            $subModule->update($request->only([
                'name', 'code', 'description', 'icon', 'is_active', 'display_order',
            ]));

            Log::info('Sub-module updated', [
                'sub_module_id' => $subModule->id,
                'updated_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Sub-module updated successfully',
                'sub_module' => $subModule->fresh('components'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update sub-module: '.$e->getMessage());

            return response()->json(['error' => 'Failed to update sub-module'], 500);
        }
    }

    /**
     * Delete a sub-module (Platform Admin Only)
     */
    public function destroySubModule($subModuleId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $subModule = SubModule::findOrFail($subModuleId);
            $subModuleName = $subModule->name;
            $subModule->delete();

            Log::info('Sub-module deleted', [
                'sub_module_id' => $subModuleId,
                'sub_module_name' => $subModuleName,
                'deleted_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Sub-module deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete sub-module: '.$e->getMessage());

            return response()->json(['error' => 'Failed to delete sub-module'], 500);
        }
    }

    /**
     * Store a new component (Platform Admin Only)
     */
    public function storeComponent(Request $request, $subModuleId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'type' => 'required|string|in:'.implode(',', array_keys(ModuleComponent::types())),
            'description' => 'nullable|string|max:1000',
            'route_name' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $subModule = SubModule::findOrFail($subModuleId);

            $component = $subModule->components()->create([
                'module_id' => $subModule->module_id,
                'name' => $request->name,
                'code' => $request->code,
                'type' => $request->type,
                'description' => $request->description,
                'route_name' => $request->route_name,
                'is_active' => $request->is_active ?? true,
                'display_order' => $request->display_order ?? 0,
            ]);

            Log::info('Component created', [
                'component_id' => $component->id,
                'sub_module_id' => $subModuleId,
                'created_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Component created successfully',
                'component' => $component,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create component: '.$e->getMessage());

            return response()->json(['error' => 'Failed to create component'], 500);
        }
    }

    /**
     * Update a component (Platform Admin Only)
     */
    public function updateComponent(Request $request, $componentId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'type' => 'required|string|in:'.implode(',', array_keys(ModuleComponent::types())),
            'description' => 'nullable|string|max:1000',
            'route_name' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $component = ModuleComponent::findOrFail($componentId);
            $component->update($request->only([
                'name', 'code', 'type', 'description', 'route_name', 'is_active', 'display_order',
            ]));

            Log::info('Component updated', [
                'component_id' => $component->id,
                'updated_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Component updated successfully',
                'component' => $component->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update component: '.$e->getMessage());

            return response()->json(['error' => 'Failed to update component'], 500);
        }
    }

    /**
     * Delete a component (Platform Admin Only)
     */
    public function destroyComponent($componentId)
    {
        if (true) {
            return response()->json([
                'error' => 'Module structure can only be managed from platform admin context.',
            ], 403);
        }

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $component = ModuleComponent::findOrFail($componentId);
            $componentName = $component->name;
            $component->delete();

            Log::info('Component deleted', [
                'component_id' => $componentId,
                'component_name' => $componentName,
                'deleted_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Component deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete component: '.$e->getMessage());

            return response()->json(['error' => 'Failed to delete component'], 500);
        }
    }

    /**
     * Sync permissions for a module (Tenant Context Only)
/ =====================================================
    // Role Module Access Management Endpoints
    // =====================================================

    /**
     * Get role access tree for a specific role (for UI display)
     *
     * The {roleId} is read explicitly from the route by name. Multi-tenant routes
     * carry a leading tenant route parameter, so a positional/typed binding would
     * receive the tenant slug instead of the role id.
     */
    public function getRoleAccess(Request $request)
    {
        $roleId = (int) $request->route('roleId');

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            // Context-free: the role is fetched by id on the active connection
            // (central for platform, tenant DB for tenant), so a found role is already
            // in-context. No hardcoded guard gate (that assumed the tenant 'web' guard).
            $role = Role::findOrFail($roleId);

            $accessTree = $this->roleModuleAccessService->getRoleAccessTree($role);

            return response()->json([
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'is_protected' => $role->is_protected,
                ],
                'access' => $accessTree,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get role access: '.$e->getMessage());

            return response()->json(['error' => 'Failed to retrieve role access'], 500);
        }
    }

    /**
     * Sync role access from UI selections
     *
     * Expected payload:
     * {
     *   "modules": [1, 2, 3],            // Module IDs with full access
     *   "sub_modules": [5, 6],           // SubModule IDs with access
     *   "components": [10, 11],          // Component IDs with access
     *   "actions": [                      // Action IDs with scope
     *     {"id": 20, "scope": "all"},
     *     {"id": 21, "scope": "own"}
     *   ]
     * }
     */
    public function syncRoleAccess(Request $request)
    {
        // Read {roleId} from the route by name — multi-tenant routes carry a leading
        // tenant route parameter that would otherwise bind positionally to $roleId.
        $roleId = (int) $request->route('roleId');

        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Debug: Log incoming request data
        Log::debug('syncRoleAccess request received', [
            'roleId' => $roleId,
            'request_data' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'modules' => 'nullable|array',
            'modules.*' => 'integer|exists:modules,id',
            'sub_modules' => 'nullable|array',
            'sub_modules.*' => 'integer|exists:sub_modules,id',
            'components' => 'nullable|array',
            'components.*' => 'integer|exists:module_components,id',
            'actions' => 'nullable|array',
            'actions.*.id' => 'required_with:actions|integer|exists:module_component_actions,id',
            'actions.*.scope' => 'nullable|string|in:all,own,team,department',
        ]);

        if ($validator->fails()) {
            Log::error('syncRoleAccess validation failed', [
                'roleId' => $roleId,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);

            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Context-free: the role is fetched by id on the active connection, so a
            // found role is already in-context (no hardcoded 'web' guard gate — platform
            // roles use the 'landlord' guard).
            $role = Role::findOrFail($roleId);

            // Check if role is protected (Super Administrator)
            if ($role->is_protected) {
                return response()->json([
                    'error' => 'Cannot modify access for protected roles. Protected roles have full system access.',
                ], 403);
            }

            $accessData = [
                'modules' => $request->input('modules', []),
                'sub_modules' => $request->input('sub_modules', []),
                'components' => $request->input('components', []),
                'actions' => $request->input('actions', []),
            ];

            // Capture old access state before sync for audit
            $oldAccessData = null;
            try {
                $oldAccessData = $this->roleModuleAccessService->getRoleAccessTree($role);
            } catch (\Exception $e) {
                Log::warning('Could not capture old access state for audit: '.$e->getMessage());
            }

            $this->roleModuleAccessService->syncRoleAccess($role, $accessData);

            Log::info('Role access synced', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'synced_by' => $this->getCurrentUser()->id,
                'modules_count' => count($accessData['modules']),
                'sub_modules_count' => count($accessData['sub_modules']),
                'components_count' => count($accessData['components']),
                'actions_count' => count($accessData['actions']),
            ]);

            // AUDIT: role access change with before/after state. AuditService::log()
            // is self-protecting (never throws), so no try/catch needed — the previous
            // code called a non-existent logRoleAccessChanged() and only caught
            // \Exception, so the resulting \Error 500'd the (already-persisted) save.
            // Pass before/after via metadata (json) rather than the before/after params:
            // those feed array_diff_assoc() for changed_fields, which chokes on the
            // nested-array access tree (Array-to-string warning -> ErrorException under
            // the web error handler) and would silently drop the audit row.
            app(AuditServiceInterface::class)->log(
                'role.module_access.updated',
                'module_access_synced',
                $role,
                "Role module access updated: {$role->name}",
                null,
                null,
                ['role' => $role->name, 'role_id' => $role->id, 'before' => $oldAccessData, 'after' => $accessData],
            );

            return response()->json([
                'message' => 'Role access updated successfully',
                'access' => $this->roleModuleAccessService->getRoleAccessTree($role),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync role access: '.$e->getMessage());

            return response()->json(['error' => 'Failed to update role access'], 500);
        }
    }

    /**
     * Grant full access to a module for a role
     */
    public function grantModuleAccess(Request $request, int $roleId, int $moduleId)
    {
        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $role = Role::findOrFail($roleId);

            if ($role->is_protected) {
                return response()->json([
                    'error' => 'Cannot modify access for protected roles.',
                ], 403);
            }

            $this->roleModuleAccessService->grantModuleAccess($role, $moduleId);

            Log::info('Module access granted', [
                'role_id' => $role->id,
                'module_id' => $moduleId,
                'granted_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Module access granted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to grant module access: '.$e->getMessage());

            return response()->json(['error' => 'Failed to grant access'], 500);
        }
    }

    /**
     * Revoke all access to a module for a role
     */
    public function revokeModuleAccess(Request $request, int $roleId, int $moduleId)
    {
        if (! $this->isSuperAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            $role = Role::findOrFail($roleId);

            if ($role->is_protected) {
                return response()->json([
                    'error' => 'Cannot modify access for protected roles.',
                ], 403);
            }

            $this->roleModuleAccessService->revokeModuleAccess($role, $moduleId);

            Log::info('Module access revoked', [
                'role_id' => $role->id,
                'module_id' => $moduleId,
                'revoked_by' => $this->getCurrentUser()->id,
            ]);

            return response()->json([
                'message' => 'Module access revoked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke module access: '.$e->getMessage());

            return response()->json(['error' => 'Failed to revoke access'], 500);
        }
    }

    /**
     * Get all roles with their access counts (for role selector dropdown)
     */
    public function getRolesWithAccessCounts()
    {
        try {
            $isPlatform = false;
            $guardName = 'web';

            $roles = Role::where('guard_name', $guardName)
                ->withCount([
                    'moduleAccess as modules_count' => function ($q) {
                        $q->whereNotNull('module_id')
                            ->whereNull('sub_module_id');
                    },
                    'moduleAccess as sub_modules_count' => function ($q) {
                        $q->whereNotNull('sub_module_id')
                            ->whereNull('component_id');
                    },
                    'moduleAccess as components_count' => function ($q) {
                        $q->whereNotNull('component_id')
                            ->whereNull('action_id');
                    },
                    'moduleAccess as actions_count' => function ($q) {
                        $q->whereNotNull('action_id');
                    },
                ])
                ->orderBy('name')
                ->get();

            return response()->json([
                'roles' => $roles,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get roles with access counts: '.$e->getMessage());

            return response()->json(['error' => 'Failed to retrieve roles'], 500);
        }
    }
}
