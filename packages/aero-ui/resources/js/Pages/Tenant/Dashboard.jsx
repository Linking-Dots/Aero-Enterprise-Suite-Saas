/**
 * Tenant/Dashboard — Modular widget dashboard.
 *
 * Conventions (matches codebase standard):
 *   • No inline style={} except for single dynamic computed values (e.g. progress width %).
 *   • No <style> tags — all classes live in utilities.css or component CSS files.
 *   • Layout via @aero/ui primitives: HStack, VStack, Box with gap/align props.
 *   • Visual treatment via className strings from the aeos design system.
 *   • All data arrives via Inertia lazy props from DashboardController.
 */

import { useState, useCallback } from 'react';
import { usePage, Link, router } from '@inertiajs/react';
import {
  AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer,
} from 'recharts';
import {
  DashboardLayout,
  Card, CardHeader, CardBody,
  VStack, HStack, Box,
  Text, Mono, Eyebrow, Badge,
  Button, Avatar,
  Progress, Skeleton,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

// ─── Utilities ──────────────────────────────────────────────────────────────

function auditIntent(action = '') {
  if (/creat|add|invit|enabl/i.test(action))  return 'success';
  if (/delet|remov|disabl/i.test(action))      return 'danger';
  if (/updat|modif|edit/i.test(action))        return 'primary';
  return 'neutral';
}

function healthIntent(status = '') {
  if (status === 'healthy')   return 'success';
  if (status === 'degraded')  return 'amber';
  if (status === 'unhealthy') return 'danger';
  return 'neutral';
}

function planIntent(status = '') {
  if (status === 'active') return 'success';
  if (status === 'trial')  return 'amber';
  return 'neutral';
}

function WidgetSkeleton({ rows = 4 }) {
  return (
    <VStack gap={3}>
      {Array.from({ length: rows }).map((_, i) => (
        <Skeleton key={i} h={16} w={i % 2 === 0 ? '100%' : '65%'} />
      ))}
    </VStack>
  );
}

// ─── W1 · Welcome ────────────────────────────────────────────────────────────

function WelcomeWidget({ welcomeData = {}, subscriptionInfo = null }) {
  const { auth }  = usePage().props;
  const user      = auth?.user;
  const name      = welcomeData.userName ?? user?.name ?? 'there';
  const firstName = name.split(' ')[0];
  const initials  = name.split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase();
  const planName  = subscriptionInfo?.plan?.name ?? null;
  const isOnTrial = subscriptionInfo?.isOnTrial ?? false;
  const daysLeft  = subscriptionInfo?.daysRemaining ?? null;

  return (
    <Card className="aeos-col-span-4">
      <HStack gap={4} align="center">
        <Avatar initials={initials} size={52} />
        <VStack gap={1}>
          <Eyebrow tone="primary">{welcomeData.greeting ?? 'Welcome back'}</Eyebrow>
          <Text as="h2" className="dash-welcome-title">{firstName}</Text>
          <Text tone="secondary" size="sm">{welcomeData.date ?? ''}</Text>
        </VStack>
        <Box grow />
        <HStack gap={2}>
          {planName && (
            <Badge intent={isOnTrial ? 'amber' : 'success'} dot>
              {planName}{isOnTrial && daysLeft !== null ? ` · ${daysLeft}d trial` : ''}
            </Badge>
          )}
          <Badge intent="neutral">{welcomeData.time ?? ''}</Badge>
        </HStack>
      </HStack>
    </Card>
  );
}

// ─── W2 · KPI tiles ──────────────────────────────────────────────────────────

function KpiTile({ label, value, delta, deltaTrend, sparkValues = [] }) {
  const up   = deltaTrend === 'up'   || (typeof delta === 'number' && delta > 0);
  const down = deltaTrend === 'down' || (typeof delta === 'number' && delta < 0);
  const max  = Math.max(...sparkValues, 1);

  return (
    <Card>
      <VStack gap={2}>
        <Text size="sm" tone="tertiary">{label}</Text>
        <Text as="div" className="dash-kpi-value">{value ?? '—'}</Text>
        {delta !== undefined && delta !== null && (
          <Badge intent={up ? 'success' : down ? 'danger' : 'neutral'} size="sm">
            {up ? '↑' : down ? '↓' : '→'}{' '}
            {typeof delta === 'number' ? Math.abs(delta) : delta}
          </Badge>
        )}
        {sparkValues.length > 0 && (
          <div className="dash-spark" aria-hidden="true">
            {sparkValues.map((v, i) => (
              <span
                key={i}
                className={i === sparkValues.length - 1 ? 'dash-spark-bar dash-spark-bar--active' : 'dash-spark-bar'}
                style={{ height: `${Math.round((v / max) * 100)}%` }}
              />
            ))}
          </div>
        )}
      </VStack>
    </Card>
  );
}

function KpiRow({ coreStats }) {
  if (!coreStats) {
    return (
      <>
        {[0, 1, 2, 3].map(i => (
          <Card key={i}><WidgetSkeleton rows={3} /></Card>
        ))}
      </>
    );
  }

  const tiles = [
    {
      label: 'Total users',
      value: coreStats.totalUsers,
      delta: coreStats.newUsersThisMonth,
      deltaTrend: coreStats.newUsersThisMonth > 0 ? 'up' : 'neutral',
      sparkValues: [40, 55, 45, 70, 60, 80, coreStats.totalUsers],
    },
    {
      label: 'Active users',
      value: coreStats.activeUsers,
      delta: coreStats.totalUsers > 0
        ? `${Math.round((coreStats.activeUsers / coreStats.totalUsers) * 100)}%`
        : '—',
      deltaTrend: 'neutral',
      sparkValues: [60, 65, 72, 68, 75, 80, coreStats.activeUsers],
    },
    {
      label: 'Total roles',
      value: coreStats.totalRoles,
      delta: null,
      sparkValues: [],
    },
    {
      label: 'Online now',
      value: coreStats.onlineUsers,
      delta: coreStats.newUsersThisWeek,
      deltaTrend: coreStats.newUsersThisWeek > 0 ? 'up' : 'neutral',
      sparkValues: [30, 30, 45, 45, 60, 77, coreStats.onlineUsers],
    },
  ];

  return (
    <>
      {tiles.map((tile, i) => <KpiTile key={i} {...tile} />)}
    </>
  );
}

// ─── W3 · Onboarding ─────────────────────────────────────────────────────────

function OnboardingWidget({ onboardingProgress }) {
  if (!onboardingProgress || onboardingProgress.percentage === 100) return null;

  const { steps = [], completedCount, totalSteps, percentage } = onboardingProgress;

  return (
    <Card className="aeos-col-span-2">
      <CardHeader>
        <HStack gap={2} align="center">
          <VStack gap={0}>
            <Eyebrow tone="primary">Onboarding</Eyebrow>
            <Text size="sm" tone="secondary">{completedCount} of {totalSteps} complete</Text>
          </VStack>
          <Box grow />
          <Badge intent={percentage < 50 ? 'amber' : 'success'}>{percentage}%</Badge>
        </HStack>
      </CardHeader>

      <div className="aeos-progress-track">
        <div
          className={`aeos-progress-fill${percentage < 50 ? ' aeos-progress-fill--amber' : ''}`}
          style={{ width: `${percentage}%` }}
        />
      </div>

      <VStack gap={0} className="aeos-mt-2">
        {steps.map((step, i) => (
          <HStack
            key={i}
            gap={3}
            align="center"
            className={i < steps.length - 1 ? 'dash-step-row' : 'dash-step-row dash-step-row--last'}
          >
            <span
              className={step.completed ? 'dash-step-check dash-step-check--done' : 'dash-step-check'}
              aria-hidden="true"
            />
            <Text
              size="sm"
              tone={step.completed ? 'tertiary' : 'primary'}
              className={step.completed ? 'aeos-flex-1 dash-step-label--done' : 'aeos-flex-1'}
            >
              {step.label}
            </Text>
            {!step.completed && step.route && (
              <Badge intent="neutral" size="sm" as={Link} href={route(step.route)}>
                Go →
              </Badge>
            )}
          </HStack>
        ))}
      </VStack>
    </Card>
  );
}

// ─── W4 · Announcements ──────────────────────────────────────────────────────

const PRIORITY_INTENT = { high: 'danger', medium: 'warning', low: 'neutral', info: 'primary' };

function AnnouncementsWidget({ announcements = [] }) {
  const [dismissed, setDismissed] = useState([]);
  const visible = announcements.filter(a => !dismissed.includes(a.id));

  if (visible.length === 0) return null;

  function dismiss(id) {
    setDismissed(prev => [...prev, id]);
    fetch(route('core.dashboard.announcements.dismiss', id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
    }).catch(() => {});
  }

  return (
    <Card className="aeos-col-span-2">
      <CardHeader>
        <HStack gap={2} align="center">
          <Eyebrow tone="primary">Announcements</Eyebrow>
          <Box grow />
          <Button intent="ghost" size="sm" as={Link} href={route('core.announcements.index')}>
            View all
          </Button>
        </HStack>
      </CardHeader>

      <VStack gap={0}>
        {visible.slice(0, 3).map((item, i) => (
          <div
            key={item.id}
            className={i < Math.min(visible.length, 3) - 1 ? 'dash-announce-item' : 'dash-announce-item dash-announce-item--last'}
          >
            <HStack gap={2} align="center" className="aeos-mb-1">
              {item.isPinned && <Text as="span" size="xs" tone="tertiary">📌</Text>}
              <Text as="span" size="sm" weight={500} className="aeos-flex-1">{item.title}</Text>
              <Badge intent={PRIORITY_INTENT[item.priority] ?? 'neutral'} size="sm">
                {item.priority ?? 'info'}
              </Badge>
              {item.isDismissible && (
                <button
                  type="button"
                  onClick={() => dismiss(item.id)}
                  aria-label="Dismiss"
                  className="dash-dismiss-btn"
                >
                  ×
                </button>
              )}
            </HStack>
            <Text size="sm" tone="secondary">{item.body}</Text>
            <Text size="xs" tone="tertiary" className="aeos-mt-1">
              {item.authorName} · {new Date(item.createdAt).toLocaleDateString()}
            </Text>
          </div>
        ))}
      </VStack>
    </Card>
  );
}

// ─── W5 · Activity chart ─────────────────────────────────────────────────────

const PERIOD_LABELS = { week: '7 days', month: '30 days', quarter: '90 days' };

function ActivityChartWidget({ userActivity }) {
  const [period, setPeriod]     = useState('week');
  const [chartData, setChartData] = useState(userActivity?.chartData ?? []);
  const [loading, setLoading]   = useState(false);

  const switchPeriod = useCallback(async (p) => {
    if (p === period) return;
    setPeriod(p);
    setLoading(true);
    try {
      const res  = await fetch(`/dashboard/user-activity?period=${p}`);
      const json = await res.json();
      setChartData(json.data?.chartData ?? []);
    } catch {
      // retain existing data on error
    } finally {
      setLoading(false);
    }
  }, [period]);

  return (
    <Card className="aeos-col-span-3">
      <CardHeader>
        <HStack gap={2} align="center">
          <VStack gap={0}>
            <Eyebrow tone="primary">User activity</Eyebrow>
            <Text size="sm" tone="secondary">Logins &amp; new users</Text>
          </VStack>
          <Box grow />
          <HStack gap={1}>
            {Object.entries(PERIOD_LABELS).map(([key, label]) => (
              <Badge
                key={key}
                intent={period === key ? 'primary' : 'neutral'}
                className="dash-period-tab"
                onClick={() => switchPeriod(key)}
              >
                {label}
              </Badge>
            ))}
          </HStack>
        </HStack>
      </CardHeader>

      {loading || chartData.length === 0 ? (
        <Skeleton h={100} />
      ) : (
        <ResponsiveContainer width="100%" height={100}>
          <AreaChart data={chartData} margin={{ top: 4, right: 0, left: -32, bottom: 0 }}>
            <defs>
              <linearGradient id="lgLogins" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%"  stopColor="var(--aeos-primary)" stopOpacity={0.25} />
                <stop offset="95%" stopColor="var(--aeos-primary)" stopOpacity={0} />
              </linearGradient>
              <linearGradient id="lgNew" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%"  stopColor="var(--aeos-success)" stopOpacity={0.25} />
                <stop offset="95%" stopColor="var(--aeos-success)" stopOpacity={0} />
              </linearGradient>
            </defs>
            <XAxis
              dataKey="date"
              tick={{ fontSize: 10, fill: 'var(--aeos-text-tertiary)' }}
              tickFormatter={v => v.slice(5)}
              axisLine={false}
              tickLine={false}
            />
            <YAxis hide />
            <Tooltip
              contentStyle={{
                background: 'var(--aeos-surface)',
                border: '0.5px solid var(--aeos-border)',
                borderRadius: 6,
                fontSize: 12,
              }}
            />
            <Area type="monotone" dataKey="logins"   name="Logins"     stroke="var(--aeos-primary)" strokeWidth={1.5} fill="url(#lgLogins)" dot={false} />
            <Area type="monotone" dataKey="newUsers"  name="New users"  stroke="var(--aeos-success)" strokeWidth={1.5} fill="url(#lgNew)"    dot={false} />
          </AreaChart>
        </ResponsiveContainer>
      )}

      <HStack gap={4} className="aeos-mt-1">
        <HStack gap={1} align="center">
          <span className="dash-legend-dot dash-legend-dot--primary" aria-hidden="true" />
          <Text size="xs" tone="tertiary">Logins</Text>
        </HStack>
        <HStack gap={1} align="center">
          <span className="dash-legend-dot dash-legend-dot--success" aria-hidden="true" />
          <Text size="xs" tone="tertiary">New users</Text>
        </HStack>
      </HStack>
    </Card>
  );
}

// ─── W6 · Sessions ───────────────────────────────────────────────────────────

function SessionsWidget({ sessionsData }) {
  if (!sessionsData) return <Card><WidgetSkeleton rows={4} /></Card>;

  const { onlineNow, activeToday, activeThisWeek, recentSessions = [] } = sessionsData;

  const stats = [
    { label: 'Online now', value: onlineNow,       accent: true },
    { label: 'Today',      value: activeToday,      accent: false },
    { label: 'This week',  value: activeThisWeek,   accent: false },
  ];

  return (
    <Card>
      <Eyebrow tone="primary" className="aeos-mb-2">Sessions</Eyebrow>

      <HStack gap={0} className="aeos-mb-2">
        {stats.map((s, i) => (
          <VStack
            key={i}
            gap={0}
            align="center"
            className={i < stats.length - 1 ? 'dash-session-stat' : 'dash-session-stat dash-session-stat--last'}
          >
            <Text as="div" className={s.accent ? 'dash-session-num dash-session-num--online' : 'dash-session-num'}>
              {s.value ?? 0}
            </Text>
            <Text size="xs" tone="tertiary">{s.label}</Text>
          </VStack>
        ))}
      </HStack>

      <div className="dash-divider-h">
        <Text size="xs" tone="tertiary" className="aeos-mb-1">Recent sessions</Text>
        <VStack gap={2}>
          {recentSessions.slice(0, 4).map((s, i) => (
            <HStack key={i} gap={2} align="center">
              <span className={s.isOnline ? 'dash-session-dot dash-session-dot--online' : 'dash-session-dot'} aria-hidden="true" />
              <Text size="sm" className="aeos-flex-1">{s.user}</Text>
              <Mono size="sm" tone="tertiary">{s.timeAgo}</Mono>
            </HStack>
          ))}
        </VStack>
      </div>
    </Card>
  );
}

// ─── W7 · Security ───────────────────────────────────────────────────────────

function SecurityWidget({ securityOverview }) {
  if (!securityOverview) return <Card className="aeos-col-span-2"><WidgetSkeleton rows={5} /></Card>;

  const { failedLoginsLast24h, activeSessions, mfaAdoptionPercent, lastSecurityEvent } = securityOverview;

  const rows = [
    { label: 'MFA adoption',         value: `${mfaAdoptionPercent}%`,  pct: mfaAdoptionPercent,               intent: 'success' },
    { label: 'Failed logins (24 h)', value: failedLoginsLast24h,        pct: Math.min(failedLoginsLast24h * 10, 100), intent: failedLoginsLast24h > 5 ? 'danger' : 'cyan' },
    { label: 'Active sessions (30m)', value: activeSessions,             pct: Math.min(activeSessions * 5, 100), intent: 'cyan' },
  ];

  return (
    <Card className="aeos-col-span-2">
      <CardHeader>
        <HStack gap={2} align="center">
          <Eyebrow tone="primary">Security</Eyebrow>
          <Box grow />
          {failedLoginsLast24h === 0
            ? <Badge intent="success" dot size="sm">No alerts</Badge>
            : <Badge intent="danger"  dot size="sm">{failedLoginsLast24h} failed logins</Badge>
          }
        </HStack>
      </CardHeader>

      <VStack gap={3}>
        {rows.map((row, i) => (
          <VStack key={i} gap={1}>
            <HStack gap={2} align="center">
              <Text size="sm" tone="secondary" className="aeos-flex-1">{row.label}</Text>
              <Text size="sm" weight={500}>{row.value}</Text>
            </HStack>
            <div className="aeos-progress-track">
              <div
                className={`aeos-progress-fill aeos-progress-fill--${row.intent}`}
                style={{ width: `${row.pct}%` }}
              />
            </div>
          </VStack>
        ))}
      </VStack>

      {lastSecurityEvent && (
        <div className="dash-divider-h aeos-mt-2">
          <Text size="xs" tone="tertiary" className="aeos-mb-1">Last security event</Text>
          <HStack gap={2} align="center">
            <Badge intent="neutral" size="sm">{lastSecurityEvent.action}</Badge>
            <Text size="sm" tone="secondary">{lastSecurityEvent.user_name}</Text>
          </HStack>
        </div>
      )}
    </Card>
  );
}

// ─── W8 · Storage + plan ─────────────────────────────────────────────────────

function StorageWidget({ storageAnalytics, subscriptionInfo }) {
  if (!storageAnalytics) return <Card className="aeos-col-span-2"><WidgetSkeleton rows={4} /></Card>;

  const { usagePercentage, totalUsedFormatted, totalLimitFormatted } = storageAnalytics;

  const quota        = subscriptionInfo?.quotaUsage?.users;
  const userUsed     = quota?.used ?? null;
  const userLimit    = quota?.limit ?? null;
  const userPct      = (userUsed !== null && typeof userLimit === 'number' && userLimit > 0)
    ? Math.round((userUsed / userLimit) * 100) : null;
  const daysRemaining = subscriptionInfo?.daysRemaining ?? null;
  const planName      = subscriptionInfo?.plan?.name ?? null;

  const storageIntent = usagePercentage > 85 ? 'danger' : usagePercentage > 65 ? 'amber' : 'success';
  const userIntent    = userPct !== null
    ? (userPct > 90 ? 'danger' : userPct > 75 ? 'amber' : 'success')
    : 'success';

  return (
    <Card className="aeos-col-span-2">
      <CardHeader>
        <HStack gap={2} align="center">
          <Eyebrow tone="primary">Storage &amp; plan</Eyebrow>
          <Box grow />
          {planName && <Badge intent={planIntent(subscriptionInfo?.status)}>{planName}</Badge>}
        </HStack>
      </CardHeader>

      <VStack gap={4}>
        <VStack gap={1}>
          <HStack gap={2} align="center">
            <Text size="sm" tone="secondary" className="aeos-flex-1">Storage used</Text>
            <Mono size="sm">{totalUsedFormatted} / {totalLimitFormatted}</Mono>
          </HStack>
          <div className="aeos-progress-track">
            <div className={`aeos-progress-fill aeos-progress-fill--${storageIntent}`} style={{ width: `${usagePercentage}%` }} />
          </div>
        </VStack>

        {userPct !== null && (
          <VStack gap={1}>
            <HStack gap={2} align="center">
              <Text size="sm" tone="secondary" className="aeos-flex-1">User seats</Text>
              <Mono size="sm">{userUsed} / {userLimit}</Mono>
            </HStack>
            <div className="aeos-progress-track">
              <div className={`aeos-progress-fill aeos-progress-fill--${userIntent}`} style={{ width: `${userPct}%` }} />
            </div>
          </VStack>
        )}

        {daysRemaining !== null && (
          <HStack gap={3} align="center">
            <VStack gap={0} className="aeos-flex-1">
              <Text size="xs" tone="tertiary">Subscription expires</Text>
              <Text size="sm" weight={500}>
                {daysRemaining > 0 ? `${daysRemaining} days remaining` : 'Expired'}
              </Text>
            </VStack>
            {daysRemaining <= 30 && (
              <Button intent="amber" size="sm" as={Link} href={route('core.subscription.plans')}>
                Upgrade
              </Button>
            )}
          </HStack>
        )}
      </VStack>
    </Card>
  );
}

// ─── W10 · Audit log ─────────────────────────────────────────────────────────

function AuditLogWidget({ recentAuditLog = [] }) {
  return (
    <Card className="aeos-col-span-2">
      <CardHeader>
        <HStack gap={2} align="center">
          <VStack gap={0}>
            <Eyebrow tone="primary">Audit log</Eyebrow>
            <Text size="sm" tone="secondary">Recent activity</Text>
          </VStack>
          <Box grow />
          <Button intent="ghost" size="sm" as={Link} href={route('core.audit-logs.index')}>
            View all
          </Button>
        </HStack>
      </CardHeader>

      {recentAuditLog.length === 0 ? (
        <WidgetSkeleton rows={5} />
      ) : (
        <VStack gap={0}>
          {recentAuditLog.slice(0, 7).map((log, i) => (
            <HStack
              key={log.id ?? i}
              gap={3}
              align="flex-start"
              className={i < 6 ? 'dash-audit-row' : 'dash-audit-row dash-audit-row--last'}
            >
              <span className={`dash-activity-dot dash-activity-dot--${auditIntent(log.action)}`} aria-hidden="true" />
              <VStack gap={0} className="aeos-flex-1 aeos-truncate">
                <Text size="sm">
                  <Text as="span" weight={500}>{log.user}</Text>
                  {' '}{log.description ?? log.action}
                </Text>
                <Mono size="sm" tone="tertiary">{log.timeAgo}</Mono>
              </VStack>
              <Badge intent={auditIntent(log.action)} size="sm">{log.action}</Badge>
            </HStack>
          ))}
        </VStack>
      )}
    </Card>
  );
}

// ─── W11 · Quick actions ─────────────────────────────────────────────────────

const ICON_FALLBACK = { UserPlusIcon: '👤', EnvelopeIcon: '✉', ShieldCheckIcon: '🛡', BuildingOfficeIcon: '🏢', ShieldExclamationIcon: '⚠' };

function QuickActionsWidget({ quickActions = [] }) {
  const allItems = quickActions.flatMap(g => g.items ?? []);
  if (allItems.length === 0) return null;

  return (
    <Card className="aeos-col-span-2">
      <Eyebrow tone="primary" className="aeos-mb-2">Quick actions</Eyebrow>
      <VStack gap={2}>
        {allItems.slice(0, 5).map((item, i) => (
          <Card
            key={i}
            interactive
            as={Link}
            href={item.route ? route(item.route) : '#'}
            className="dash-qa-card"
          >
            <HStack gap={3} align="center">
              <div className="dash-qa-icon" aria-hidden="true">
                {ICON_FALLBACK[item.icon] ?? '→'}
              </div>
              <Text size="sm" weight={500} className="aeos-flex-1">{item.label}</Text>
              <Text tone="tertiary" size="sm">→</Text>
            </HStack>
          </Card>
        ))}
      </VStack>
    </Card>
  );
}

// ─── System info footer ───────────────────────────────────────────────────────

function SystemInfoWidget({ systemHealth }) {
  const { auth } = usePage().props;
  const user     = auth?.user;
  const services = systemHealth?.services ?? [];
  const overall  = systemHealth?.overall ?? 'unknown';

  return (
    <Card className="aeos-col-span-4 aeos-surface-chip">
      <HStack gap={6} wrap="wrap" align="center">
        <VStack gap={0}>
          <Eyebrow tone="primary">Your role</Eyebrow>
          <Text weight={500}>{user?.roles?.[0] ?? 'Administrator'}</Text>
          <Mono tone="secondary" size="sm">{user?.email ?? '—'}</Mono>
        </VStack>

        <span className="dash-divider-v" aria-hidden="true" />

        <VStack gap={1}>
          <Eyebrow tone="primary">System health</Eyebrow>
          <HStack gap={2} wrap="wrap">
            <Badge intent={healthIntent(overall)} dot>{overall}</Badge>
            {services.map((svc, i) => (
              <Badge key={i} intent={healthIntent(svc.status)} size="sm">{svc.name}</Badge>
            ))}
          </HStack>
        </VStack>

        <Box grow />

        <HStack gap={2}>
          <Button intent="ghost" size="sm" as={Link} href={route('core.settings.system')}>
            Settings
          </Button>
          <Button intent="ghost" size="sm" as={Link} href={route('core.audit-logs.index')}>
            Audit logs
          </Button>
        </HStack>
      </HStack>
    </Card>
  );
}

// ─── Dashboard root ───────────────────────────────────────────────────────────

export default function Dashboard({
  welcomeData        = {},
  coreStats          = null,
  onboardingProgress = null,
  announcements      = [],
  userActivity       = null,
  sessionsData       = null,
  securityOverview   = null,
  storageAnalytics   = null,
  subscriptionInfo   = null,
  recentAuditLog     = [],
  quickActions       = [],
  systemHealth       = null,
}) {
  const showOnboarding    = onboardingProgress !== null && (onboardingProgress?.percentage ?? 100) < 100;
  const showAnnouncements = announcements.length > 0;
  const overallStatus     = systemHealth?.overall ?? 'unknown';

  return (
    <DashboardLayout
      title="Dashboard"
      cols={{ base: 1, md: 2, lg: 4 }}
      actions={
        <HStack gap={2}>
          <Badge intent={overallStatus === 'healthy' ? 'success' : 'amber'} dot>
            {overallStatus === 'healthy' ? 'System healthy' : overallStatus}
          </Badge>
        </HStack>
      }
    >
      <WelcomeWidget welcomeData={welcomeData} subscriptionInfo={subscriptionInfo} />

      <KpiRow coreStats={coreStats} />

      {showOnboarding    && <OnboardingWidget    onboardingProgress={onboardingProgress} />}
      {showAnnouncements && <AnnouncementsWidget announcements={announcements} />}

      <ActivityChartWidget userActivity={userActivity} />
      <SessionsWidget      sessionsData={sessionsData} />

      <SecurityWidget securityOverview={securityOverview} />
      <StorageWidget  storageAnalytics={storageAnalytics} subscriptionInfo={subscriptionInfo} />

      <AuditLogWidget     recentAuditLog={recentAuditLog} />
      <QuickActionsWidget quickActions={quickActions} />

      <SystemInfoWidget systemHealth={systemHealth} />
    </DashboardLayout>
  );
}

Dashboard.layout = page => <App title="Dashboard">{page}</App>;
