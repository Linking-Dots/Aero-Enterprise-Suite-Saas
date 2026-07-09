<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Command-centre payload for the platform Users surface — normalised staff rows
 * (roles + 2FA + lock + login state resolved), KPI stats, role distribution and
 * sparklines, plus per-user drawer detail (sessions + activity). Mirrors the
 * Tenants / Plans command centres.
 *
 * Read-only and platform-scoped; every write (create/update/delete/toggle/
 * impersonate/bulk) continues to run through the shared aero-auth
 * UserAdminController. Honest: 2FA / lock / login figures come straight from the
 * users table columns — nothing is fabricated.
 */
class PlatformUserService
{
    /**
     * @return array{stats: array, users: array, roleDist: array, sparks: array}
     */
    public function overview(): array
    {
        $users = User::withTrashed()
            ->with('roles:id,name')
            ->orderByDesc('id')
            ->get();

        $rows = $users->map(function (User $u) {
            $locked = $u->account_locked_at !== null;
            $inactive = $u->trashed();
            $status = $locked ? 'locked' : ($inactive ? 'inactive' : 'active');

            return [
                'id'            => $u->id,
                'name'          => $u->name ?: ($u->user_name ?: '—'),
                'email'         => $u->email,
                'avatar_url'    => $u->avatar_url,
                'roles'         => $u->roles->pluck('name')->values()->all(),
                'status'        => $status,
                'tfa'           => $u->two_factor_confirmed_at !== null,
                'force_reset'   => (bool) $u->force_password_reset,
                'last_login_at' => $u->last_login_at ? Carbon::parse($u->last_login_at)->toIso8601String() : null,
                'last_login_ip' => $u->last_login_ip,
                'login_count'   => (int) $u->login_count,
                'locked_reason' => $u->locked_reason,
                'created_at'    => optional($u->created_at)->toIso8601String(),
            ];
        })->all();

        return [
            'stats'    => $this->overviewStats($rows),
            'users'    => $rows,
            'roleDist' => $this->buildRoleDist($rows),
            'sparks'   => $this->buildSparks($users),
        ];
    }

    /**
     * @param  array<int, array>  $rows
     * @return array<string, int>
     */
    private function overviewStats(array $rows): array
    {
        $count = fn (callable $p) => count(array_filter($rows, $p));
        $total = max(1, count($rows));
        $tfaOn = $count(fn ($r) => $r['tfa']);

        return [
            'total'          => count($rows),
            'active'         => $count(fn ($r) => $r['status'] === 'active'),
            'inactive'       => $count(fn ($r) => $r['status'] === 'inactive'),
            'locked'         => $count(fn ($r) => $r['status'] === 'locked'),
            'tfa_on'         => $tfaOn,
            'tfa_pct'        => (int) round($tfaOn / $total * 100),
            'admins'         => $count(fn ($r) => $this->isAdmin($r)),
            'never_logged'   => $count(fn ($r) => $r['last_login_at'] === null),
            'needs_attention' => $count(fn ($r) => ! $r['tfa'] || $r['status'] !== 'active' || $r['last_login_at'] === null),
            'logins'         => array_sum(array_map(fn ($r) => $r['login_count'], $rows)),
            'roles'          => DB::table('roles')->where('guard_name', 'landlord')->count(),
        ];
    }

    /** Elevated access = the two platform-admin roles (Support/Billing/Auditor are scoped). */
    private function isAdmin(array $row): bool
    {
        foreach ($row['roles'] as $r) {
            if ($r === 'Super Platform Admin' || $r === 'Platform Admin') {
                return true;
            }
        }

        return false;
    }

