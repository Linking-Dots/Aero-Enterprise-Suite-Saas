<?php

declare(strict_types=1);

namespace Aero\Assistant\Operations;

use Aero\Assistant\Data\SchemaCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Turns a normalised rules map + a resolved operation + any values the user
 * mentioned into a `form` generative-UI block: typed fields, dropdown options
 * pulled live from the referenced tables, and pre-filled values (resolving
 * names → ids). The block posts to the operation's REAL route, so the app's
 * validation, HRMAC and audit run untouched on submit.
 */
class FormSpecBuilder
{
    /** Long-text field names that should render as a textarea. */
    private const LONGTEXT = ['reason', 'description', 'notes', 'note', 'body', 'message', 'content', 'address', 'comment', 'comments', 'remarks', 'summary', 'bio'];

    public function __construct(private SchemaCatalog $schema) {}

    /**
     * @param  array<string,array{required:bool,rules:array<int,string>}>  $rules
     * @param  array<string,mixed>  $op  operation descriptor from OperationResolver
     * @param  array<string,mixed>  $values  natural values the user supplied
     * @return array<string,mixed>  a `form` block (empty-fields form is caller's concern)
     */
    public function build(array $rules, array $op, array $values = []): array
    {
        $fields = [];
        foreach ($rules as $name => $spec) {
            $field = $this->field($name, $spec);
            if ($field === null) {
                continue;
            }
            $prefill = $this->prefill($name, $field, $values);
            if ($prefill !== null) {
                $field['value'] = $prefill;
            }
            $fields[] = $field;
        }

        $uri = $this->fillUri((string) $op['uri'], (array) ($op['params'] ?? []), $values);

        return [
            'type' => 'form',
            'entity' => $op['entity'] ?? 'record',
            'title' => $op['label'] ?? 'New record',
            'action' => $uri,
            'method' => strtolower((string) ($op['http'] ?? 'post')),
            'kind' => $op['kind'] ?? 'create',
            'submit_label' => $this->submitLabel((string) ($op['kind'] ?? 'create')),
            'note' => 'Runs the real endpoint — your permissions, validation and audit all apply.',
            'fields' => $fields,
        ];
    }

    /**
     * @param  array{required:bool,rules:array<int,string>}  $spec
     * @return array<string,mixed>|null
     */
    private function field(string $name, array $spec): array
    {
        $rules = $spec['rules'];
        $has = fn (string $r) => in_array($r, $rules, true);
        $ruleWith = function (string $prefix) use ($rules): ?string {
            foreach ($rules as $r) {
                if (Str::startsWith($r, $prefix)) {
                    return $r;
                }
            }

            return null;
        };

        $field = [
            'name' => $name,
            'label' => Str::headline(preg_replace('/_id$/', '', $name)),
            'required' => $spec['required'],
        ];

        if ($existsRule = $ruleWith('exists:')) {
            [$table, $col] = $this->parseExists($existsRule, $name);
            $field['type'] = 'select';
            $field['options'] = $this->optionsForTable($table, $col);
            $field['_table'] = $table;
            $field['_col'] = $col;

            return $field;
        }
        if ($inRule = $ruleWith('in:')) {
            $field['type'] = 'select';
            $field['options'] = array_map(
                static fn ($v) => ['value' => $v, 'label' => Str::headline((string) $v)],
                array_filter(explode(',', Str::after($inRule, 'in:')), static fn ($v) => $v !== '')
            );

            return $field;
        }
        if ($has('boolean')) {
            $field['type'] = 'toggle';

            return $field;
        }
        if ($has('date') || $ruleWith('date_format') || Str::endsWith($name, '_date') || $name === 'date') {
            $field['type'] = 'date';

            return $field;
        }
        if ($has('email') || str_contains($name, 'email')) {
            $field['type'] = 'email';

            return $field;
        }
        if ($has('integer') || $has('numeric')) {
            $field['type'] = 'number';

            return $field;
        }
        if ($has('array')) {
            // arrays / nested payloads aren't reliably formable — skip so we
            // never submit a malformed value.
            return null;
        }

        // string-ish
        $max = $ruleWith('max:');
        $maxlen = $max ? (int) Str::after($max, 'max:') : null;
        $isLong = in_array($name, self::LONGTEXT, true) || ($maxlen !== null && $maxlen > 255);
        $field['type'] = $isLong ? 'textarea' : 'text';
        if ($maxlen) {
            $field['maxlength'] = $maxlen;
        }

        return $field;
    }

