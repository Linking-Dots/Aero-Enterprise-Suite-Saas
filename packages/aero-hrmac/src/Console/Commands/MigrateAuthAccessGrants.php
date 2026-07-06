<?php

declare(strict_types=1);

namespace Aero\HRMAC\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-point role_module_access grants from the OLD user/role/module-access code
 * paths (which lived under aero-core `core.*` and aero-platform `platform-*`) to
 * the NEW shared foundations: user admin → `auth.user_management.*`, access
 * control → `hrmac.roles_permissions.*`.
 *
 * Grants are stored by FK id (module_id/sub_module_id/component_id/action_id), so
 * after the config move + resync the OLD rows still hold the grants. This copies
 * each grant to the equivalent NEW row (matched by code path) so NON-super roles
 * keep their access. Idempotent — safe to re-run; skips grants that already exist.
 *
 * Run per DB: central (--scope not needed, uses current connection) and inside
 * each tenant context. Pair with `aero:sync-module --prune` afterwards to drop
 * the stale old rows.
 *
 * Usage:  php artisan aero:migrate-auth-grants [--dry-run]
 */
class MigrateAuthAccessGrants extends Command
{
    protected $signature = 'aero:migrate-auth-grants {--dry-run : Report what would change without writing}';

    protected $description = 'Re-point role grants from old core/platform user+role codes to the shared auth/hrmac codes';

    /**
     * Old (module.submodule[.component]) code path → new path. Component codes
     * below core submodules are IDENTICAL (users/user_invitations/roles/
     * module_access), so a submodule remap carries them; only the platform
     * landlord-* components rename, handled explicitly.
     *
     * @var array<string, array{module: string, submodule: string, component: ?string}>
     */
    private array $submoduleMap = [
        'core.user_management'    => ['module' => 'auth',  'submodule' => 'user_management',    'component' => null],
        'core.roles_permissions'  => ['module' => 'hrmac', 'submodule' => 'roles_permissions',  'component' => null],
        'platform.platform-users' => ['module' => 'auth',  'submodule' => 'user_management',    'component' => null], // + hrmac added below
    ];

