<?php

declare(strict_types=1);

namespace Aero\Assistant\Operations;

use Aero\Assistant\Data\SchemaCatalog;
use Illuminate\Support\Str;

/**
 * Discovers every write operation the app exposes (create / update / delete /
 * action) straight from the router, and resolves a natural-language intent
 * ("add a leave", "create employee") to the exact registered route — so Aeon
 * drives the app's own, already-secured endpoints instead of a parallel path.
 *
 * An operation descriptor:
 *   ['name','uri','http','controller','action','kind','entity','label','table','params','keywords']
 */
class OperationResolver
{
    /** @var array<int,array<string,mixed>>|null */
    private ?array $catalog = null;

    public function __construct(private SchemaCatalog $schema) {}

    /**
     * Resolve an intent to the best operation + close alternates.
     *
     * @return array{best:?array<string,mixed>,alternates:array<int,array<string,mixed>>}
     */
    public function resolve(string $entity, string $operation = 'create'): array
    {
        $kind = $this->normaliseKind($operation);
        $wanted = $this->tokens($entity);
        if (empty($wanted)) {
            return ['best' => null, 'alternates' => []];
        }

        $scored = [];
        foreach ($this->catalog() as $op) {
            $overlap = count(array_intersect($wanted, $op['keywords']));
            if ($overlap === 0) {
                continue;
            }
            // exact phrase bonus + operation-kind alignment
            $score = $overlap * 10;
            $score += $overlap / max(1, count($op['keywords'])) * 5; // tightness
            if ($op['kind'] === $kind) {
                $score += 8;
            }
            // Prefer the CANONICAL REST resource route over legacy duplicates.
            // Apps with v1/v2 dupes expose two writers for one entity (e.g.
            // hrm.leave.types.store vs the legacy hrm.add-leave-type); the
            // resource-named/RESTful one is the maintained path that actually
            // persists, so it must win.
            $score += $this->canonicalScore($op);
            $scored[] = ['score' => $score, 'op' => $op];
        }
        if (empty($scored)) {
            return ['best' => null, 'alternates' => []];
        }
        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);

        $best = $scored[0]['op'];
        $alternates = [];
        foreach (array_slice($scored, 1, 3) as $s) {
            $alternates[] = $s['op'];
        }