    /**
     * Staff-per-role distribution across landlord roles (roles with zero staff
     * are still shown so the catalogue reads completely).
     *
     * @param  array<int, array>  $rows
     * @return array<int, array{role: string, count: int}>
     */
    private function buildRoleDist(array $rows): array
    {
        $counts = [];
        foreach (DB::table('roles')->where('guard_name', 'landlord')->orderBy('name')->pluck('name') as $name) {
            $counts[$name] = 0;
        }
        foreach ($rows as $r) {
            foreach ($r['roles'] as $role) {
                $counts[$role] = ($counts[$role] ?? 0) + 1;
            }
        }
        arsort($counts);

        return array_map(fn ($name, $c) => ['role' => $name, 'count' => $c], array_keys($counts), array_values($counts));
    }

    /**
     * KPI sparklines (6 months): cumulative staff count and cumulative 2FA-enabled
     * count by account-creation month. Real derivation from users.created_at.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return array{staff: array<int,int>, tfa: array<int,int>}
     */
    private function buildSparks($users): array
    {
        $end = now()->startOfMonth();
        $months = array_map(fn ($i) => $end->copy()->subMonths($i)->endOfMonth(), range(5, 0));

        $staff = [];
        $tfa = [];
        foreach ($months as $cut) {
            $s = 0;
            $t = 0;
            foreach ($users as $u) {
                if ($u->created_at !== null && Carbon::parse($u->created_at)->lte($cut)) {
                    $s++;
                    if ($u->two_factor_confirmed_at !== null) {
                        $t++;
                    }
                }
            }
            $staff[] = $s;
            $tfa[] = $t;
        }

        return ['staff' => $staff, 'tfa' => $tfa];
    }

    /**
     * Drawer detail: active sessions and audit activity for one staff account.
     * Guarded so a missing table can't 500 the drawer.
     *
     * @return array{sessions: array, activity: array}
     */
    public function detail(int $userId): array
    {
        $sessions = [];
        try {
            $sessions = DB::table('sessions')
                ->where('user_id', $userId)
                ->orderByDesc('last_activity')
                ->limit(20)
                ->get(['ip_address', 'user_agent', 'last_activity'])
                ->map(fn ($s) => [
                    'ip'         => $s->ip_address,
                    'agent'      => $this->shortAgent($s->user_agent),
                    'last_active' => $s->last_activity ? Carbon::createFromTimestamp((int) $s->last_activity)->toIso8601String() : null,
                ])->all();
        } catch (\Illuminate\Database\QueryException) {
            // sessions table absent
        }

        $activity = [];
        try {
            [$conn, $table] = (is_saas_mode() && ! (function_exists('tenancy') && tenancy()->initialized))
                ? [central_connection(), 'platform_audit_logs']
                : [null, 'audit_logs'];

            $activity = DB::connection($conn)->table($table)
                ->where(function ($q) use ($userId) {
                    $q->where(fn ($w) => $w->where('subject_type', 'like', '%User%')->where('subject_id', (string) $userId))
                        ->orWhere('actor_id', $userId);
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['event_type', 'action', 'description', 'actor_name', 'created_at'])
                ->map(fn ($a) => [
                    'event'  => $a->event_type,
                    'action' => $a->action,
                    'detail' => $a->description,
                    'actor'  => $a->actor_name,
                    'at'     => $a->created_at,
                ])->all();
        } catch (\Illuminate\Database\QueryException) {
            // audit table absent
        }

        return ['sessions' => $sessions, 'activity' => $activity];
    }

    /**
     * All staff flattened for CSV export.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(): array
    {
        return array_map(fn ($r) => [
            'name'        => $r['name'],
            'email'       => $r['email'],
            'roles'       => implode(' | ', $r['roles']),
            'status'      => $r['status'],
            'two_factor'  => $r['tfa'] ? 'on' : 'off',
            'last_login'  => $r['last_login_at'] ?? '',
            'logins'      => $r['login_count'],
            'joined'      => $r['created_at'] ?? '',
        ], $this->overview()['users']);
    }

    private function shortAgent(?string $ua): string
    {
        if (! $ua) {
            return 'Unknown device';
        }
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            default => '',
        };

        return trim("{$browser} · {$os}", ' ·');
    }
}