    /**
     * Platform component renames (codes differ below platform-users).
     *
     * @var array<string, array{module: string, submodule: string, component: string}>
     */
    private array $platformComponentMap = [
        'landlord-user-list' => ['module' => 'auth',  'submodule' => 'user_management',   'component' => 'users'],
        'landlord-roles'     => ['module' => 'hrmac', 'submodule' => 'roles_permissions', 'component' => 'roles'],
        'module-access'      => ['module' => 'hrmac', 'submodule' => 'roles_permissions', 'component' => 'module_access'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // Skip the self-service profile: it stays in core (core.user_management.user_profile).
        $skipComponents = ['user_profile'];

        $grants = DB::table('role_module_access')->get();

        $created = 0;
        $skipped = 0;

        foreach ($grants as $grant) {
            // Grants are stored at ONE level only (deepest id set; parent ids null),
            // so resolve the effective module.submodule.component.action codes upward.
            [$mcode, $scode, $ccode, $acode] = $this->effectivePath($grant);

            $g = (object) [
                'role_id' => $grant->role_id,
                'access_scope' => $grant->access_scope ?? null,
                'status' => $grant->status ?? null,
                'mcode' => $mcode,
                'scode' => $scode,
                'ccode' => $ccode,
                'acode' => $acode,
            ];

            $targets = $this->resolveTargets($g);
            if (empty($targets)) {
                continue;
            }
            if (in_array($g->ccode, $skipComponents, true)) {
                continue;
            }

            foreach ($targets as $t) {
                $ids = $this->resolveRowIds($t['module'], $t['submodule'], $t['component'] ?? null, $g->acode && $t['component'] ? $g->acode : null);
                if ($ids === null) {
                    continue;
                }

                $exists = DB::table('role_module_access')
                    ->where('role_id', $g->role_id)
                    ->where('module_id', $ids['module_id'])
                    ->where('sub_module_id', $ids['sub_module_id'])
                    ->where('component_id', $ids['component_id'])
                    ->where('action_id', $ids['action_id'])
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                if (! $dry) {
                    DB::table('role_module_access')->insert([
                        'role_id' => $g->role_id,
                        'module_id' => $ids['module_id'],
                        'sub_module_id' => $ids['sub_module_id'],
                        'component_id' => $ids['component_id'],
                        'action_id' => $ids['action_id'],
                        'access_scope' => $g->access_scope ?? 'all',
                        'status' => $g->status ?? 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $created++;
            }
        }

        $verb = $dry ? 'would create' : 'created';
        $this->info("✅ auth/hrmac grant migration: {$verb} {$created} grant(s), {$skipped} already present.");

        return self::SUCCESS;
    }

    /**
     * Resolve a grant's effective [moduleCode, subCode, compCode, actionCode] by
     * walking UP from the single level it is stored at (parent ids are null).
     *
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string}
     */
    private function effectivePath(object $grant): array
    {
        if ($grant->action_id) {
            $a = DB::table('module_component_actions')->find($grant->action_id);
            $c = $a ? DB::table('module_components')->find($a->component_id) : null;
            $s = $c ? DB::table('sub_modules')->find($c->sub_module_id) : null;
            $m = $s ? DB::table('modules')->find($s->module_id) : null;

            return [$m->code ?? null, $s->code ?? null, $c->code ?? null, $a->code ?? null];
        }

        if ($grant->component_id) {
            $c = DB::table('module_components')->find($grant->component_id);
            $s = $c ? DB::table('sub_modules')->find($c->sub_module_id) : null;
            $m = $s ? DB::table('modules')->find($s->module_id) : null;

            return [$m->code ?? null, $s->code ?? null, $c->code ?? null, null];
        }

        if ($grant->sub_module_id) {
            $s = DB::table('sub_modules')->find($grant->sub_module_id);
            $m = $s ? DB::table('modules')->find($s->module_id) : null;

            return [$m->code ?? null, $s->code ?? null, null, null];
        }

        if ($grant->module_id) {
            $m = DB::table('modules')->find($grant->module_id);

            return [$m->code ?? null, null, null, null];
        }

        return [null, null, null, null];
    }

    /**
     * Resolve the NEW target(s) for an old grant from its code path. Returns an
     * array of targets (platform submodule-level grants fan out to auth + hrmac).
     *
     * @return array<int, array{module: string, submodule: string, component: ?string}>
     */
    private function resolveTargets(object $g): array
    {
        // Platform component-level rename (landlord-* / module-access).
        if ($g->mcode === 'platform' && $g->scode === 'platform-users' && $g->ccode !== null) {
            return isset($this->platformComponentMap[$g->ccode]) ? [$this->platformComponentMap[$g->ccode]] : [];
        }

        // Platform submodule-level grant → fan out to both foundations.
        if ($g->mcode === 'platform' && $g->scode === 'platform-users' && $g->ccode === null) {
            return [
                ['module' => 'auth',  'submodule' => 'user_management',   'component' => null],
                ['module' => 'hrmac', 'submodule' => 'roles_permissions', 'component' => null],
            ];
        }

        // Core submodule remap (component/action codes carry over unchanged).
        $key = ($g->mcode ?? '').'.'.($g->scode ?? '');
        if (isset($this->submoduleMap[$key])) {
            $t = $this->submoduleMap[$key];

            // Carry the component code through (it's identical below core submodules).
            return [['module' => $t['module'], 'submodule' => $t['submodule'], 'component' => $g->ccode]];
        }

        return [];
    }

    /**
     * Resolve module/sub_module/component/action row ids for a target code path.
     * Missing levels resolve to null (a submodule-level grant has null component/action).
     *
     * @return array{module_id: ?int, sub_module_id: ?int, component_id: ?int, action_id: ?int}|null
     */
    private function resolveRowIds(string $moduleCode, string $subCode, ?string $compCode, ?string $actionCode): ?array
    {
        $moduleId = DB::table('modules')->where('code', $moduleCode)->value('id');
        if (! $moduleId) {
            return null;
        }

        $subId = DB::table('sub_modules')->where('module_id', $moduleId)->where('code', $subCode)->value('id');
        if (! $subId) {
            return null;
        }

        $compId = null;
        $actionId = null;

        if ($compCode !== null) {
            $compId = DB::table('module_components')->where('sub_module_id', $subId)->where('code', $compCode)->value('id');
            if (! $compId) {
                return null; // component expected but absent → skip (don't mis-grant)
            }

            if ($actionCode !== null) {
                $actionId = DB::table('module_component_actions')->where('component_id', $compId)->where('code', $actionCode)->value('id');
                if (! $actionId) {
                    return null;
                }
            }
        }

        return [
            'module_id' => $subId ? null : $moduleId, // grants are held at the deepest set level; submodule grants keep module_id null
            'sub_module_id' => $subId,
            'component_id' => $compId,
            'action_id' => $actionId,
        ];
    }
}
