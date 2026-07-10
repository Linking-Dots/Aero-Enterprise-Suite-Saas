<?php

declare(strict_types=1);

namespace Aero\Assistant\Tools;

use Aero\Assistant\Data\SchemaCatalog;
use Aero\Assistant\Operations\FormSpecBuilder;
use Aero\Assistant\Operations\OperationResolver;
use Aero\Assistant\Operations\RulesIntrospector;
use Aero\Contracts\Ai\AeonToolContract;
use Illuminate\Support\Facades\DB;

/**
 * Aeon's generic "do the operation" tool. Given what the user wants to create /
 * update / act on, it resolves the exact registered route, introspects that
 * route's validation contract, and returns a pre-filled `form` block. On submit
 * the form posts to the REAL endpoint, so the app's validation, permissions
 * (HRMAC) and audit all run — Aeon never writes behind the app's back.
 */
class PrepareOperationTool implements AeonToolContract
{
    public function __construct(
        private OperationResolver $resolver,
        private RulesIntrospector $introspector,
        private FormSpecBuilder $builder,
        private SchemaCatalog $catalog,
    ) {}

    public function name(): string
    {
        return 'prepare_operation';
    }

    public function description(): string
    {
        return 'Use when the user wants to CREATE, ADD, RECORD, UPDATE, DELETE or perform an action on data '
            .'(e.g. "add a leave for Jane", "create an employee", "delete that tag"). Describe the record '
            .'in "entity" (e.g. "leave application", "employee", "tag"), set "operation" to create/update/'
            .'delete, and pass any details the user gave in "values" as a JSON object '
            .'(e.g. {"employee":"Jane Doe","start_date":"2026-07-09","leave_type":"Annual Leave"}). '
            .'For UPDATE/DELETE of a specific record, first use query_data (operation "find" with a filter) '
            .'to get its id, then include {"id": <id>} in "values" — current values are prefilled automatically. '
            .'The user always reviews and submits the form; nothing is written without their confirmation.';
    }

    /** @return array<string,mixed> */
    public function parameters(): array
    {
        return [
            'entity' => ['type' => 'string', 'description' => 'What to act on, e.g. "leave application", "employee", "tag".'],
            'operation' => ['type' => 'string', 'description' => 'create | update | delete | action (default create).'],
            'route_name' => ['type' => 'string', 'description' => 'Exact registered route name if known (e.g. hrm.leave.applications.store); optional.'],
            'values' => ['type' => 'string', 'description' => 'JSON object of known field values, e.g. {"employee":"Jane Doe","start_date":"2026-07-09"}.'],
        ];
    }

