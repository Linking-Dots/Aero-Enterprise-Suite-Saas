<?php

declare(strict_types=1);

namespace Aero\Core\Services\Dashboard;

use Aero\Core\Contracts\ModuleSummaryProvider;
use Aero\Core\Models\Announcement;
use Aero\Core\Models\AuditLog;
use Aero\Core\Models\CompanySetting;
use Aero\Notifications\Models\NotificationLog;
use Aero\Core\Models\User;
use Aero\Core\Models\UserDevice;
use Aero\Core\Models\UserSession;
use Aero\Core\Services\ModuleRegistry;
use Aero\DMS\Models\Document;
use Aero\Finance\Models\Invoice;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Holiday;
use Aero\HRM\Models\LeaveRequest;
use Aero\HRMAC\Facades\HRMAC;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\ErrorLog;
use Aero\Project\Models\Task;
use Aero\Quality\Models\NonConformanceReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Aggregates cross-module dashboard data for the admin dashboard.
 *
 * Each method is independently try/catch wrapped so one module failure
 * doesn't break the dashboard. All queries use caching (2-15 min TTL).
 */
class AdminDashboardService
{
    /**
     * Core user/role stats.
     *
     * @return array<string, mixed>
     */
    /**
     * Personalized welcome greeting based on time of day.
     *
     * @return array{greeting: string, userName: string, date: string, time: string}
     */
    public function getWelcomeData(): array
    {
        $user = auth()->user();
        $now = now();
        $hour = $now->hour;

        $greeting = match (true) {
            $hour >= 5 && $hour < 12 => 'Good Morning',
            $hour >= 12 && $hour < 17 => 'Good Afternoon',
            $hour >= 17 && $hour < 21 => 'Good Evening',
            default => 'Hello',
        };

        return [
            'greeting' => $greeting,
            'userName' => $user?->name ?? 'User',
            'date' => $now->format('l, F j, Y'),
            'time' => $now->format('g:i A'),
        ];
    }

    /**
     * Get total users count.
     */
    public function getTotalUsers(): int
    {
        return $this->getCoreStats()['totalUsers'] ?? 0;
    }

    /**
     * Get active sessions count.
     */
    public function getActiveSessions(): int
    {
        return $this->getCoreStats()['onlineUsers'] ?? 0;
    }

    /**
     * Get monthly revenue (placeholder - would integrate with billing module).
     */
    public function getMonthlyRevenue(): float
    {
        // TODO: Integrate with billing module when available
        return 0.0;
    }

    /**
     * Get recent activity formatted for dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentActivity(): array
    {
        return collect($this->getRecentAuditLog(10))
            ->map(function ($log) {
                return [
                    'id' => $log['id'],
                    'user' => $log['user'],
                    'action' => $log['description'] ?? $log['action'],
                    'time' => $log['timeAgo'],
                    'status' => $this->mapAuditStatus($log['action']),
                ];
            })
            ->toArray();
    }

    /**
     * Get top modules by usage (placeholder).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTopModules(): array
    {
        // TODO: Integrate with module registry to get actual usage stats
        return [
            ['id' => 1, 'name' => 'aero-hrm', 'users' => 0, 'active' => 0, 'growth' => '+0%'],
            ['id' => 2, 'name' => 'aero-finance', 'users' => 0, 'active' => 0, 'growth' => '+0%'],
            ['id' => 3, 'name' => 'aero-crm', 'users' => 0, 'active' => 0, 'growth' => '+0%'],
            ['id' => 4, 'name' => 'aero-project', 'users' => 0, 'active' => 0, 'growth' => '+0%'],
        ];
    }

    /**
     * Map audit log action to status badge.
     */
    private function mapAuditStatus(string $action): string
    {
        return match (true) {
            str_contains($action, 'failed'), str_contains($action, 'error') => 'danger',
            str_contains($action, 'deleted'), str_contains($action, 'removed') => 'danger',
            str_contains($action, 'created'), str_contains($action, 'added') => 'success',
            str_contains($action, 'updated'), str_contains($action, 'modified') => 'info',
            default => 'info',
        };
    }