        return ['best' => $best, 'alternates' => $alternates];
    }

    /**
     * Score how "canonical" a write route is. Resource-convention routes
     * (name ends .store/.update/.destroy, RESTful URI) are the maintained path;
     * legacy verb-in-URL routes (/hrm/add-leave-type, /delete-x) are the
     * duplicate that a v1/v2 codebase leaves behind — often a dead/parallel
     * writer. Reward the former, penalise the latter so the persisting route wins.
     *
     * @param  array<string,mixed>  $op
     */
    private function canonicalScore(array $op): float
    {
        $score = 0.0;
        $name = (string) ($op['name'] ?? '');
        $uri = (string) ($op['uri'] ?? '');

        // Resource-convention route name — the strongest "this is the real one" signal.
        if (Str::endsWith($name, ['.store', '.update', '.destroy'])) {
            $score += 12;
        }

        // Legacy verb baked into the URL path (add-, create-, update-, edit-,
        // delete-, remove-, save-, store- on a path segment) — the parallel writer.
        $lastSeg = Str::of($uri)->afterLast('/')->before('{')->__toString();
        if (Str::startsWith($lastSeg, ['add-', 'create-', 'update-', 'edit-', 'delete-', 'remove-', 'save-', 'store-', 'new-'])) {
            $score -= 10;
        }

        // Resolves to a real backing table → it actually persists something.
        if (! empty($op['table'])) {
            $score += 3;
        }

        return $score;
    }

    /** Look an operation up by its exact route name (model may pass it directly). */
    public function byName(string $routeName): ?array
    {
        foreach ($this->catalog() as $op) {
            if ($op['name'] === $routeName) {
                return $op;
            }
        }

        return null;
    }

    /**
     * Enumerate write operations once from the router.
     *
     * @return array<int,array<string,mixed>>
     */
    public function catalog(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $out = [];
        try {
            foreach (app('router')->getRoutes() as $route) {
                $http = $this->writeVerb($route->methods());
                if ($http === null) {
                    continue;
                }
                $action = $route->getActionName();
                if ($action === 'Closure' || ! str_contains($action, '@')) {
                    continue;
                }
                [$controller, $method] = explode('@', $action);
                $name = (string) $route->getName();
                $uri = '/'.ltrim($route->uri(), '/');
                $kind = $this->kind($name, $http, $uri);
                $table = $this->guessTable($name, $uri);
                $entity = $this->entityLabel($name, $uri, $table);

                $out[] = [
                    'name' => $name,
                    'uri' => $uri,
                    'http' => $http,
                    'controller' => $controller,
                    'action' => $method,
                    'kind' => $kind,
                    'entity' => $entity,
                    'label' => $this->opLabel($kind, $entity),
                    'table' => $table,
                    'params' => $this->routeParams($uri),
                    'keywords' => $this->keywords($name, $uri, $entity, $table),
                ];
            }
        } catch (\Throwable) {
            // router unavailable
        }

        return $this->catalog = $out;
    }

    /** @param array<int,string> $methods */
    private function writeVerb(array $methods): ?string
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $v) {
            if (in_array($v, $methods, true)) {
                return $v;
            }
        }

        return null;
    }

    private function kind(string $name, string $http, string $uri): string
    {
        if (Str::endsWith($name, '.store') || ($http === 'POST' && ! str_contains($uri, '{'))) {
            return 'create';
        }
        if (Str::endsWith($name, '.update') || in_array($http, ['PUT', 'PATCH'], true)) {
            return 'update';
        }
        if (Str::endsWith($name, '.destroy') || $http === 'DELETE') {
            return 'delete';
        }

        return 'action';
    }

    /** Guess the backing table by testing name/uri-derived candidates against the live schema. */
    private function guessTable(string $name, string $uri): ?string
    {
        $segs = array_values(array_filter(preg_split('/[.\/]/', trim($name !== '' ? $name : $uri, './')), function ($s) {
            return $s !== '' && ! str_contains($s, '{') && ! in_array($s, ['store', 'update', 'destroy', 'create', 'edit'], true);
        }));
        if (empty($segs)) {
            return null;
        }
        $last = end($segs);
        $prev = count($segs) >= 2 ? $segs[count($segs) - 2] : null;

        $candidates = [
            $last,
            Str::plural($last),
            Str::singular($last),
        ];
        if ($prev) {
            $candidates[] = $prev.'_'.$last;
            $candidates[] = Str::singular($prev).'_'.$last;
            $candidates[] = Str::singular($prev).'_'.Str::plural($last);
        }

        foreach ($candidates as $c) {
            $c = str_replace('-', '_', (string) $c);
            if ($c !== '' && $this->schema->entity($c)) {
                return $c;
            }
        }

        return null;
    }

    private function entityLabel(string $name, string $uri, ?string $table): string
    {
        if ($table) {
            return Str::headline(Str::singular($table));
        }
        $base = $name !== '' ? Str::beforeLast($name, '.') : trim($uri, '/');
        $seg = Str::of($base)->afterLast('.')->afterLast('/')->replace(['-', '_'], ' ')->__toString();

        return Str::headline(Str::singular($seg ?: 'record'));
    }

    private function opLabel(string $kind, string $entity): string
    {
        return match ($kind) {
            'create' => 'New '.$entity,
            'update' => 'Update '.$entity,
            'delete' => 'Delete '.$entity,
            default => $entity,
        };
    }

    /** @return array<int,string> */
    private function routeParams(string $uri): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\??\}/', $uri, $m);

        return $m[1] ?? [];
    }

    /**
     * Keyword set for intent matching.
     *
     * @return array<int,string>
     */
    private function keywords(string $name, string $uri, string $entity, ?string $table): array
    {
        $raw = preg_split('/[.\/_\-\s]+/', strtolower($name.' '.$uri.' '.$entity.' '.($table ?? '')));
        $drop = ['store', 'update', 'destroy', 'create', 'edit', 'index', 'show', 'the', 'a', 'an', 'hrm', 'core', 'platform', 'api', 'admin'];
        $set = [];
        foreach ((array) $raw as $w) {
            $w = trim($w);
            if ($w === '' || strlen($w) < 3 || str_contains($w, '{') || in_array($w, $drop, true)) {
                continue;
            }
            $set[$w] = true;
            $set[Str::singular($w)] = true;
        }

        return array_values(array_unique(array_keys($set)));
    }

    /** @return array<int,string> */
    private function tokens(string $phrase): array
    {
        $raw = preg_split('/[\s_\-]+/', strtolower(trim($phrase)));
        $drop = ['new', 'add', 'create', 'a', 'an', 'the', 'for', 'record', 'entry', 'to'];
        $out = [];
        foreach ((array) $raw as $w) {
            $w = trim($w);
            if ($w === '' || strlen($w) < 3 || in_array($w, $drop, true)) {
                continue;
            }
            $out[] = $w;
            $out[] = Str::singular($w);
        }

        return array_values(array_unique($out));
    }

    private function normaliseKind(string $operation): string
    {
        $o = strtolower(trim($operation));
        if (in_array($o, ['create', 'add', 'new', 'store'], true)) {
            return 'create';
        }
        if (in_array($o, ['update', 'edit', 'change', 'modify'], true)) {
            return 'update';
        }
        if (in_array($o, ['delete', 'remove', 'destroy'], true)) {
            return 'delete';
        }

        return in_array($o, ['create', 'update', 'delete', 'action'], true) ? $o : 'action';
    }
}
