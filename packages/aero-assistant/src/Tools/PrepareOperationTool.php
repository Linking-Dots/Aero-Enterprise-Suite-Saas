<?php

declare(strict_types=1);

namespace Aero\Assistant\Tools;

use Aero\Assistant\Operations\FormSpecBuilder;
use Aero\Assistant\Operations\OperationResolver;
use Aero\Assistant\Operations\RulesIntrospector;
use Aero\Contracts\Ai\AeonToolContract;

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
    ) {}

    public function name(): string
    {
        return 'prepare_operation';
    }

    public function description(): string
    {
        return 'Use when the user wants to CREATE, ADD, RECORD, UPDATE or perform an action on data '
            .'(e.g. "add a leave for Jane", "create an employee", "add a new tag"). Describe the record '
            .'in "entity" (e.g. "leave application", "employee", "tag"), set "operation" to create/update/'
            .'delete, and pass any details the user gave in "values" as a JSON object '
            .'(e.g. {"employee":"Jane Doe","start_date":"2026-07-09","leave_type":"Annual Leave"}). '
            .'Aeon shows the user a pre-filled form to review and submit — it does not write anything itself.';
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
            ];
        }

        $rules = $this->introspector->forAction((string) $op['controller'], (string) $op['action'], $op['table'] ?? null);
        $form = $this->builder->build($rules, $op, $values);
        $form['fields'] = $this->cleanFields($form['fields']);

        if (empty($form['fields'])) {
            // Nothing formable — hand off to the page instead of a broken form.
            return [
                'text' => "I can take you to the {$op['entity']} page to do this — that operation needs the full form.",
                'blocks' => [[
                    'type' => 'action', 'kind' => 'navigate',
                    'title' => $op['label'], 'route' => $op['uri'], 'confirm_label' => 'Open →',
                ]],
            ];
        }

        $filled = $this->countFilled($form['fields']);
        $text = $filled > 0
            ? "Here's a **{$op['label']}** with what you told me filled in — review, tweak anything, and submit."
            : "Here's a **{$op['label']}** form — fill it in and submit whenever you're ready.";

        $blocks = [$form];
        if (! empty($alternates)) {
            $blocks[] = [
                'type' => 'chips',
                'items' => array_map(static fn ($a) => $a['label'], array_slice($alternates, 0, 3)),
            ];
        }

        return ['text' => $text, 'blocks' => $blocks];
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