    public function getCoreStats(): array
    {
        return Cache::remember('admin_dashboard.core_stats', 300, function () {
            try {
                $totalUsers = User::count();
                $activeUsers = User::where('active', true)->count();
                $newThisMonth = User::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
                $newThisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();
                $lastMonthUsers = User::whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->subMonth()->year)
                    ->count();

                $growthRate = $lastMonthUsers > 0
                    ? round((($newThisMonth - $lastMonthUsers) / $lastMonthUsers) * 100, 1)
                    : ($newThisMonth > 0 ? 100 : 0);

                $onlineUsers = UserSession::where('last_activity', '>=', now()->subMinutes(5))->count();

                return [
                    'totalUsers' => $totalUsers,
                    'activeUsers' => $activeUsers,
                    'inactiveUsers' => $totalUsers - $activeUsers,
                    'onlineUsers' => $onlineUsers,
                    'totalRoles' => Role::count(),
                    'newUsersThisMonth' => $newThisMonth,
                    'newUsersThisWeek' => $newThisWeek,
                    'userGrowthRate' => $growthRate,
                ];
            } catch (\Throwable $e) {
                report($e);

                return [
                    'totalUsers' => 0,
                    'activeUsers' => 0,
                    'inactiveUsers' => 0,
                    'onlineUsers' => 0,
                    'totalRoles' => 0,
                    'newUsersThisMonth' => 0,
                    'newUsersThisWeek' => 0,
                    'userGrowthRate' => 0,
                ];
            }
        });
    }

    /**
     * User login/activity chart data.
     *
     * @return array<string, mixed>
     */
    public function getUserActivity(string $period = 'week'): array
    {
        $cacheKey = "admin_dashboard.user_activity.{$period}";

        return Cache::remember($cacheKey, 300, function () use ($period) {
            try {
                $days = match ($period) {
                    'month' => 30,
                    'quarter' => 90,
                    'year' => 365,
                    default => 7,
                };

                $startDate = now()->subDays($days)->startOfDay();

                $logins = AuditLog::where('action', 'login')
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as active_users, COUNT(*) as logins')
                    ->groupByRaw('DATE(created_at)')
                    ->orderBy('date')
                    ->get();

                $newUsers = User::where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupByRaw('DATE(created_at)')
                    ->pluck('count', 'date');

                $chartData = [];
                for ($d = 0; $d < $days; $d++) {
                    $date = $startDate->copy()->addDays($d)->format('Y-m-d');
                    $loginRow = $logins->firstWhere('date', $date);
                    $chartData[] = [
                        'date' => $date,
                        'activeUsers' => $loginRow->active_users ?? 0,
                        'logins' => $loginRow->logins ?? 0,
                        'newUsers' => $newUsers[$date] ?? 0,
                    ];
                }

                $peakHours = AuditLog::where('action', 'login')
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                    ->groupByRaw('HOUR(created_at)')
                    ->orderByDesc('count')
                    ->limit(5)
                    ->get()
                    ->toArray();

                return [
                    'chartData' => $chartData,
                    'peakHours' => $peakHours,
                    'period' => $period,
                ];
            } catch (\Throwable $e) {
                report($e);

                return ['chartData' => [], 'peakHours' => [], 'period' => $period];
            }
        });
    }

    /**
     * Security overview metrics.
     *
     * @return array<string, mixed>
     */
    public function getSecurityOverview(): array
    {
        return Cache::remember('admin_dashboard.security_overview', 120, function () {
            try {
                $failedLoginsToday = AuditLog::where('action', 'failed_login')
                    ->whereDate('created_at', today())
                    ->count();

                $failedLoginsWeek = AuditLog::where('action', 'failed_login')
                    ->where('created_at', '>=', now()->subWeek())
                    ->count();

                $activeSessions = UserSession::where('last_activity', '>=', now()->subMinutes(30))->count();

                $recentDevices = UserDevice::with('user:id,name')
                    ->orderByDesc('last_used_at')
                    ->limit(5)
                    ->get(['id', 'user_id', 'device_name', 'browser', 'platform', 'last_used_at', 'is_trusted'])
                    ->toArray();

                $totalUsersWithMfa = User::whereNotNull('two_factor_secret')->count();
                $totalActiveUsers = User::where('active', true)->count();
                $mfaAdoptionRate = $totalActiveUsers > 0
                    ? round(($totalUsersWithMfa / $totalActiveUsers) * 100, 1)
                    : 0;

                $lastSecurityEvent = AuditLog::whereIn('action', ['failed_login', 'suspicious', 'password_reset', 'account_locked'])
                    ->latest()
                    ->first(['action', 'description', 'created_at', 'user_name']);

                return [
                    'failedLoginsLast24h' => $failedLoginsToday,
                    'failedLoginsWeek' => $failedLoginsWeek,
                    'activeSessions' => $activeSessions,
                    'recentDevices' => $recentDevices,
                    'mfaAdoptionPercent' => $mfaAdoptionRate,
                    'recentNewDevices' => count($recentDevices),
                    'activeSanctumTokens' => 0,
                    'lastSecurityEvent' => $lastSecurityEvent?->toArray(),
                ];
            } catch (\Throwable $e) {
                report($e);

                return [
                    'failedLoginsLast24h' => 0,
                    'failedLoginsWeek' => 0,
                    'activeSessions' => 0,
                    'recentDevices' => [],
                    'mfaAdoptionPercent' => 0,
                    'recentNewDevices' => 0,
                    'activeSanctumTokens' => 0,
                    'lastSecurityEvent' => null,
                ];
            }
        });
    }

    /**
     * Storage usage analytics.
     *
     * @return array<string, mixed>
     */
    public function getStorageAnalytics(): array
    {
        return Cache::remember('admin_dashboard.storage_analytics', 600, function () {
            try {
                $storagePath = storage_path('app');
                $totalUsed = 0;

                if (is_dir($storagePath)) {
                    $totalUsed = $this->getDirectorySize($storagePath);
                }

                // Plan storage limit (from tenant subscription)
                $totalLimit = $this->getPlanQuota('storage_limit_gb', 5) * 1024 * 1024 * 1024;
                $usagePercentage = $totalLimit > 0 ? round(($totalUsed / $totalLimit) * 100, 1) : 0;

                return [
                    'usedBytes' => $totalUsed,
                    'totalBytes' => $totalLimit,
                    'usagePercentage' => min($usagePercentage, 100),
                    'totalUsedFormatted' => $this->formatBytes($totalUsed),
                    'totalLimitFormatted' => $this->formatBytes($totalLimit),
                ];
            } catch (\Throwable $e) {
                report($e);

                return [
                    'usedBytes' => 0,
                    'totalBytes' => 0,
                    'usagePercentage' => 0,
                    'totalUsedFormatted' => '0 B',
                    'totalLimitFormatted' => '0 B',
                ];
            }
        });
    }

    /**
     * Subscription and billing information.
     *
     * @return array<string, mixed>
     */
    public function getSubscriptionInfo(): array
    {
        return Cache::remember('admin_dashboard.subscription_info', 900, function () {
            try {
                $tenant = tenant();
                if (! $tenant) {
                    return $this->defaultSubscriptionInfo();
                }

                $plan = null;
                $subscription = null;

                if (method_exists($tenant, 'subscription')) {
                    $subscription = $tenant->subscription;
                }
                if (method_exists($tenant, 'plan')) {
                    $plan = $tenant->plan;
                }

                $isOnTrial = false;
                $trialEndsAt = null;
                if (method_exists($tenant, 'onTrial')) {
                    $isOnTrial = $tenant->onTrial();
                }
                $trialEndsAt = $tenant->subscription('default')?->trial_ends_at;

                $expiresAt = $subscription?->ends_at ?? $trialEndsAt;
                $daysRemaining = $expiresAt ? now()->diffInDays(Carbon::parse($expiresAt), false) : null;

                return [
                    'plan' => $plan ? [
                        'name' => $plan->name ?? 'Free',
                        'slug' => $plan->slug ?? 'free',
                    ] : ['name' => 'Free', 'slug' => 'free'],
                    'status' => $subscription?->status ?? ($isOnTrial ? 'trial' : 'active'),
                    'isOnTrial' => $isOnTrial,
                    'trialEndsAt' => $trialEndsAt,
                    'expiresAt' => $expiresAt,
                    'daysRemaining' => $daysRemaining,
                    'quotaUsage' => $this->getQuotaUsage(),
                ];
            } catch (\Throwable $e) {
                report($e);

                return $this->defaultSubscriptionInfo();
            }
        });
    }

    /**
     * Cross-module pending approval counts.
     *
     * @return array<string, mixed>
     */
    public function getPendingApprovals(): array
    {
        return Cache::remember('admin_dashboard.pending_approvals', 180, function () {
            $approvals = [];
            $total = 0;

            $moduleChecks = [
                'hrm' => function () {
                    $pending = [];
                    try {
                        if (class_exists(LeaveRequest::class)) {
                            $pending['pendingLeaves'] = LeaveRequest::where('status', 'pending')->count();
                        }
                    } catch (\Throwable) {
                    }

                    return $pending;
                },
                'finance' => function () {
                    $pending = [];
                    try {
                        if (class_exists(Invoice::class)) {
                            $pending['pendingInvoices'] = Invoice::where('status', 'pending')->count();
                        }
                    } catch (\Throwable) {
                    }

                    return $pending;
                },
                'dms' => function () {
                    $pending = [];
                    try {
                        if (class_exists(Document::class)) {
                            $pending['pendingDocumentApprovals'] = Document::where('status', 'pending_approval')->count();
                        }
                    } catch (\Throwable) {
                    }

                    return $pending;
                },
                'project' => function () {
                    $pending = [];
                    try {
                        if (class_exists(Task::class)) {
                            $pending['overdueTasks'] = Task::where('due_date', '<', now())
                                ->whereNotIn('status', ['completed', 'cancelled'])
                                ->count();
                        }
                    } catch (\Throwable) {
                    }

                    return $pending;
                },
                'quality' => function () {
                    $pending = [];
                    try {
                        if (class_exists(NonConformanceReport::class)) {
                            $pending['pendingNCRs'] = NonConformanceReport::where('status', 'pending')->count();
                        }
                    } catch (\Throwable) {
                    }

                    return $pending;
                },
            ];

            foreach ($moduleChecks as $moduleKey => $callback) {
                try {
                    if ($this->isModuleAccessible($moduleKey)) {
                        $result = $callback();
                        if (! empty($result)) {
                            $approvals[$moduleKey] = $result;
                            $total += array_sum($result);
                        }
                    }
                } catch (\Throwable) {
                    // Skip module silently
                }
            }

            return [
                'modules' => $approvals,
                'total' => $total,
            ];
        });
    }

    /**
     * Per-module summary cards via registered providers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModuleSummaries(): array
    {
        return Cache::remember('admin_dashboard.module_summaries', 300, function () {
            $summaries = [];
            $providers = app()->tagged('module.summary.provider');

            foreach ($providers as $provider) {
                try {
                    if ($provider instanceof ModuleSummaryProvider) {
                        $summary = $provider->getDashboardSummary();
                        if (! empty($summary) && $this->isModuleAccessible($summary['key'] ?? '')) {
                            $summaries[] = $summary;
                        }
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $summaries;
        });
    }

    /**
     * Recent audit log entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentAuditLog(int $limit = 15): array
    {
        return Cache::remember('admin_dashboard.recent_audit_log', 120, function () use ($limit) {
            try {
                return AuditLog::with('user:id,name')
                    ->latest()
                    ->limit($limit)
                    ->get(['id', 'user_id', 'user_name', 'action', 'description', 'auditable_type', 'created_at', 'metadata'])
                    ->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'user' => $log->user_name ?? $log->user?->name ?? 'System',
                            'action' => $log->action,
                            'description' => $log->description,
                            'auditableType' => class_basename($log->auditable_type ?? ''),
                            'createdAt' => $log->created_at->toISOString(),
                            'timeAgo' => $log->created_at->diffForHumans(),
                        ];
                    })
                    ->toArray();
            } catch (\Throwable $e) {
                report($e);

                return [];
            }
        });
    }

    /**
     * System health indicators.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealth(): array
    {
        return Cache::remember('admin_dashboard.system_health', 120, function () {
            try {
                $health = [
                    'database' => 'healthy',
                    'cache' => 'healthy',
                    'queue' => 'healthy',
                    'failedJobs' => 0,
                    'errorCountToday' => 0,
                ];

                // Database check
                try {
                    DB::connection()->getPdo();
                } catch (\Throwable) {
                    $health['database'] = 'unhealthy';
                }

                // Cache check
                try {
                    Cache::put('health_check_test', true, 5);
                    if (! Cache::get('health_check_test')) {
                        $health['cache'] = 'degraded';
                    }
                    Cache::forget('health_check_test');
                } catch (\Throwable) {
                    $health['cache'] = 'unhealthy';
                }

                // Queue check
                try {
                    if (config('queue.default') !== 'sync') {
                        $queueSize = Queue::size();
                        if ($queueSize > 100) {
                            $health['queue'] = 'degraded';
                        }
                    }
                } catch (\Throwable) {
                    $health['queue'] = 'unknown';
                }

                // Failed jobs
                try {
                    $health['failedJobs'] = DB::table('failed_jobs')->count();
                } catch (\Throwable) {
                    // Table may not exist
                }

                // Error count today
                try {
                    if (class_exists(ErrorLog::class)) {
                        $health['errorCountToday'] = ErrorLog::whereDate('created_at', today())->count();
                    }
                } catch (\Throwable) {
                    // Platform not installed
                }

                // Overall score
                $statuses = [$health['database'], $health['cache'], $health['queue']];
                if (in_array('unhealthy', $statuses)) {
                    $health['overall'] = 'unhealthy';
                } elseif (in_array('degraded', $statuses)) {
                    $health['overall'] = 'degraded';
                } else {
                    $health['overall'] = 'healthy';
                }

                // Build services array for frontend
                $health['services'] = [
                    ['name' => 'Database', 'status' => $health['database']],
                    ['name' => 'Cache', 'status' => $health['cache']],
                    ['name' => 'Queue', 'status' => $health['queue']],
                ];

                if ($health['failedJobs'] > 0) {
                    $health['services'][] = ['name' => 'Failed Jobs ('.$health['failedJobs'].')', 'status' => 'degraded'];
                }

                return $health;
            } catch (\Throwable $e) {
                report($e);

                return [
                    'database' => 'unknown',
                    'cache' => 'unknown',
                    'queue' => 'unknown',
                    'failedJobs' => 0,
                    'errorCountToday' => 0,
                    'overall' => 'unknown',
                ];
            }
        });
    }

    /**
     * Active announcements for the tenant.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAnnouncements(): array
    {
        return Cache::remember('admin_dashboard.announcements', 300, function () {
            try {
                if (! class_exists(Announcement::class)) {
                    return [];
                }

                $user = auth()->user();

                return Announcement::query()
                    ->active()
                    ->forUser($user)
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'title' => $a->title,
                        'body' => $a->body,
                        'type' => $a->type,
                        'priority' => $a->priority,
                        'isPinned' => $a->is_pinned,
                        'isDismissible' => $a->is_dismissible,
                        'authorName' => $a->author?->name ?? 'System',
                        'createdAt' => $a->created_at->toISOString(),
                    ])
                    ->toArray();
            } catch (\Throwable $e) {
                report($e);

                return [];
            }
        });
    }

    /**
     * Permission-gated quick action list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getQuickActions(): array
    {
        try {
            $user = auth()->user();
            $actions = [
                [
                    'group' => 'Users',
                    'items' => [
                        ['label' => 'Add User', 'icon' => 'UserPlusIcon', 'route' => 'core.users.index', 'permission' => 'core.user_management.users.create'],
                        ['label' => 'Invite User', 'icon' => 'EnvelopeIcon', 'route' => 'core.users.index', 'permission' => 'core.user_management.user_invitations.invite'],
                        ['label' => 'Manage Roles', 'icon' => 'ShieldCheckIcon', 'route' => 'core.roles.index', 'permission' => 'core.roles_permissions.roles.view'],
                    ],
                ],
                [
                    'group' => 'Settings',
                    'items' => [
                        ['label' => 'Company Settings', 'icon' => 'BuildingOfficeIcon', 'route' => 'core.settings.system', 'permission' => 'core.settings.general.view'],
                        ['label' => 'Security Settings', 'icon' => 'ShieldExclamationIcon', 'route' => 'core.settings.system', 'permission' => 'core.settings.security.view'],
                    ],
                ],
            ];

            // Filter by permission
            return collect($actions)->map(function ($group) use ($user) {
                $group['items'] = collect($group['items'])->filter(function ($item) use ($user) {
                    if (empty($item['permission'])) {
                        return true;
                    }

                    try {
                        $parts = explode('.', $item['permission']);
                        if (count($parts) >= 4) {
                            return HRMAC::userCanAccessAction($user, $parts[0], $parts[1], $parts[3]);
                        }
                    } catch (\Throwable) {
                    }

                    return false;
                })->values()->toArray();

                return $group;
            })->filter(fn ($g) => ! empty($g['items']))->values()->toArray();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Onboarding progress for new tenants.
     *
     * @return array<string, mixed>|null
     */
    public function getOnboardingProgress(): ?array
    {
        return Cache::remember('admin_dashboard.onboarding_progress', 600, function () {
            try {
                $tenant = tenant();
                if (! $tenant) {
                    return null;
                }

                // Only show for tenants created in the last 30 days
                $createdAt = $tenant->created_at ?? null;
                if ($createdAt && Carbon::parse($createdAt)->diffInDays(now()) > 30) {
                    return null;
                }

                $steps = [
                    ['key' => 'company_logo', 'label' => 'Set company logo', 'completed' => $this->hasCompanyLogo(), 'route' => 'core.settings.system'],
                    ['key' => 'first_user', 'label' => 'Invite first user', 'completed' => User::count() > 1, 'route' => 'core.users.index'],
                    ['key' => 'first_role', 'label' => 'Create a custom role', 'completed' => Role::where('name', '!=', 'Super Administrator')->count() > 0, 'route' => 'core.roles.index'],
                    ['key' => 'enable_modules', 'label' => 'Enable modules', 'completed' => $this->hasMultipleModules(), 'route' => null],
                ];

                $completedCount = collect($steps)->where('completed', true)->count();
                $totalSteps = count($steps);

                return [
                    'steps' => $steps,
                    'completedCount' => $completedCount,
                    'totalSteps' => $totalSteps,
                    'percentage' => $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0,
                    'completed' => $completedCount === $totalSteps,
                ];
            } catch (\Throwable $e) {
                report($e);

                return null;
            }
        });
    }

    /**
     * Upcoming events from across modules.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingEvents(int $days = 7): array
    {
        return Cache::remember('admin_dashboard.upcoming_events', 600, function () use ($days) {
            $events = [];

            // Holidays from HRM
            try {
                if (class_exists(Holiday::class)) {
                    $holidays = Holiday::whereBetween('date', [now(), now()->addDays($days)])
                        ->get(['name', 'date'])
                        ->map(fn ($h) => [
                            'title' => $h->name,
                            'date' => $h->date->toDateString(),
                            'type' => 'holiday',
                            'module' => 'hrm',
                        ]);
                    $events = array_merge($events, $holidays->toArray());
                }
            } catch (\Throwable) {
            }

            // Task deadlines from Project
            try {
                if (class_exists(Task::class)) {
                    $tasks = Task::whereBetween('due_date', [now(), now()->addDays($days)])
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->limit(10)
                        ->get(['title', 'due_date'])
                        ->map(fn ($t) => [
                            'title' => $t->title,
                            'date' => $t->due_date->toDateString(),
                            'type' => 'deadline',
                            'module' => 'project',
                        ]);
                    $events = array_merge($events, $tasks->toArray());
                }
            } catch (\Throwable) {
            }

            // Sort by date
            usort($events, fn ($a, $b) => strcmp($a['date'], $b['date']));

            return $events;
        });
    }

    /**
     * Recent notification log entries for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function getRecentNotifications(): array
    {
        return Cache::remember('admin_dashboard.recent_notifications', 120, function () {
            try {
                $items = NotificationLog::latest()
                    ->limit(8)
                    ->get(['id', 'user_id', 'channel', 'notification_type', 'subject', 'status', 'sent_at', 'read_at', 'created_at'])
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'channel' => $n->channel,
                        'type' => class_basename($n->notification_type ?? 'Notification'),
                        'subject' => $n->subject ?? 'Notification',
                        'status' => $n->status,
                        'isRead' => ! is_null($n->read_at),
                        'timeAgo' => $n->created_at->diffForHumans(),
                    ])
                    ->toArray();

                $total = NotificationLog::count();
                $unread = NotificationLog::whereNull('read_at')->count();
                $failedToday = NotificationLog::where('status', 'failed')
                    ->whereDate('created_at', today())
                    ->count();

                return [
                    'items' => $items,
                    'total' => $total,
                    'unread' => $unread,
                    'failedToday' => $failedToday,
                ];
            } catch (\Throwable $e) {
                report($e);

                return ['items' => [], 'total' => 0, 'unread' => 0, 'failedToday' => 0];
            }
        });
    }

    /**
     * Active sessions and device data for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function getActiveSessionsData(): array
    {
        return Cache::remember('admin_dashboard.active_sessions', 60, function () {
            try {
                $onlineNow = UserSession::where('last_activity', '>=', now()->subMinutes(5))->count();
                $activeToday = UserSession::whereDate('last_activity', today())->count();
                $activeThisWeek = UserSession::where('last_activity', '>=', now()->startOfWeek())->count();

                // Recent sessions
                $recentSessions = UserSession::with('user:id,name')
                    ->orderByDesc('last_activity')
                    ->limit(6)
                    ->get(['id', 'user_id', 'ip_address', 'last_activity', 'user_agent'])
                    ->map(fn ($s) => [
                        'user' => $s->user?->name ?? 'Unknown',
                        'ip' => $s->ip_address ?? '—',
                        'timeAgo' => \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                        'isOnline' => $s->last_activity >= now()->subMinutes(5)->timestamp,
                    ])
                    ->toArray();

                // Device type breakdown from UserDevice
                $devices = [];
                try {
                    $deviceCounts = UserDevice::where('is_active', true)
                        ->selectRaw('device_type, count(*) as total')
                        ->groupBy('device_type')
                        ->pluck('total', 'device_type')
                        ->toArray();
                    $devices = $deviceCounts;
                } catch (\Throwable) {
                }

                return [
                    'onlineNow' => $onlineNow,
                    'activeToday' => $activeToday,
                    'activeThisWeek' => $activeThisWeek,
                    'recentSessions' => $recentSessions,
                    'deviceBreakdown' => $devices,
                ];
            } catch (\Throwable $e) {
                report($e);

                return [
                    'onlineNow' => 0,
                    'activeToday' => 0,
                    'activeThisWeek' => 0,
                    'recentSessions' => [],
                    'deviceBreakdown' => [],
                ];
            }
        });
    }

    /**
     * Check if a module is accessible to the current user.
     */
    protected function isModuleAccessible(string $moduleKey): bool
    {
        try {
            $user = auth()->user();
            if (! $user) {
                return false;
            }

            return HRMAC::userCanAccessModule($user, $moduleKey);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get plan quota value.
     */
    protected function getPlanQuota(string $key, mixed $default = null): mixed
    {
        try {
            $tenant = tenant();
            if ($tenant && method_exists($tenant, 'plan')) {
                $plan = $tenant->plan;
                if ($plan && isset($plan->features[$key])) {
                    return $plan->features[$key];
                }
            }
        } catch (\Throwable) {
        }

        return $default;
    }

    /**
     * Get quota usage stats.
     *
     * @return array<string, array{used: int, limit: int|string}>
     */
    protected function getQuotaUsage(): array
    {
        try {
            return [
                'users' => [
                    'used' => User::count(),
                    'limit' => $this->getPlanQuota('max_users', 'unlimited'),
                ],
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    protected function hasCompanyLogo(): bool
    {
        try {
            $setting = CompanySetting::where('key', 'company_logo')->first();

            return $setting && ! empty($setting->value);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function hasDepartments(): bool
    {
        try {
            if (class_exists(Department::class)) {
                return Department::count() > 0;
            }
        } catch (\Throwable) {
        }

        return false;
    }

    protected function hasMultipleModules(): bool
    {
        try {
            $registry = app(ModuleRegistry::class);

            return count($registry->getActiveModules()) > 1;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $size += $file->getSize();
            }
        } catch (\Throwable) {
        }

        return $size;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSubscriptionInfo(): array
    {
        return [
            'plan' => ['name' => 'Free', 'slug' => 'free'],
            'status' => 'active',
            'isOnTrial' => false,
            'trialEndsAt' => null,
            'expiresAt' => null,
            'daysRemaining' => null,
            'quotaUsage' => [],
        ];
    }
}
