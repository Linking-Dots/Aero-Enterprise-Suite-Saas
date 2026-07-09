<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregation for the Onboarding command centre — the full tenant
 * lifecycle console (approvals, provisioning, trials, conversion) at
 * /onboarding. Composes real tenant + subscription state; every figure is
 * derived, never fabricated.
 */
class OnboardingAdminService
{
    /** @return array<string, mixed> */
    public function overview(): array
    {
        $conn = central_connection();
        $now = Carbon::now();

        $statusCounts = DB::connection($conn)->table('tenants')
            ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $count = fn (string $s) => (int) ($statusCounts[$s] ?? 0);

        // Trialing subscriptions → tenants (any tenant status). The legacy
        // trials() query required tenant status = active, which excluded the
        // real 'trial'-status tenants; this is the corrected definition.
        $trialRows = $this->trialRows($conn, $now);
        $expiringSoon = count(array_filter($trialRows, fn ($t) => $t['days_left'] !== null && $t['days_left'] >= 0 && $t['days_left'] <= 7));

        $monthStart = $now->copy()->startOfMonth()->toDateTimeString();
        $registered = (int) DB::connection($conn)->table('tenants')->where('created_at', '>=', $monthStart)->count();
        $provisioned = (int) DB::connection($conn)->table('tenants')->where('created_at', '>=', $monthStart)->where('status', 'active')->count();
        $subscribed = (int) DB::connection($conn)->table('tenants as t')->where('t.created_at', '>=', $monthStart)
            ->whereExists(fn ($q) => $q->from('subscriptions as s')->whereColumn('s.tenant_id', 't.id')->where('s.status', 'active'))->count();

        return [
            'kpis' => [
                'pending'        => $count('pending'),
                'provisioning'   => $count('provisioning'),
                'failed'         => $count('failed'),
                'active_trials'  => count($trialRows),
                'expiring_soon'  => $expiringSoon,
                'suspended'      => $count('suspended'),
                'archived'       => $count('archived'),
                'active'         => $count('active'),
                'new_this_month' => $registered,
                'conversion_pct' => $registered > 0 ? (int) round($subscribed / $registered * 100) : 0,
            ],
            'funnel' => [
                ['stage' => 'Registered', 'count' => $registered],
                ['stage' => 'Provisioned', 'count' => $provisioned],
                ['stage' => 'Subscribed', 'count' => $subscribed],
            ],
            'trend'       => $this->weeklyTrend($conn, $now),
            'status_dist' => $this->statusDist($statusCounts),
            'queues' => [
                'pending'      => $this->pendingRows($conn),
                'provisioning' => $this->provisioningRows($conn),
                'trials'       => array_slice($trialRows, 0, 6),
            ],
            'tenants'    => $this->lifecycleRows($conn),
            'automation' => $this->automationRules(),
            'settings'   => $this->settings(),
            'templates'  => $this->emailTemplates(),
            'plans'      => DB::connection($conn)->table('plans')->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'price_monthly'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price_monthly' => (float) $p->price_monthly])->all(),
        ];
    }

