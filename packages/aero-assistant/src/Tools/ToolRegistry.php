<?php

declare(strict_types=1);

namespace Aero\Assistant\Tools;

use Aero\Contracts\Ai\AeonToolContract;

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
     * Gemini functionDeclarations for the enabled tools (navigate + data tools).
     *
     * @return array<int,array<string,mixed>>
     */
    public function declarations(): array
    {
        $fns = [[
            'name' => 'navigate',
            'description' => 'Take the user to a page in AEOS365. Call this ONLY when the user asks to '
                .'go to, open, view or start creating something. Use the exact route path from the '
                .'knowledge base (e.g. /hrm/leave/types). Do not invent routes.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'route' => ['type' => 'string', 'description' => 'Exact route path, e.g. /hrm/leave/types'],
                    'label' => ['type' => 'string', 'description' => 'Human-readable destination, e.g. "Leave Types"'],
                ],
                'required' => ['route'],
            ],
        ]];

        foreach ($this->dataTools as $tool) {
            $fns[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => ['type' => 'object', 'properties' => (object) $tool->parameters(), 'required' => []],
            ];
        }

        return [['functionDeclarations' => $fns]];
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

        return $this->routes = $out;
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