    /**
     * Options for a foreign-key select: value = referenced column, label = a
     * human column (name/title/…) or the related user's name, else "#id".
     *
     * @return array<int,array{value:mixed,label:string}>
     */
    private function optionsForTable(string $table, string $valueCol): array
    {
        try {
            if (! $this->schema->entity($table)) {
                return [];
            }
            $cols = Schema::getColumnListing($table);
            $labelCol = null;
            foreach (['name', 'title', 'display_name', 'label', 'code', 'reference', 'number'] as $c) {
                if (in_array($c, $cols, true)) {
                    $labelCol = $c;
                    break;
                }
            }
            $q = DB::table($table);
            if (in_array('deleted_at', $cols, true)) {
                $q->whereNull($table.'.deleted_at');
            }

            if ($labelCol) {
                $rows = $q->select($table.'.'.$valueCol.' as v', $table.'.'.$labelCol.' as l')
                    ->orderBy($table.'.'.$labelCol)->limit(300)->get();
            } elseif (in_array('user_id', $cols, true) && Schema::hasColumn('users', 'name')) {
                $rows = $q->join('users', 'users.id', '=', $table.'.user_id')
                    ->select($table.'.'.$valueCol.' as v', 'users.name as l')
                    ->orderBy('users.name')->limit(300)->get();
            } else {
                $rows = $q->select($table.'.'.$valueCol.' as v')->orderBy($table.'.'.$valueCol)->limit(300)->get();
            }

            $out = [];
            foreach ($rows as $r) {
                $out[] = ['value' => $r->v, 'label' => (string) ($r->l ?? ('#'.$r->v))];
            }

            return $out;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Resolve a user-supplied value for a field into a submittable value:
     * for FK selects, match a name/label to its id; otherwise pass through.
     *
     * @param  array<string,mixed>  $field
     * @param  array<string,mixed>  $values
     */
    private function prefill(string $name, array $field, array $values): mixed
    {
        $raw = $this->matchValue($name, $field, $values);
        if ($raw === null || $raw === '') {
            return null;
        }

        if (($field['type'] ?? '') === 'select' && ! empty($field['options'])) {
            // already a valid option value?
            foreach ($field['options'] as $o) {
                if ((string) $o['value'] === (string) $raw) {
                    return $o['value'];
                }
            }
            // match by label (case-insensitive contains)
            $needle = Str::lower((string) $raw);
            foreach ($field['options'] as $o) {
                if (str_contains(Str::lower($o['label']), $needle)) {
                    return $o['value'];
                }
            }

            return null; // couldn't resolve — leave for the user to pick
        }

        if (($field['type'] ?? '') === 'toggle') {
            return in_array(Str::lower((string) $raw), ['1', 'true', 'yes', 'on'], true);
        }

        return $raw;
    }

    /**
     * Find the user's value for a field by exact name or a friendly alias
     * (employee → employee_id, "from"/"start" → start_date, etc.).
     *
     * @param  array<string,mixed>  $field
     * @param  array<string,mixed>  $values
     */
    private function matchValue(string $name, array $field, array $values): mixed
    {
        if (array_key_exists($name, $values)) {
            return $values[$name];
        }
        $bare = preg_replace('/_id$/', '', $name);
        if ($bare !== $name && array_key_exists($bare, $values)) {
            return $values[$bare];
        }
        $aliases = [
            'start_date' => ['start', 'from', 'from_date', 'begin', 'date'],
            'end_date' => ['end', 'to', 'to_date', 'until', 'till'],
            'employee_id' => ['employee', 'person', 'staff', 'name', 'for'],
            'leave_type_id' => ['leave_type', 'type'],
        ];
        foreach ($aliases[$name] ?? [] as $a) {
            if (array_key_exists($a, $values)) {
                return $values[$a];
            }
        }

        return null;
    }

    /** @return array{0:string,1:string} [table, column] */
    private function parseExists(string $rule, string $field): array
    {
        $spec = Str::after($rule, 'exists:');
        $parts = explode(',', $spec);
        $table = trim($parts[0]);
        // handle "Connection.table"
        if (str_contains($table, '.')) {
            $table = Str::afterLast($table, '.');
        }
        $col = isset($parts[1]) && trim($parts[1]) !== '' && trim($parts[1]) !== 'NULL' ? trim($parts[1]) : 'id';

        return [$table, $col];
    }

    /**
     * @param  array<int,string>  $params
     * @param  array<string,mixed>  $values
     */
    private function fillUri(string $uri, array $params, array $values): string
    {
        foreach ($params as $p) {
            $val = $values[$p] ?? $values[$p.'_id'] ?? $values['id'] ?? null;
            $uri = preg_replace('/\{'.preg_quote($p, '/').'\??\}/', (string) ($val ?? ''), $uri);
        }

        return $uri;
    }

    private function submitLabel(string $kind): string
    {
        return match ($kind) {
            'update' => 'Save changes',
            'delete' => 'Confirm delete',
            'action' => 'Confirm',
            default => 'Create',
        };
    }
}
