<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Marketing;

use Aero\Platform\Models\Affiliate;
use Aero\Platform\Models\AffiliatePayout;
use Aero\Platform\Models\AffiliateReferral;
use Aero\Platform\Models\PlatformSetting;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

/**
 * Affiliate Service
 *
 * Manages affiliate/referral program.
 */
class AffiliateService
{
    protected const COOKIE_NAME = 'aero_ref';

    /**
     * Get paginated affiliates.
     */
    public function getPaginatedAffiliates(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Affiliate::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Create an affiliate application.
     */
    public function createAffiliate(array $data): Affiliate
    {
        $settings = PlatformSetting::current()->getAffiliateSettings();

        $affiliate = Affiliate::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'website' => $data['website'] ?? null,
            'commission_rate' => $settings['default_commission_rate'] ?? 10.00,
            'commission_type' => $settings['default_commission_type'] ?? 'percentage',
            'cookie_days' => $settings['cookie_days'] ?? 30,
            'minimum_payout' => $settings['minimum_payout'] ?? 50.00,
            'status' => ($settings['auto_approve_affiliates'] ?? false)
                ? Affiliate::STATUS_APPROVED
                : Affiliate::STATUS_PENDING,
        ]);

        if ($affiliate->isApproved()) {
            $affiliate->update(['approved_at' => now()]);
        }

