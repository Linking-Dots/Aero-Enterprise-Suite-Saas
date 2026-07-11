<?php

declare(strict_types=1);

namespace Aero\Assistant\Data;

use Aero\Contracts\Ai\AeonToolContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Aeon's dynamic, schema-aware data tool. The model fills a STRUCTURED query
 * (entity + operation + column/group_by/period) — never raw SQL. Every field is
 * validated against the live schema (SchemaCatalog), so it can query ANY table in
 * the system, but can never touch a column that doesn't exist, run a write, or
 * emit a sensitive/PII value (SchemaCatalog::SENSITIVE). Tenant isolation comes
 * from the current (tenant) DB connection.
 *
 * Access control: every query is HRMAC-gated by the module that owns the table
 * (SchemaCatalog::moduleFor + RoleModuleAccessInterface) — the table list shown
 * to the model AND every execution are both filtered. Every run is written to
 * the audit trail; list/find (row-level exposure) additionally writes an access
 * log. Results render as stat / table / chart / entityCard blocks, and a compact
 * `data` payload is returned so the model can reason and chain.
 */
class QueryTool implements AeonToolContract
{
    public function __construct(private SchemaCatalog $catalog) {}

    public function name(): string
    {
        return 'query_data';
    }

    public function description(): string
    {
        $entities = [];
        foreach ($this->catalog->all() as $e) {
            if ($this->userCanQuery($e['table'])) {
                $entities[] = $e['table'].' ('.$e['label'].')';
            }
        }
        $list = implode(', ', array_slice($entities, 0, 120));

        return 'Answer ANY data question about this system by querying a table. Provide `entity` (one '
            .'of the tables below), `operation` (count | aggregate | list), and optionally `group_by` '
            .'(a column), `column`+`aggregate` (sum/avg/min/max), `period` (today|last_7_days|last_30_days|'
            .'this_month|all_time) and `date_field`. Only use column names shown in the knowledge base for '
            .'that entity. Available tables: '.$list;
    }

