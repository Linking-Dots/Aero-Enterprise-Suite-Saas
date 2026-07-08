<?php

declare(strict_types=1);

namespace Aero\Assistant\Services;

use Aero\Assistant\Data\SchemaCatalog;
use Aero\Assistant\Models\Embedding;
use Aero\Contracts\Ai\AiProvider;

/**
 * Builds Aeon's knowledge base: turns the live module registry (real names,
 * routes, actions) and any curated markdown into embedded chunks stored in
 * aeon_embeddings. Checksum-guarded so re-runs only embed changed content.
 */
class IndexingService
{
    public function __construct(private AiProvider $provider, private SchemaCatalog $schema) {}

    /**
     * @return array{indexed:int, skipped:int, sources:int}
     */
    public function index(bool $fresh = false): array
    {
        if ($fresh) {
            Embedding::query()->delete();
        }

        $chunks = array_merge($this->moduleChunks(), $this->schemaChunks(), $this->docChunks());
        $indexed = 0;
        $skipped = 0;

        foreach ($chunks as $c) {
            $checksum = md5($c['text']);
            if (Embedding::where('checksum', $checksum)->exists()) {
                $skipped++;
                continue;
            }

            $vectors = $this->provider->embed([$c['text']]);
            $vec = $vectors[0] ?? [];
            if (empty($vec)) {
                continue; // embedding failed (quota/transient) — skip, retry next run
            }

            Embedding::create([
                'source_type' => $c['source_type'],
                'source_ref' => $c['source_ref'],
                'title' => $c['title'],
                'chunk_text' => $c['text'],
                'vector' => $vec,
                'dims' => count($vec),
                'checksum' => $checksum,
            ]);
            $indexed++;
        }

        return ['indexed' => $indexed, 'skipped' => $skipped, 'sources' => count($chunks)];
    }

    /**
     * One chunk per registered module, from the live config('modules') registry
     * (accurate names, routes and permission actions) — grounds Aeon in what
     * actually exists in this install.
     *
     * @return array<int,array{source_type:string,source_ref:string,title:string,text:string}>
     */
    private function moduleChunks(): array
    {
        $chunks = [];
        foreach ((array) config('modules', []) as $code => $m) {
            if (! is_array($m)) {
                continue;
            }
            $name = $m['name'] ?? (is_string($code) ? ucfirst($code) : 'Module');
            $desc = $m['description'] ?? '';

            // Nav grouping: submodules render under a section header in the sidebar
            // (e.g. "Leaves" lives under "Time & Attendance"). Capture it so Aeon
            // gives the correct path, not a flattened guess.
            $navGroups = $m['nav_groups'] ?? [];
            $navMap = $m['nav_group_map'] ?? [];
            $sectionOf = static function ($smCode) use ($navGroups, $navMap): ?string {
                $key = $navMap[$smCode] ?? null;
                return $key ? ($navGroups[$key]['label'] ?? null) : null;
            };

            $lines = [];
            foreach ($m['submodules'] ?? [] as $sm) {
                $smCode = $sm['code'] ?? '';
                $smName = $sm['name'] ?? $smCode;
                $section = $sectionOf($smCode);
                $path = $name.($section ? " › {$section}" : '')." › {$smName}";
                $route = ! empty($sm['route']) ? "  [{$sm['route']}]" : '';
                $lines[] = "- {$path}{$route}";

                foreach ($sm['components'] ?? [] as $comp) {
                    $cName = $comp['name'] ?? ($comp['code'] ?? '');
                    if (! $cName) {
                        continue;
                    }
                    $cRoute = ! empty($comp['route']) ? "  [{$comp['route']}]" : '';
                    $actions = array_filter(array_map(
                        static fn ($a) => is_array($a) ? ($a['name'] ?? null) : null,
                        $comp['actions'] ?? []
                    ));
                    $actionStr = $actions ? ' — actions: '.implode(', ', $actions) : '';
                    $lines[] = "    · {$path} › {$cName}{$cRoute}{$actionStr}";
                }
            }

            $text = "Module: {$name}\n"
                .($desc ? "Description: {$desc}\n" : '')
                ."Navigation is shown as \"Section › Page › Sub-page  [route]\". Use these exact paths and routes:\n"
                .implode("\n", $lines);

            $chunks[] = [
                'source_type' => 'module',
                'source_ref' => (string) $code,
                'title' => (string) $name,
                'text' => $text,
            ];
        }

        return $chunks;
    }

    /**
     * One chunk per data table (from the live schema) so Aeon knows which entity
     * + columns to query with the query_data tool for any data question.
     *
     * @return array<int,array{source_type:string,source_ref:string,title:string,text:string}>
     */
    private function schemaChunks(): array
    {
        $chunks = [];
        foreach ($this->schema->all() as $e) {
            $cols = implode(', ', $e['columns']);
            $dates = implode(', ', $e['date_fields']);
            $text = "Data table: {$e['table']} ({$e['label']}). To answer data questions about {$e['label']} "
                ."— counts, breakdowns (group_by), totals/averages, or trends over time — call the query_data "
                ."tool with entity=\"{$e['table']}\". Columns available to count / group_by / aggregate / filter: {$cols}."
                .($dates ? " Date columns for period & trend: {$dates}." : '');
            $chunks[] = [
                'source_type' => 'schema',
                'source_ref' => $e['table'],
                'title' => $e['label'].' data',
                'text' => $text,
            ];
        }

        return $chunks;
    }

    /**
     * Curated markdown how-tos under the package's knowledge/ directory (optional).
     *
     * @return array<int,array{source_type:string,source_ref:string,title:string,text:string}>
     */
    private function docChunks(): array
    {
        $dir = config('aeon.rag.knowledge_path');
        if (! $dir || ! is_dir($dir)) {
            return [];
        }

        $chunks = [];
        foreach (glob(rtrim($dir, '/').'/*.md') ?: [] as $file) {
            $raw = (string) file_get_contents($file);
            $name = basename($file, '.md');
            foreach ($this->splitMarkdown($raw) as $i => $part) {
                $title = $this->firstHeading($part) ?: $name;
                $chunks[] = [
                    'source_type' => 'doc',
                    'source_ref' => $name.'.md',
                    'title' => $title,
                    'text' => trim($part),
                ];
            }
        }

        return $chunks;
    }

    /** Split markdown on H2/H3 boundaries so each section is its own chunk. */
    private function splitMarkdown(string $md): array
    {
        $parts = preg_split('/\n(?=#{1,3}\s)/', $md) ?: [$md];

        return array_values(array_filter(array_map('trim', $parts), static fn ($p) => $p !== ''));
    }

    private function firstHeading(string $md): ?string
    {
        if (preg_match('/^#{1,3}\s+(.+)$/m', $md, $mm)) {
            return trim($mm[1]);
        }

        return null;
    }
}
