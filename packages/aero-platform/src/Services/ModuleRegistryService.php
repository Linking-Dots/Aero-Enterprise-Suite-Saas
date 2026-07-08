<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Illuminate\Support\Facades\DB;

/**
 * Assembles the platform Modules (registry) page — the TECHNICAL twin of the
 * Products catalog. Where Products governs what is *sold*, this governs what is
 * *shipped*: the module registry, its HRMAC hierarchy depth (sub-modules /
 * components / actions), dependencies, sync health, and which modules are core
 * vs sellable. No pricing lives here (that is a Product concern).
 *
 * All reads are via the query builder so it is free of the HRMAC context guard.
 */
class ModuleRegistryService
{
    /** @return array{kpis: array, modules: array, sync: array} */
    public function overview(): array
    {
        return [
            'kpis'    => $this->kpis(),
            'modules' => $this->modules(),
            'sync'    => $this->sync(),
        ];
    }

    /** Module codes that are sold by at least one product (pivot ∪ legacy scalar). */
    private function sellableCodes()
    {
        return DB::table('product_modules')->pluck('module_code')
            ->merge(DB::table('products')->whereNotNull('module_code')->pluck('module_code'))
            ->unique()
            ->flip();
    }

    /** @return array<int, array> */
    private function modules(): array
    {
        $subs = DB::table('sub_modules')->selectRaw('module_id, COUNT(*) c')->groupBy('module_id')->pluck('c', 'module_id');
        $comps = DB::table('module_components')->selectRaw('module_id, COUNT(*) c')->groupBy('module_id')->pluck('c', 'module_id');
        $acts = DB::table('module_component_actions as a')
            ->join('module_components as mc', 'a.module_component_id', '=', 'mc.id')
            ->selectRaw('mc.module_id, COUNT(*) c')->groupBy('mc.module_id')->pluck('c', 'module_id');
        $sellable = $this->sellableCodes();

        return DB::table('modules')
            ->orderByDesc('is_core')
            ->orderBy('priority')
            ->orderBy('name')
            ->get()
            ->map(function ($m) use ($subs, $comps, $acts, $sellable): array {
                $deps = $m->dependencies ? (json_decode($m->dependencies, true) ?: []) : [];

                return [
                    'id'          => $m->id,
                    'code'        => $m->code,
                    'name'        => $m->name,
                    'description' => $m->description,
                    'category'    => $m->category,
                    'scope'       => $m->scope,
                    'is_core'     => (bool) $m->is_core,
                    'is_active'   => (bool) $m->is_active,
                    'is_sellable' => isset($sellable[$m->code]),
                    'version'     => $m->version,
                    'dependencies' => array_values((array) $deps),
                    'sub_modules' => (int) ($subs[$m->id] ?? 0),
                    'components'  => (int) ($comps[$m->id] ?? 0),
                    'actions'     => (int) ($acts[$m->id] ?? 0),
                ];
            })
            ->all();
    }

    /** @return array<string, int> */
    private function kpis(): array
    {
        $sellable = $this->sellableCodes();
        $sellableCount = DB::table('modules')->pluck('code')->filter(fn ($c) => isset($sellable[$c]))->count();

        return [
            'total'      => (int) DB::table('modules')->count(),
            'core'       => (int) DB::table('modules')->where('is_core', true)->count(),
            'sellable'   => $sellableCount,
            'components' => (int) DB::table('module_components')->count(),
            'actions'    => (int) DB::table('module_component_actions')->count(),
            'active'     => (int) DB::table('modules')->where('is_active', true)->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function sync(): array
    {
        // updated_at moves on every aero:sync-module run — a reliable "last synced".
        $last = DB::table('modules')->max('updated_at');

        return [
            'last_synced' => $last,
            // Drift proxy: active modules that never synced a component (0 components).
            'drift'       => (int) DB::table('modules')
                ->where('is_active', true)
                ->whereNotIn('id', DB::table('module_components')->select('module_id')->distinct())
                ->count(),
        ];
    }
}