        return $affiliate;
    }

    /**
     * Track a referral click.
     */
    public function trackReferralClick(string $referralCode): ?AffiliateReferral
    {
        $affiliate = Affiliate::findByCode($referralCode);

        if (! $affiliate) {
            return null;
        }

        $referral = $affiliate->recordReferral([
            'visitor_id' => $this->getVisitorId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer_url' => request()->headers->get('referer'),
            'landing_page' => request()->fullUrl(),
            'utm_data' => [
                'utm_source' => request()->get('utm_source'),
                'utm_medium' => request()->get('utm_medium'),
                'utm_campaign' => request()->get('utm_campaign'),
            ],
        ]);

        // Set referral cookie
        $cookieDays = $affiliate->cookie_days;
        Cookie::queue(self::COOKIE_NAME, $referralCode, $cookieDays * 24 * 60);

        return $referral;
    }

    /**
     * Get affiliate from cookie.
     */
    public function getAffiliateFromCookie(): ?Affiliate
    {
        $code = Cookie::get(self::COOKIE_NAME);

        if (! $code) {
            return null;
        }

        return Affiliate::findByCode($code);
    }

    /**
     * Get referral from cookie/session.
     */
    public function getCurrentReferral(): ?AffiliateReferral
    {
        $affiliate = $this->getAffiliateFromCookie();

        if (! $affiliate) {
            return null;
        }

        $visitorId = $this->getVisitorId();

        return AffiliateReferral::where('affiliate_id', $affiliate->id)
            ->where('visitor_id', $visitorId)
            ->whereIn('status', [AffiliateReferral::STATUS_CLICKED, AffiliateReferral::STATUS_REGISTERED])
            ->latest()
            ->first();
    }

    /**
     * Record registration from referral.
     */
    public function recordRegistration(string $email): ?AffiliateReferral
    {
        $referral = $this->getCurrentReferral();

        if (! $referral) {
            return null;
        }

        $referral->markAsRegistered($email);

        return $referral;
    }

    /**
     * Record conversion (subscription payment).
     */
    public function recordConversion(int $tenantId, float $transactionAmount): ?AffiliateReferral
    {
        // Find referral by tenant email or current session
        $referral = $this->getCurrentReferral();

        if (! $referral) {
            return null;
        }

        $referral->markAsConverted($tenantId, $transactionAmount);

        // Clear cookie after conversion
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));

        return $referral;
    }

    /**
     * Get affiliate statistics.
     */
    public function getAffiliateStats(?string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        return [
            'total_affiliates' => Affiliate::count(),
            'active_affiliates' => Affiliate::approved()->count(),
            'pending_affiliates' => Affiliate::pending()->count(),
            'total_referrals' => AffiliateReferral::where('created_at', '>=', $startDate)->count(),
            'conversions' => AffiliateReferral::where('created_at', '>=', $startDate)
                ->where('status', AffiliateReferral::STATUS_CONVERTED)
                ->count(),
            'total_revenue' => AffiliateReferral::where('created_at', '>=', $startDate)
                ->where('status', AffiliateReferral::STATUS_CONVERTED)
                ->sum('transaction_amount'),
            'total_commission' => AffiliateReferral::where('created_at', '>=', $startDate)
                ->where('status', AffiliateReferral::STATUS_CONVERTED)
                ->sum('commission_amount'),
            'pending_payouts' => Affiliate::sum('pending_earnings'),
        ];
    }

    /**
     * Get top affiliates.
     */
    public function getTopAffiliates(int $limit = 10, ?string $period = 'month'): Collection
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        return Affiliate::approved()
            ->withCount(['referrals as period_referrals' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }])
            ->withSum(['referrals as period_revenue' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                    ->where('status', AffiliateReferral::STATUS_CONVERTED);
            }], 'transaction_amount')
            ->orderByDesc('period_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending payouts.
     */
    public function getPendingPayouts(): Collection
    {
        return Affiliate::approved()
            ->where('pending_earnings', '>=', DB::raw('minimum_payout'))
            ->whereNotNull('payout_method')
            ->get();
    }

    /**
     * Create a payout.
     */
    public function createPayout(Affiliate $affiliate, ?float $amount = null): AffiliatePayout
    {
        return AffiliatePayout::createForAffiliate($affiliate, $amount);
    }

    /**
     * Process a payout.
     */
    public function processPayout(AffiliatePayout $payout): bool
    {
        $payout->markAsProcessing();

        // In real implementation, integrate with payment provider
        // For now, just mark as completed
        // $payout->markAsCompleted('TXN-' . time());

        return true;
    }

    /**
     * Get affiliate referrals.
     */
    public function getAffiliateReferrals(Affiliate $affiliate, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $affiliate->referrals();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['commission_status'])) {
            $query->where('commission_status', $filters['commission_status']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get affiliate payouts.
     */
    public function getAffiliatePayouts(Affiliate $affiliate, int $perPage = 15): LengthAwarePaginator
    {
        return $affiliate->payouts()->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Full command-centre payload: every affiliate (mapped), the payouts ledger,
     * program stats, KPI sparklines, status mix, leaderboard, payout queue,
     * earned-vs-paid trend, and the option/setting lists the console needs.
     */
    public function overview(string $period = 'quarter'): array
    {
        $affiliates = Affiliate::query()->orderByDesc('total_earnings')->get();
        $mapped = $affiliates->map(fn (Affiliate $a) => $this->mapAffiliate($a))->values()->all();

        $byStatus = $affiliates->groupBy('status')->map->count();
        $totalReferrals = AffiliateReferral::count();
        $conversions = AffiliateReferral::where('status', AffiliateReferral::STATUS_CONVERTED)->count();

        $stats = [
            'total' => $affiliates->count(),
            'approved' => (int) ($byStatus[Affiliate::STATUS_APPROVED] ?? 0),
            'pending' => (int) ($byStatus[Affiliate::STATUS_PENDING] ?? 0),
            'suspended' => (int) ($byStatus[Affiliate::STATUS_SUSPENDED] ?? 0),
            'rejected' => (int) ($byStatus[Affiliate::STATUS_REJECTED] ?? 0),
            'total_referrals' => $totalReferrals,
            'conversions' => $conversions,
            'conversion_rate' => $totalReferrals > 0 ? round($conversions / $totalReferrals * 100, 1) : 0.0,
            'revenue' => (float) AffiliateReferral::where('status', AffiliateReferral::STATUS_CONVERTED)->sum('transaction_amount'),
            'commission_earned' => (float) AffiliateReferral::where('status', AffiliateReferral::STATUS_CONVERTED)->sum('commission_amount'),
            'pending_commission' => (float) $affiliates->sum('pending_earnings'),
            'paid_ltd' => (float) $affiliates->sum('paid_earnings'),
        ];

        $statusMix = collect(Affiliate::getStatusOptions())
            ->map(fn ($label, $key) => ['status' => $key, 'label' => $label, 'count' => (int) ($byStatus[$key] ?? 0)])
            ->values()->all();

        $top = $affiliates->where('status', Affiliate::STATUS_APPROVED)
            ->sortByDesc('total_earnings')->take(6)
            ->map(fn (Affiliate $a) => [
                'id' => $a->id, 'name' => $a->name, 'company' => $a->company_name,
                'earnings' => (float) $a->total_earnings, 'conversions' => (int) $a->successful_referrals,
            ])->values()->all();

        $queue = $affiliates->filter(fn (Affiliate $a) => $a->isEligibleForPayout())
            ->map(fn (Affiliate $a) => [
                'id' => $a->id, 'name' => $a->name, 'pending' => (float) $a->pending_earnings,
                'payout_method' => $a->payout_method, 'minimum' => (float) $a->minimum_payout,
            ])->sortByDesc('pending')->values()->all();

        $payouts = AffiliatePayout::with('affiliate:id,name')->orderByDesc('created_at')->limit(200)->get()
            ->map(fn (AffiliatePayout $p) => [
                'id' => $p->id,
                'affiliate_id' => $p->affiliate_id,
                'affiliate' => $p->affiliate?->name,
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'payout_method' => $p->payout_method,
                'transaction_reference' => $p->transaction_reference,
                'created_at' => optional($p->created_at)->toIso8601String(),
                'processed_at' => optional($p->processed_at)->toIso8601String(),
                'completed_at' => optional($p->completed_at)->toIso8601String(),
            ])->values()->all();

        $refs = AffiliateReferral::query()->get(['status', 'created_at', 'converted_at', 'commission_amount', 'commission_paid_at']);
        $sparks = [
            'affiliates' => $this->weekly($affiliates, 'created_at'),
            'referrals' => $this->weekly($refs, 'created_at'),
            'conversions' => $this->weekly($refs->whereNotNull('converted_at'), 'converted_at'),
            'paid' => $this->weekly(collect($payouts)->filter(fn ($p) => $p['completed_at'])->map(fn ($p) => (object) ['completed_at' => Carbon::parse($p['completed_at'])]), 'completed_at'),
        ];

        return [
            'affiliates' => $mapped,
            'payouts' => $payouts,
            'stats' => $stats,
            'sparks' => $sparks,
            'statusMix' => $statusMix,
            'top' => $top,
            'queue' => $queue,
            'trend' => $this->earnedVsPaidTrend($refs, $payouts),
            'settings' => PlatformSetting::current()->getAffiliateSettings(),
            'statusOptions' => Affiliate::getStatusOptions(),
            'payoutMethodOptions' => Affiliate::getPayoutMethodOptions(),
        ];
    }

    /**
     * Map an affiliate to the shape the console consumes.
     */
    private function mapAffiliate(Affiliate $a): array
    {
        $totalRef = (int) $a->total_referrals;

        return [
            'id' => $a->id,
            'name' => $a->name,
            'email' => $a->email,
            'company' => $a->company_name,
            'website' => $a->website,
            'referral_code' => $a->referral_code,
            'referral_url' => $a->getReferralUrl(),
            'status' => $a->status,
            'commission_rate' => (float) $a->commission_rate,
            'commission_type' => $a->commission_type,
            'fixed_commission' => (float) $a->fixed_commission,
            'cookie_days' => (int) $a->cookie_days,
            'payout_method' => $a->payout_method,
            'minimum_payout' => (float) $a->minimum_payout,
            'total_earnings' => (float) $a->total_earnings,
            'pending_earnings' => (float) $a->pending_earnings,
            'paid_earnings' => (float) $a->paid_earnings,
            'total_referrals' => $totalRef,
            'successful_referrals' => (int) $a->successful_referrals,
            'conversion_rate' => $totalRef > 0 ? round($a->successful_referrals / $totalRef * 100, 1) : 0.0,
            'eligible' => $a->isEligibleForPayout(),
            'approved_at' => optional($a->approved_at)->toIso8601String(),
            'last_referral_at' => optional($a->last_referral_at)->toIso8601String(),
            'created_at' => optional($a->created_at)->toIso8601String(),
        ];
    }

    /**
     * Weekly counts for a collection over the last 8 weeks (oldest → newest).
     */
    private function weekly(Collection $rows, string $dateField): array
    {
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();
            $weeks[] = $rows->filter(function ($r) use ($dateField, $start, $end) {
                $d = $r->{$dateField};

                return $d instanceof Carbon && $d->between($start, $end);
            })->count();
        }

        return $weeks;
    }

    /**
     * Monthly commission earned (converted referrals) vs paid (completed payouts).
     */
    private function earnedVsPaidTrend(Collection $refs, array $payouts): array
    {
        $labels = [];
        $earned = [];
        $paid = [];
        $payCollection = collect($payouts);
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->format('M');
            $earned[] = round((float) $refs->filter(fn ($r) => $r->converted_at instanceof Carbon && $r->converted_at->isSameMonth($month))->sum('commission_amount'), 2);
            $paid[] = round((float) $payCollection->filter(fn ($p) => $p['completed_at'] && Carbon::parse($p['completed_at'])->isSameMonth($month))->sum('amount'), 2);
        }

        return ['labels' => $labels, 'earned' => $earned, 'paid' => $paid];
    }

    /**
     * Bulk lifecycle action across many affiliates, in one transaction.
     */
    public function bulkAction(array $ids, string $action): int
    {
        return DB::transaction(function () use ($ids, $action) {
            $affiliates = Affiliate::whereIn('id', $ids)->get();
            $n = 0;
            foreach ($affiliates as $a) {
                $ok = match ($action) {
                    'approve' => ! $a->isApproved() && $a->approve(),
                    'suspend' => $a->suspend('Bulk suspend'),
                    'reject' => $a->reject('Bulk reject'),
                    'create_payout' => $a->isEligibleForPayout() ? (bool) $this->createPayout($a) : false,
                    default => false,
                };
                if ($ok) {
                    $n++;
                }
            }

            return $n;
        });
    }

    /**
     * Generate visitor ID.
     */
    protected function getVisitorId(): string
    {
        $sessionId = session()->getId();

        return md5($sessionId.request()->ip());
    }

    /**
     * Check if affiliate program is enabled.
     */
    public function isEnabled(): bool
    {
        $settings = PlatformSetting::current()->getAffiliateSettings();

        return $settings['enabled'] ?? false;
    }
}