    /**
     * Payload for the dedicated Onboarding Settings editor: registration policy,
     * automation rules and lifecycle email templates.
     *
     * @return array<string, mixed>
     */
    public function settingsPayload(): array
    {
        $conn = central_connection();

        return [
            'settings'   => $this->settings(),
            'automation' => $this->automationRules(),
            'templates'  => $this->emailTemplates(),
            'plans'      => DB::connection($conn)->table('plans')->where('is_active', true)->orderBy('sort_order')
                ->get(['id', 'name', 'trial_days'])->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'trial_days' => (int) ($p->trial_days ?? 0)])->all(),
        ];
    }

    /**
     * Per-tenant drawer detail — activity from the central audit trail plus the
     * lifecycle reason data stored on tenants.data. Guarded.
     *
     * @return array<string, mixed>
     */
    public function detail(string $tenantId): array
    {
        $conn = central_connection();
        $tenant = DB::connection($conn)->table('tenants')->where('id', $tenantId)->first();
        abort_if($tenant === null, 404);

        $activity = [];
        try {
            $activity = DB::connection($conn)->table('platform_audit_logs')
                ->where('subject_type', Tenant::class)->where('subject_id', $tenantId)
                ->orderByDesc('created_at')->limit(15)
                ->get(['event_type', 'action', 'description', 'actor_name', 'created_at'])
                ->map(fn ($a) => [
                    'message' => $a->description ?: ($a->action ?: $a->event_type),
                    'actor'   => $a->actor_name,
                    'at'      => $a->created_at,
                ])->all();
        } catch (QueryException) {
            // audit table absent in this context
        }

        $data = $this->decodeData($tenant->data ?? null);

        return [
            'id'          => (string) $tenant->id,
            'name'        => $tenant->name,
            'email'       => $tenant->email,
            'subdomain'   => $tenant->subdomain,
            'status'      => $tenant->status,
            'verified'    => $tenant->company_email_verified_at !== null,
            'reg_step'    => $tenant->registration_step,
            'prov_step'   => $tenant->provisioning_step,
            'created_at'  => $tenant->created_at,
            'reasons'     => array_filter([
                'rejection'   => $data['rejection_reason'] ?? null,
                'suspension'  => $data['suspension_reason'] ?? null,
                'archive'     => $data['archive_reason'] ?? null,
                'cancellation' => $data['trial_cancellation_reason'] ?? null,
            ]),
            'activity'    => $activity,
        ];
    }

    /* ---------------- pieces ---------------- */

    /** @return array<int, array<string, mixed>> */
    private function trialRows(string $conn, Carbon $now): array
    {
        return DB::connection($conn)->table('subscriptions as s')
            ->join('tenants as t', 't.id', '=', 's.tenant_id')
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.status', 'trialing')->whereNotNull('s.trial_ends_at')
            ->whereNotIn('t.status', ['archived', 'cancelled'])
            ->orderBy('s.trial_ends_at')
            ->get(['t.id', 't.name', 't.status', 'p.name as plan', 's.trial_ends_at'])
            ->map(fn ($r) => [
                'id'         => (string) $r->id,
                'tenant'     => $r->name,
                'plan'       => $r->plan ?: 'No plan',
                'trial_ends' => $r->trial_ends_at,
                'days_left'  => $r->trial_ends_at ? (int) round($now->diffInDays(Carbon::parse($r->trial_ends_at), false)) : null,
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function pendingRows(string $conn): array
    {
        return DB::connection($conn)->table('tenants')
            ->where('status', 'pending')->orderByDesc('created_at')->limit(6)
            ->get(['id', 'name', 'email', 'company_email_verified_at', 'created_at'])
            ->map(fn ($t) => [
                'id'       => (string) $t->id,
                'tenant'   => $t->name,
                'email'    => $t->email,
                'verified' => $t->company_email_verified_at !== null,
                'ago'      => $t->created_at ? Carbon::parse($t->created_at)->diffForHumans() : '—',
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function provisioningRows(string $conn): array
    {
        return DB::connection($conn)->table('tenants')
            ->whereIn('status', ['provisioning', 'failed'])->orderByDesc('updated_at')->limit(6)
            ->get(['id', 'name', 'status', 'provisioning_step', 'subdomain', 'updated_at'])
            ->map(fn ($t) => [
                'id'      => (string) $t->id,
                'tenant'  => $t->name,
                'status'  => $t->status,
                'step'    => $t->status === 'failed'
                    ? 'Failed · '.str_replace(['failed:', '_'], ['', ' '], (string) ($t->provisioning_step ?? 'unknown'))
                    : ucfirst(str_replace('_', ' ', (string) ($t->provisioning_step ?? 'queued'))),
                'ago'     => $t->updated_at ? Carbon::parse($t->updated_at)->diffForHumans() : '—',
            ])->all();
    }

    /**
     * The full lifecycle workbench: every tenant still in the onboarding
     * pipeline (not plainly active), newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lifecycleRows(string $conn): array
    {
        return DB::connection($conn)->table('tenants as t')
            ->leftJoin('subscriptions as s', function ($j) {
                $j->on('s.tenant_id', '=', 't.id')->whereIn('s.status', ['trialing', 'active']);
            })
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->whereIn('t.status', ['pending', 'provisioning', 'failed', 'trial', 'suspended', 'archived'])
            ->orderByDesc('t.created_at')
            ->limit(200)
            ->get(['t.id', 't.name', 't.email', 't.subdomain', 't.status', 't.company_email_verified_at',
                't.registration_step', 't.provisioning_step', 't.created_at', 'p.name as plan', 's.trial_ends_at'])
            ->unique('id')->values()
            ->map(fn ($t) => [
                'id'         => (string) $t->id,
                'tenant'     => $t->name,
                'email'      => $t->email,
                'subdomain'  => $t->subdomain,
                'status'     => $t->status,
                'verified'   => $t->company_email_verified_at !== null,
                'reg_step'   => $t->registration_step,
                'prov_step'  => $t->provisioning_step,
                'plan'       => $t->plan ?: '—',
                'trial_ends' => $t->trial_ends_at,
                'created_at' => $t->created_at,
            ])->all();
    }

    /** @return array{labels: array, counts: array} */
    private function weeklyTrend(string $conn, Carbon $now): array
    {
        $rows = DB::connection($conn)->table('tenants')
            ->where('created_at', '>=', $now->copy()->subWeeks(7)->startOfWeek()->toDateTimeString())
            ->get(['created_at']);
        $buckets = [];
        for ($i = 7; $i >= 0; $i--) {
            $buckets[$now->copy()->subWeeks($i)->startOfWeek()->format('Y-m-d')] = 0;
        }
        foreach ($rows as $r) {
            $k = Carbon::parse($r->created_at)->startOfWeek()->format('Y-m-d');
            if (isset($buckets[$k])) {
                $buckets[$k]++;
            }
        }

        return [
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('M j'), array_keys($buckets)),
            'counts' => array_values($buckets),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $counts
     * @return array<int, array{status: string, label: string, count: int}>
     */
    private function statusDist($counts): array
    {
        $order = ['active' => 'Active', 'trial' => 'Trial', 'pending' => 'Pending', 'provisioning' => 'Provisioning', 'failed' => 'Failed', 'suspended' => 'Suspended', 'archived' => 'Archived', 'cancelled' => 'Cancelled'];
        $out = [];
        foreach ($order as $key => $label) {
            $c = (int) ($counts[$key] ?? 0);
            if ($c > 0) {
                $out[] = ['status' => $key, 'label' => $label, 'count' => $c];
            }
        }

        return $out;
    }

    /**
     * Onboarding preferences live in the PlatformSetting singleton's
     * admin_preferences JSON bag (there is no key/value settings table).
     *
     * @return array<string, mixed>
     */
    private function prefs(): array
    {
        try {
            return (array) (PlatformSetting::current()->admin_preferences ?? []);
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function automationRules(): array
    {
        $prefs = $this->prefs();
        $rules = [
            ['id' => 'auto_approve_verified', 'name' => 'Auto-approve verified registrations', 'desc' => 'On email verification complete', 'default' => false],
            ['id' => 'trial_expiry_reminder', 'name' => 'Trial expiry reminder', 'desc' => '7, 3 & 1 days before expiry', 'default' => true],
            ['id' => 'cleanup_abandoned', 'name' => 'Cleanup abandoned registrations', 'desc' => 'Pending >30 days → archive', 'default' => false],
            ['id' => 'welcome_sequence', 'name' => 'Welcome email sequence', 'desc' => 'Tips over the first 7 days', 'default' => true],
            ['id' => 'failed_provisioning_alert', 'name' => 'Failed provisioning alert', 'desc' => 'Notify admin on failure', 'default' => true],
        ];

        return array_map(fn ($r) => [
            'id'     => $r['id'],
            'name'   => $r['name'],
            'desc'   => $r['desc'],
            'active' => (bool) data_get($prefs, "onboarding.automation.{$r['id']}", $r['default']),
        ], $rules);
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        $prefs = $this->prefs();
        $get = fn (string $key, mixed $def) => data_get($prefs, "onboarding.{$key}", $def);

        return [
            'default_trial_days'       => (int) $get('default_trial_days', 14),
            'require_email_verification' => (bool) $get('require_email_verification', true),
            'require_phone_verification' => (bool) $get('require_phone_verification', false),
            'require_manual_approval'  => (bool) $get('require_manual_approval', false),
            'enable_captcha'           => (bool) $get('enable_captcha', true),
            'max_registrations_per_ip' => (int) $get('max_registrations_per_ip', 5),
            'blocked_domains'          => (string) $get('blocked_domains', 'tempmail.com,throwaway.com'),
        ];
    }

    /** @return array<int, array<string, string>> */
    private function emailTemplates(): array
    {
        return [
            ['id' => 'welcome', 'name' => 'Welcome Email', 'desc' => 'After registration'],
            ['id' => 'email_verification', 'name' => 'Email Verification', 'desc' => 'Verify address'],
            ['id' => 'trial_started', 'name' => 'Trial Started', 'desc' => 'Trial begins'],
            ['id' => 'trial_expiring', 'name' => 'Trial Expiring', 'desc' => '7 / 3 / 1 days before'],
            ['id' => 'trial_expired', 'name' => 'Trial Expired', 'desc' => 'After trial ends'],
            ['id' => 'provisioning_complete', 'name' => 'Setup Complete', 'desc' => 'Provisioning done'],
        ];
    }

    private function decodeData(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
