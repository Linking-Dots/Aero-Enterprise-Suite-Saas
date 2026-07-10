<?php

declare(strict_types=1);

namespace Aero\Assistant\Data;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Auto-discovers every data table + its real columns straight from the live
 * database schema — so Aeon can query ANY table dynamically, with zero
 * per-table code and no fragile model loading. New tables appear automatically.
 *
 * The query layer only ever runs validated, parameterised, read-only queries on
 * the current (tenant) connection; sensitive columns (credentials, PII, encrypted
 * fields) are flagged so raw values are never listed. System/plumbing tables are
 * excluded so Aeon only sees real business data.
 */
class SchemaCatalog
{
    private const SENSITIVE = [
        // credentials / tokens
        'password', 'remember_token', 'api_key', 'secret', 'two_factor', 'byoc_db_', 'access_token', 'refresh_token',
        // government / identity PII
        'national_id', 'tax_id', 'ssn', 'passport', 'nid', 'date_of_birth', 'dob', 'birth_date',
        // financial / banking
        'account_number', 'routing_number', 'iban', 'swift', 'card_number', 'cvv', 'bank_',
        // compensation (aggregating/listing pay across the org without authorisation)
        'salary', 'basic_salary', 'gross', 'net_pay', 'net_salary', 'compensation', 'ctc', 'wage',
        'hourly_rate', 'pay_rate', 'allowance', 'deduction',
        // health / private
        'medical_notes', 'medical', 'diagnosis', 'emergency_contact',
    ];

    private const SYSTEM_TABLES = [
        'migrations', 'password_reset_tokens', 'password_resets', 'failed_jobs', 'jobs',
        'job_batches', 'cache', 'cache_locks', 'sessions', 'personal_access_tokens',
        'model_has_permissions', 'model_has_roles', 'role_has_permissions',
    ];

    private const SYSTEM_PREFIXES = ['telescope_', 'pulse_', 'aeon_'];

    /** @var array<string,array{table:string,label:string,columns:array<int,string>,date_fields:array<int,string>,soft_delete:bool}>|null */
    private ?array $catalog = null;

    /** @return array<string,array{table:string,label:string,columns:array<int,string>,date_fields:array<int,string>,soft_delete:bool}> */
    public function all(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $out = [];
        foreach ($this->tables() as $table) {
            if ($this->isSystemTable($table)) {
                continue;
            }
            try {
                $columns = Schema::getColumnListing($table);
            } catch (\Throwable) {
                continue;
            }
            if (empty($columns)) {
                continue;
            }
            $out[$table] = [
                'table' => $table,
                'label' => Str::headline(Str::singular($table)),
                'columns' => array_values($columns),
                'date_fields' => array_values(array_filter(
                    $columns,
                    static fn ($c) => Str::endsWith($c, ['_at', '_date']) || in_array($c, ['date'], true),
                )),
                'soft_delete' => in_array('deleted_at', $columns, true),
            ];
        }

        ksort($out);

        return $this->catalog = $out;
    }

    /** @return array{table:string,label:string,columns:array<int,string>,date_fields:array<int,string>,soft_delete:bool}|null */
    public function entity(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function isColumn(string $entity, string $column): bool
    {
        $e = $this->entity($entity);

        return $e !== null && in_array($column, $e['columns'], true);
    }

    public function isSensitive(string $column): bool
    {
        foreach (self::SENSITIVE as $s) {
            if (Str::contains($column, $s)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The module (code) that owns a table, resolved by module-code prefix
     * against the live module registry (config/module.php files). Unprefixed
     * tables (users, departments, audit_logs, …) belong to 'core'.
     */
    public function moduleFor(string $table): string
    {
        $best = 'core';
        $bestLen = 0;
        foreach ((array) config('modules', []) as $m) {
            $code = is_array($m) ? (string) ($m['code'] ?? '') : '';
            if ($code !== '' && Str::startsWith($table, $code.'_') && strlen($code) > $bestLen) {
                $best = $code;
                $bestLen = strlen($code);
            }
        }

        return $best;
    }

    /**
     * Table names in the CURRENT database only. getTableListing() can return
     * schema-qualified names spanning every database on the server (e.g. on a
     * shared MySQL) — scope to this connection's DB and strip the prefix.
     *
     * @return array<int,string>
     */
    private function tables(): array
    {
        $db = null;
        try {
            $db = DB::connection()->getDatabaseName();
        } catch (\Throwable) {
        }

        $raw = [];
        try {
            $raw = Schema::getTableListing();
        } catch (\Throwable) {
            try {
                $raw = array_map(static fn ($t) => is_array($t) ? ($t['name'] ?? '') : (string) $t, Schema::getTables());
            } catch (\Throwable) {
                $raw = [];
            }
        }

        $out = [];
        foreach ($raw as $t) {
            $t = (string) $t;
            if (str_contains($t, '.')) {
                [$schema, $name] = explode('.', $t, 2);
                if ($db !== null && $schema !== $db) {
                    continue; // a different database on the same server — skip
                }
                $t = $name;
            }
            if ($t !== '') {
                $out[$t] = $t;
            }
        }

        return array_values($out);
    }

    private function isSystemTable(string $table): bool
    {
        if (in_array($table, self::SYSTEM_TABLES, true)) {
            return true;
        }
        foreach (self::SYSTEM_PREFIXES as $p) {
            if (Str::startsWith($table, $p)) {
                return true;
            }
        }

        return false;
    }
}