    /**
     * @param  array<string,mixed>  $args
     * @return array{text:string,blocks:array<int,array<string,mixed>>}
     */
    public function run(array $args, ?int $userId): array
    {
        $entity = trim((string) ($args['entity'] ?? ''));
        $operation = trim((string) ($args['operation'] ?? 'create')) ?: 'create';
        $routeName = trim((string) ($args['route_name'] ?? ''));
        $values = $this->decodeValues($args['values'] ?? null);

        $op = $routeName !== '' ? $this->resolver->byName($routeName) : null;
        $alternates = [];
        if (! $op) {
            $res = $this->resolver->resolve($entity !== '' ? $entity : $routeName, $operation);
            $op = $res['best'];
            $alternates = $res['alternates'];
        }

        if (! $op) {
            return [
                'text' => "I couldn't find an operation for that. Tell me exactly what you'd like to add or change "
                    ."(for example \"add a leave application\" or \"create a tag\") and I'll open a form for it.",
                'blocks' => [],
                'terminal' => false,
                'data' => ['status' => 'not_found', 'message' => 'No matching operation. Try a more specific entity name, or pass route_name.'],
            ];
        }

        // Update/delete/action target a specific record: resolve it and prefill
        // its current values so the user edits reality, not a blank form.
        $values = $this->mergeRecordDefaults($op, $values);

        $rules = $this->introspector->forAction((string) $op['controller'], (string) $op['action'], $op['table'] ?? null);
        $form = $this->builder->build($rules, $op, $values);
        $form['fields'] = $this->cleanFields($form['fields']);

        // Route params still unresolved (e.g. /hrm/leave/{leave}) — the model
        // must find the record id first (query_data find), then call again.
        if (str_contains((string) $form['action'], '{')) {
            return [
                'text' => "I need to know exactly which {$op['entity']} you mean first.",
                'blocks' => [],
                'terminal' => false,
                'data' => [
                    'status' => 'needs_record',
                    'message' => "Route {$op['uri']} needs ".implode(', ', (array) $op['params'])
                        .'. Use query_data (operation "find" with a filter) to get the record id, then call '
                        .'prepare_operation again with values including {"id": <the id>}.',
                ],
            ];
        }

        if (empty($form['fields']) && $op['kind'] !== 'delete') {
            // Nothing formable — hand off to the page instead of a broken form.
            return [
                'text' => "I can take you to the {$op['entity']} page to do this — that operation needs the full form.",
                'blocks' => [[
                    'type' => 'action', 'kind' => 'navigate',
                    'title' => $op['label'], 'route' => $op['uri'], 'confirm_label' => 'Open →',
                ]],
                'terminal' => true,
            ];
        }

        $this->audit($op, $values);

        $filled = $this->countFilled($form['fields']);
        $text = match (true) {
            $op['kind'] === 'delete' => "Please confirm — this will **delete** this {$op['entity']} through the app's own endpoint (your permissions apply).",
            $filled > 0 => "Here's a **{$op['label']}** with what you told me filled in — review, tweak anything, and submit.",
            default => "Here's a **{$op['label']}** form — fill it in and submit whenever you're ready.",
        };

        $blocks = [$form];
        if (! empty($alternates)) {
            $blocks[] = [
                'type' => 'chips',
                'items' => array_map(static fn ($a) => $a['label'], array_slice($alternates, 0, 3)),
            ];
        }

        return [
            'text' => $text,
            'blocks' => $blocks,
            'terminal' => true,
            'data' => ['status' => 'form_shown', 'operation' => $op['name'], 'kind' => $op['kind']],
        ];
    }

    /**
     * For update/delete/action ops: resolve the target record (id from values or
     * route params), prefill unspecified fields with its current non-sensitive
     * values, and fill the route params.
     *
     * @param  array<string,mixed>  $op
     * @param  array<string,mixed>  $values
     * @return array<string,mixed>
     */
    private function mergeRecordDefaults(array $op, array $values): array
    {
        if (($op['kind'] ?? '') === 'create' || empty($op['table'])) {
            return $values;
        }

        $id = $values['id'] ?? null;
        foreach ((array) ($op['params'] ?? []) as $p) {
            $id = $id ?? $values[$p] ?? $values[$p.'_id'] ?? null;
        }
        if ($id === null || ! is_scalar($id)) {
            return $values;
        }

        try {
            $record = (array) DB::table((string) $op['table'])->where('id', $id)->first();
        } catch (\Throwable) {
            $record = [];
        }
        if (empty($record)) {
            return $values;
        }

        foreach ($record as $col => $val) {
            if (array_key_exists($col, $values) || $val === null || ! is_scalar($val)) {
                continue;
            }
            if ($this->catalog->isSensitive($col)) {
                continue; // never surface a sensitive value into a chat form
            }
            $values[$col] = $val;
        }
        foreach ((array) ($op['params'] ?? []) as $p) {
            $values[$p] = $values[$p] ?? $id;
        }

        return $values;
    }

    /** Audit that Aeon prepared a write form (the submit is audited by the endpoint itself). */
    private function audit(array $op, array $values): void
    {
        try {
            if (! app()->bound(\Aero\Contracts\AuditServiceInterface::class)) {
                return;
            }
            app(\Aero\Contracts\AuditServiceInterface::class)->log(
                'aeon.operation_prepared',
                (string) ($op['kind'] ?? 'action'),
                null,
                "Aeon prepared \"{$op['label']}\" ({$op['name']})",
                null,
                null,
                ['route' => $op['uri'], 'prefilled' => array_keys($values)],
            );
        } catch (\Throwable) {
            // audit must never break the chat turn
        }
    }

    /** Strip internal resolver keys before the block goes to the client. */
    private function cleanFields(array $fields): array
    {
        return array_map(static function ($f) {
            unset($f['_table'], $f['_col']);

            return $f;
        }, $fields);
    }

    private function countFilled(array $fields): int
    {
        $n = 0;
        foreach ($fields as $f) {
            if (isset($f['value']) && $f['value'] !== null && $f['value'] !== '') {
                $n++;
            }
        }

        return $n;
    }

    private function decodeValues(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