    public function parameters(): array
    {
        return [
            'entity' => ['type' => 'string', 'description' => 'Table/entity to query, e.g. hrm_leave_applications'],
            'operation' => ['type' => 'string', 'enum' => ['count', 'aggregate', 'list', 'find'], 'description' => 'count rows, aggregate a numeric column, list rows, or find ONE record (use with a name filter) to show its details as a card'],
            'group_by' => ['type' => 'string', 'description' => 'Optional column to group counts by, e.g. status'],
            'chart' => ['type' => 'string', 'enum' => ['bar', 'donut'], 'description' => 'How to visualise a group_by: bar (ranking) or donut (share/proportion). Default bar.'],
            'column' => ['type' => 'string', 'description' => 'Numeric column for aggregate'],
            'aggregate' => ['type' => 'string', 'enum' => ['sum', 'avg', 'min', 'max']],
            'period' => ['type' => 'string', 'enum' => ['today', 'last_7_days', 'last_30_days', 'this_month', 'all_time']],
            'date_field' => ['type' => 'string', 'description' => 'Date column for period/trend, e.g. created_at'],
            'filters' => [
                'type' => 'array',
                'description' => 'Conditions to narrow the query. For a foreign-key column (ends with _id) pass '
                    .'the human NAME as the value (e.g. {"column":"department_id","op":"eq","value":"Sales"}) and it '
                    .'is resolved automatically. Use op: eq, ne, gt, gte, lt, lte, contains.',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'column' => ['type' => 'string'],
                        'op' => ['type' => 'string', 'enum' => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'contains']],
                        'value' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }

    public function run(array $args, ?int $userId): array
    {
        $entityKey = (string) ($args['entity'] ?? '');
        $entity = $this->catalog->entity($entityKey);
        if ($entity === null) {
            return $this->fail("I don't have a data table called \"{$entityKey}\".");
        }

        // HRMAC: the module that owns this table must be accessible to the user.
        if (! $this->userCanQuery($entityKey)) {
            $this->audit('aeon.query_denied', $entityKey, $args, 'denied');

            return $this->fail("You don't have access to ".Str::headline($this->catalog->moduleFor($entityKey)).' data.');
        }

        $label = $entity['label'];
        $operation = (string) ($args['operation'] ?? 'count');
        $groupBy = $this->validColumn($entityKey, $args['group_by'] ?? null);
        $dateField = $this->validColumn($entityKey, $args['date_field'] ?? null)
            ?? ($entity['date_fields'][0] ?? null);
        $period = (string) ($args['period'] ?? 'all_time');

        try {
            $query = DB::table($entity['table']);
            if (! empty($entity['soft_delete'])) {
                $query->whereNull('deleted_at'); // respect soft-deletes generically
            }
            $this->applyPeriod($query, $dateField, $period);
            $this->applyFilters($query, $entityKey, $args['filters'] ?? []);

            $this->audit('aeon.query', $entityKey, $args, 'executed');

            if ($operation === 'aggregate') {
                return $this->aggregate($query, $entityKey, $label, $args);
            }
            if ($operation === 'list') {
                return $this->listRows($query, $entity, $label);
            }
            if ($operation === 'find') {
                return $this->find($query, $entity, $label);
            }

            $chartArg = (string) ($args['chart'] ?? 'bar');
            $chart = in_array($chartArg, ['bar', 'donut'], true) ? $chartArg : 'bar';

            return $this->count($query, $entity, $label, $groupBy, $dateField, $period, $chart);
        } catch (\Throwable $e) {
            return $this->fail("I couldn't run that on {$label} — ".Str::limit($e->getMessage(), 80));
        }
    }

    /** @return array{text:string,blocks:array<int,array<string,mixed>>} */
    private function count($query, array $entity, string $label, ?string $groupBy, ?string $dateField, string $period, string $chart = 'bar'): array
    {
        if ($groupBy) {
            if ($this->catalog->isSensitive($groupBy)) {
                return $this->fail("I can't break {$label} down by {$groupBy}.");
            }
            $rows = (clone $query)
                ->select($groupBy, DB::raw('count(*) as aeon_c'))
                ->groupBy($groupBy)->orderByDesc('aeon_c')->limit(20)->get();
            $total = (int) (clone $query)->count();

            // Resolve foreign-key ids (e.g. department_id) to their real names.
            $labels = $this->resolveLabels($groupBy, $rows->pluck($groupBy)->all());
            $items = $rows->map(fn ($r) => [
                'label' => (string) ($labels[$r->{$groupBy}] ?? $this->formatValue($groupBy, $r->{$groupBy})),
                'value' => (int) $r->aeon_c,
            ])->all();

            return [
                'text' => "**{$total}** {$label} in total, by ".Str::headline($this->dimensionLabel($groupBy)).':',
                'blocks' => [
                    ['type' => 'stats', 'items' => [['k' => $label.$this->periodSuffix($period), 'v' => (string) $total]]],
                    ['type' => $chart, 'title' => Str::headline($label).' by '.Str::headline($this->dimensionLabel($groupBy)), 'items' => $items],
                ],
                'data' => ['total' => $total, 'group_by' => $groupBy, 'groups' => $items, 'period' => $period],
            ];
        }

        $total = (int) (clone $query)->count();
        $blocks = [['type' => 'stats', 'items' => [['k' => $label.$this->periodSuffix($period), 'v' => (string) $total]]]];

        if ($dateField) {
            $trend = $this->trend($query, $dateField);
            if ($trend) {
                $blocks[] = ['type' => 'chart', 'title' => Str::headline($label).' · last 14 days', 'points' => $trend];
            }
        }

        return [
            'text' => "There are **{$total}** {$label}".$this->periodPhrase($period).'.',
            'blocks' => $blocks,
            'data' => ['total' => $total, 'period' => $period],
        ];
    }

    /** @return array{text:string,blocks:array<int,array<string,mixed>>} */
    private function aggregate($query, string $entityKey, string $label, array $args): array
    {
        $column = $this->validColumn($entityKey, $args['column'] ?? null);
        $fn = strtolower((string) ($args['aggregate'] ?? 'sum'));
        if (! $column || ! in_array($fn, ['sum', 'avg', 'min', 'max'], true)) {
            return $this->fail('Tell me which numeric column to '.($args['aggregate'] ?? 'aggregate').'.');
        }
        if ($this->catalog->isSensitive($column)) {
            return $this->fail("I can't aggregate {$column}.");
        }
        $value = (clone $query)->{$fn}($column);
        $pretty = is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2), '0'), '.') : (string) $value;

        return [
            'text' => "The {$fn} of {$column} across {$label} is **{$pretty}**.",
            'blocks' => [['type' => 'stats', 'items' => [['k' => Str::upper($fn).' · '.Str::headline($column), 'v' => (string) $pretty]]]],
            'data' => ['aggregate' => $fn, 'column' => $column, 'value' => is_numeric($value) ? (float) $value : $value],
        ];
    }

    /** @return array{text:string,blocks:array<int,array<string,mixed>>,data:array<string,mixed>} */
    private function listRows($query, array $entity, string $label): array
    {
        $cols = array_values(array_filter(
            $entity['columns'],
            fn ($c) => ! $this->catalog->isSensitive($c),
        ));
        $cols = array_slice($cols, 0, 6);
        $idCols = in_array('id', $entity['columns'], true)
            ? array_values(array_unique(array_merge(['id'], $cols)))
            : $cols;
        $rows = (clone $query)->limit(10)->get($idCols);
        $tableRows = $rows->map(fn ($r) => array_map(fn ($c) => Str::limit($this->formatValue($c, $r->{$c}), 40), $cols))->all();

        $this->logAccess($entity['table'], null, $label, $cols);

        return [
            'text' => "Here are up to 10 {$label}:",
            'blocks' => [['type' => 'table', 'columns' => array_map([Str::class, 'headline'], $cols), 'rows' => $tableRows]],
            // ids included so the model can chain (e.g. find → update).
            'data' => ['rows' => $rows->map(fn ($r) => (array) $r)->all()],
        ];
    }

    /** @return array{text:string,blocks:array<int,array<string,mixed>>,data:array<string,mixed>} */
    private function find($query, array $entity, string $label): array
    {
        $row = (clone $query)->first();
        if (! $row) {
            return $this->fail("I couldn't find a matching {$label}.");
        }
        $arr = (array) $row;
        $title = $this->cardTitle($arr, $entity) ?? ($label.' #'.($arr['id'] ?? '?'));

        $fields = [];
        foreach ($entity['columns'] as $col) {
            if (count($fields) >= 8) {
                break;
            }
            if (in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at'], true) || $this->catalog->isSensitive($col)) {
                continue;
            }
            $val = $arr[$col] ?? null;
            if ($val === null || $val === '') {
                continue;
            }
            if (Str::endsWith($col, '_id') && is_numeric($val)) {
                $map = $this->resolveLabels($col, [$val]);
                $val = $map[$val] ?? $map[(int) $val] ?? (string) $val;
            } else {
                $val = $this->formatValue($col, $val);
            }
            $fields[] = ['k' => Str::headline($col), 'v' => Str::limit((string) $val, 60)];
        }

        $this->logAccess($entity['table'], $arr['id'] ?? null, $title, array_keys($arr));

        // Sensitive values stripped; id kept so the model can chain into an update.
        $safe = array_filter($arr, fn ($v, $k) => ! $this->catalog->isSensitive($k), ARRAY_FILTER_USE_BOTH);

        return [
            'text' => "Here's {$title}:",
            'blocks' => [['type' => 'entityCard', 'title' => $title, 'subtitle' => $label, 'fields' => $fields]],
            'data' => ['record' => $safe],
        ];
    }

    /**
     * @param  array<string,mixed>  $arr
     * @param  array{columns:array<int,string>}  $entity
     */
    private function cardTitle(array $arr, array $entity): ?string
    {
        foreach (['name', 'title', 'label', 'display_name', 'full_name'] as $c) {
            if (! empty($arr[$c])) {
                return (string) $arr[$c];
            }
        }
        $first = $arr['first_name'] ?? null;
        $last = $arr['last_name'] ?? null;
        if ($first || $last) {
            return trim(((string) $first).' '.((string) $last));
        }
        // Name held on a related record (e.g. employees.user_id → users.name).
        foreach (['user_id', 'person_id', 'employee_id', 'contact_id', 'owner_id'] as $fk) {
            if (! empty($arr[$fk]) && in_array($fk, $entity['columns'], true)) {
                $map = $this->resolveLabels($fk, [$arr[$fk]]);
                $name = $map[$arr[$fk]] ?? $map[(int) $arr[$fk]] ?? null;
                if ($name) {
                    return (string) $name;
                }
            }
        }
        foreach (['code', 'email', 'reference', 'number'] as $c) {
            if (! empty($arr[$c])) {
                return (string) $arr[$c];
            }
        }

        return null;
    }

    /** @return array<int,int>|null 14-day daily counts */
    private function trend($query, string $dateField): ?array
    {
        $start = Carbon::today()->subDays(13);
        $days = [];
        for ($i = 0; $i < 14; $i++) {
            $days[Carbon::today()->subDays(13 - $i)->format('Y-m-d')] = 0;
        }
        $rows = (clone $query)
            ->whereNotNull($dateField)
            ->where($dateField, '>=', $start->copy()->startOfDay())
            ->get([$dateField]);
        foreach ($rows as $r) {
            $key = optional(Carbon::parse($r->{$dateField}))->format('Y-m-d');
            if ($key !== null && array_key_exists($key, $days)) {
                $days[$key]++;
            }
        }

        return array_values($days);
    }

    private function applyPeriod($query, ?string $dateField, string $period): void
    {
        if (! $dateField || $period === 'all_time') {
            return;
        }
        $start = match ($period) {
            'today' => Carbon::today(),
            'last_7_days' => Carbon::today()->subDays(6),
            'last_30_days' => Carbon::today()->subDays(29),
            'this_month' => Carbon::now()->startOfMonth(),
            default => null,
        };
        if ($start) {
            $query->whereNotNull($dateField)->where($dateField, '>=', $start->startOfDay());
        }
    }

    private function validColumn(string $entity, $column): ?string
    {
        $column = is_string($column) && $column !== '' ? $column : null;

        return ($column !== null && $this->catalog->isColumn($entity, $column)) ? $column : null;
    }

    /**
     * Apply validated WHERE conditions. Unknown/sensitive columns are ignored;
     * a foreign-key filter given a NAME is resolved to matching ids.
     *
     * @param  mixed  $filters
     */
    private function applyFilters($query, string $entityKey, $filters): void
    {
        if (! is_array($filters)) {
            return;
        }
        foreach ($filters as $f) {
            if (! is_array($f)) {
                continue;
            }
            $col = $this->validColumn($entityKey, $f['column'] ?? null);
            if (! $col || $this->catalog->isSensitive($col)) {
                continue;
            }
            $op = strtolower((string) ($f['op'] ?? 'eq'));
            $value = $f['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            // Foreign key given a name → resolve to matching ids and match those.
            if (Str::endsWith($col, '_id') && ! is_numeric($value)) {
                $ids = $this->resolveNameToIds($col, (string) $value);
                $query->whereIn($col, $ids ?: [-1]); // no match → match nothing (honest)
                continue;
            }

            match ($op) {
                'ne' => $query->where($col, '!=', $value),
                'gt' => $query->where($col, '>', $value),
                'gte' => $query->where($col, '>=', $value),
                'lt' => $query->where($col, '<', $value),
                'lte' => $query->where($col, '<=', $value),
                'contains' => $query->where($col, 'like', '%'.$value.'%'),
                default => $query->where($col, '=', $value),
            };
        }
    }

    /**
     * Resolve a related record NAME to its id(s) via the related table's name column.
     *
     * @return array<int,int|string>
     */
    private function resolveNameToIds(string $col, string $name): array
    {
        $base = Str::beforeLast($col, '_id');
        foreach ([Str::plural($base), $base] as $table) {
            $e = $this->catalog->entity($table);
            if (! $e || ! in_array('id', $e['columns'], true)) {
                continue;
            }
            $nameCol = $this->nameColumn($e['columns']);
            if (! $nameCol) {
                continue;
            }
            try {
                return DB::table($e['table'])->where($nameCol, 'like', '%'.$name.'%')->pluck('id')->all();
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }

    /**
     * Resolve foreign-key ids (department_id → "Sales") using the related table's
     * name column. Returns id => name; empty when it isn't a resolvable relation.
     *
     * @param  array<int,mixed>  $ids
     * @return array<int|string,string>
     */
    private function resolveLabels(string $col, array $ids): array
    {
        if (! Str::endsWith($col, '_id')) {
            return [];
        }
        $ids = array_values(array_filter(array_unique($ids), static fn ($v) => $v !== null));
        if (empty($ids)) {
            return [];
        }
        $base = Str::beforeLast($col, '_id');
        foreach ([Str::plural($base), $base] as $table) {
            $e = $this->catalog->entity($table);
            if (! $e || ! in_array('id', $e['columns'], true)) {
                continue;
            }
            $nameCol = $this->nameColumn($e['columns']);
            if (! $nameCol) {
                continue;
            }
            try {
                return DB::table($e['table'])->whereIn('id', $ids)->pluck($nameCol, 'id')->all();
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }

    /** @param array<int,string> $columns */
    private function nameColumn(array $columns): ?string
    {
        foreach (['name', 'title', 'label', 'display_name', 'full_name', 'code'] as $c) {
            if (in_array($c, $columns, true)) {
                return $c;
            }
        }

        return null;
    }

    private function dimensionLabel(string $col): string
    {
        return Str::endsWith($col, '_id') ? Str::beforeLast($col, '_id') : $col;
    }

    /**
     * Human-friendly display of a raw DB value: booleans → Yes/No, ISO dates →
     * "Jan 5, 2026", snake_case enums → "Full Time". Leaves other values as-is.
     *
     * @param  mixed  $val
     */
    private function formatValue(string $col, $val): string
    {
        if ($val === null || $val === '') {
            return '—';
        }

        $isBoolCol = Str::startsWith($col, ['is_', 'has_', 'can_'])
            || in_array($col, ['active', 'enabled', 'published', 'archived', 'verified', 'default'], true);
        if ($isBoolCol && (is_bool($val) || in_array((string) $val, ['0', '1'], true))) {
            return ($val === true || (string) $val === '1') ? 'Yes' : 'No';
        }

        if (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2})?/', $val)) {
            try {
                return Carbon::parse($val)->format('M j, Y');
            } catch (\Throwable) {
            }
        }

        if (is_string($val) && str_contains($val, '_') && preg_match('/^[a-z][a-z0-9_]*$/', $val) && strlen($val) <= 30) {
            return Str::headline($val);
        }

        return (string) $val;
    }

    private function periodSuffix(string $period): string
    {
        return $period === 'all_time' ? '' : ' · '.Str::headline($period);
    }

    private function periodPhrase(string $period): string
    {
        return match ($period) {
            'today' => ' today',
            'last_7_days' => ' in the last 7 days',
            'last_30_days' => ' in the last 30 days',
            'this_month' => ' this month',
            default => '',
        };
    }

    /** @return array{text:string,blocks:array<int,array<string,mixed>>} */
    private function fail(string $msg): array
    {
        return ['text' => $msg, 'blocks' => []];
    }

    /**
     * HRMAC gate: the signed-in user must have access to the module that owns
     * the table. Consumer-guard pattern: when no HRMAC binding exists (isolated
     * package tests, console) the gate is a no-op.
     */
    private function userCanQuery(string $table): bool
    {
        try {
            if (! function_exists('auth') || ! app()->bound(\Aero\Contracts\RoleModuleAccessInterface::class)) {
                return true;
            }
            $user = auth()->user();
            if (! $user) {
                return true; // console / jobs — route middleware already requires auth for HTTP
            }

            $svc = app(\Aero\Contracts\RoleModuleAccessInterface::class);
            $moduleCode = $this->catalog->moduleFor($table);

            // Fast path: super-admin bypass + any explicit module-level grant.
            if ($svc->userCanAccessModule($user, $moduleCode)) {
                return true;
            }

            // This system grants access at the SUB-MODULE level (even admins have
            // no module-level rows), so module-level alone would wrongly deny
            // legitimately-authorised users. Allow the read when the user can reach
            // ANY sub-module of the owning module — i.e. they can see part of it
            // in the UI. (Row-level own-vs-all scoping is a separate concern; PII
            // columns are always stripped and every read is audited.)
            $accessibleSubModuleIds = $svc->getUserAccessibleSubModuleIds($user);
            if (empty($accessibleSubModuleIds)) {
                return false;
            }
            $moduleId = DB::table('modules')->where('code', $moduleCode)->value('id');
            if (! $moduleId) {
                return false;
            }

            return DB::table('sub_modules')
                ->whereIn('id', $accessibleSubModuleIds)
                ->where('module_id', $moduleId)
                ->exists();
        } catch (\Throwable) {
            return false; // a broken gate must deny, never expose
        }
    }

    /** Audit every query Aeon executes (never throws, never blocks the answer). */
    private function audit(string $event, string $table, array $args, string $outcome): void
    {
        try {
            if (! app()->bound(\Aero\Contracts\AuditServiceInterface::class)) {
                return;
            }
            app(\Aero\Contracts\AuditServiceInterface::class)->log(
                $event,
                'query',
                null,
                "Aeon queried {$table} (".($args['operation'] ?? 'count').") — {$outcome}",
                null,
                null,
                ['table' => $table, 'args' => $args],
            );
        } catch (\Throwable) {
            // audit must never break the chat turn
        }
    }

    /** Row-level exposure (list/find) also writes an access log. */
    private function logAccess(string $table, int|string|null $id, ?string $label, array $fields): void
    {
        try {
            if (! app()->bound(\Aero\Contracts\AuditServiceInterface::class)) {
                return;
            }
            app(\Aero\Contracts\AuditServiceInterface::class)->logAccess('aeon:'.$table, $id, $label, $fields);
        } catch (\Throwable) {
            // access-log failure must never break the chat turn
        }
    }
}
