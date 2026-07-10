<?php

declare(strict_types=1);

namespace Aero\Assistant\Tools;

use Aero\Contracts\Ai\AeonToolContract;
use Illuminate\Support\Str;

/**
 * Aeon's tool catalog: the built-in `navigate` guided tool + any data tools that
 * feature packages register (tagged 'aeon.tools'). Every navigation is validated
 * against the live module registry, so Aeon can never route to a non-existent
 * page. Registry is open for more tools (data reads now, guarded writes later).
 */
class ToolRegistry
{
    /** @var array<string,string>|null  route => "Section › Page" label */
    private ?array $routes = null;

    /** @param iterable<AeonToolContract> $dataTools */
    public function __construct(private iterable $dataTools = []) {}

    /**
     * Neutral tool declarations (navigate + data tools). Each provider adapts
     * this list to its own wire format (Gemini functionDeclarations, OpenAI tools).
     *
     * @return array<int,array{name:string,description:string,parameters:array<string,mixed>}>
     */
    public function declarations(): array
    {
        $fns = [[
            'name' => 'navigate',
            'description' => 'Take the user to a page in AEOS365. Call this ONLY when the user asks to go to '
                .'or open a page. Use the exact route path from the knowledge base (e.g. /hrm/leave/types). '
                .'To open a NEW/create form, append "/create" to that entity\'s list route '
                .'(e.g. /hrm/employees → /hrm/employees/create); these are valid. Do not invent other routes.',
            'parameters' => [
                'route' => ['type' => 'string', 'description' => 'Exact route path, e.g. /hrm/leave/types'],
                'label' => ['type' => 'string', 'description' => 'Human-readable destination, e.g. "Leave Types"'],
            ],
        ]];

        foreach ($this->dataTools as $tool) {
            $fns[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => $tool->parameters(),
            ];
        }

        return $fns;
    }

    public function dataTool(string $name): ?AeonToolContract
    {
        foreach ($this->dataTools as $tool) {
            if ($tool->name() === $name) {
                return $tool;
            }
        }

        return null;
    }

    /** @return array<string,string> route => label */
    public function routes(): array
    {
        if ($this->routes !== null) {
            return $this->routes;
        }

        $out = [];
        foreach ((array) config('modules', []) as $m) {
            if (! is_array($m)) {
                continue;
            }
            $moduleName = $m['name'] ?? '';
            $navGroups = $m['nav_groups'] ?? [];
            $navMap = $m['nav_group_map'] ?? [];

            foreach ($m['submodules'] ?? [] as $sm) {
                $smCode = $sm['code'] ?? '';
                $section = isset($navMap[$smCode]) ? ($navGroups[$navMap[$smCode]]['label'] ?? null) : null;
                $prefix = trim($moduleName.($section ? " › {$section}" : ''), ' ›');

                if (! empty($sm['route'])) {
                    $out[$sm['route']] = trim($prefix.' › '.($sm['name'] ?? $smCode), ' ›');
                }
                foreach ($sm['components'] ?? [] as $comp) {
                    if (! empty($comp['route'])) {
                        $out[$comp['route']] = trim($prefix.' › '.($sm['name'] ?? $smCode).' › '.($comp['name'] ?? ''), ' ›');
                    }
                }
            }
        }

        // Also allow navigating to real "create/new" forms (guided actions).
        foreach ($this->createRoutes() as $route => $label) {
            $out[$route] = $label;
        }

        return $this->routes = $out;
    }

    /**
     * Real GET create-form routes from the router (named *.create or URI ending
     * /create, no path params) so Aeon can open a genuine "New X" form.
     *
     * @return array<string,string> route => "New X"
     */
    private function createRoutes(): array
    {
        $out = [];
        try {
            foreach (app('router')->getRoutes() as $route) {
                if (! in_array('GET', $route->methods(), true)) {
                    continue;
                }
                $uri = '/'.ltrim($route->uri(), '/');
                if (str_contains($uri, '{')) {
                    continue; // needs parameters we can't supply
                }
                $name = (string) $route->getName();
                $isCreate = Str::endsWith($name, '.create') || Str::endsWith($uri, '/create');
                if (! $isCreate) {
                    continue;
                }
                // 'hrm.employees.create' -> "New Employee"; fallback from the URI.
                $base = $name !== '' ? Str::beforeLast($name, '.create') : trim(Str::beforeLast($uri, '/create'), '/');
                $entity = Str::of($base)->afterLast('.')->afterLast('/')->replace('-', ' ')->__toString();
                if ($entity === '') {
                    continue;
                }
                $out[$uri] = 'New '.Str::headline(Str::singular($entity));
            }
        } catch (\Throwable) {
            // router unavailable (e.g. isolated tests) — no create routes
        }

        return $out;
    }

    public function isValidRoute(?string $route): bool
    {
        return $route !== null && array_key_exists($route, $this->routes());
    }

    public function labelForRoute(string $route): ?string
    {
        return $this->routes()[$route] ?? null;
    }
}
