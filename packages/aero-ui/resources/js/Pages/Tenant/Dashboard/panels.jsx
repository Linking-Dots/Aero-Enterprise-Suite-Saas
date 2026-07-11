/**
 * Dashboard command-centre panels.
 *
 * Every panel consumes the SAME backend the old widgets used (AdminDashboardService
 * via Inertia::lazy props + the /dashboard/widget/{key} refresh endpoint through
 * useWidgetRefresh). Charts use the @aero/ui primitives (AreaTrend / AreaSpark /
 * Donut / ProgressRow). Surfaces are <Card> so card-style reaches them.
 *
 * Dual-mode: `mode` ('saas' | 'standalone') gates subscription/onboarding framing.
 */
import { Link } from '@inertiajs/react';
import {
  Card, CardBody, Skeleton,
  AreaTrend, AreaSpark, Donut, ProgressRow,
} from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';

/* ------------------------------------------------------------------ helpers */
export const initials = (n) =>
  (n || 'SY').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();

export const severity = (verb) => {
  const s = String(verb || '').toLowerCase();
  if (/(fail|denied|deny|delet|destroy|remov|suspicious|revoke|block|error|lock|breach)/.test(s)) return 'danger';
  if (/(updat|export|password|impersonat|chang|disable|reset|warn)/.test(s)) return 'warning';
  if (/(creat|add|success|login|grant|restore|enable|verif|invit)/.test(s)) return 'success';
  return 'info';
};

export function fmtBytes(bytes) {
  if (!bytes) return '0 B';
  const gb = bytes / 1024 ** 3;
  if (gb >= 1) return `${gb.toFixed(1)} GB`;
  const mb = bytes / 1024 ** 2;
  if (mb >= 1) return `${mb.toFixed(0)} MB`;
  return `${(bytes / 1024).toFixed(0)} KB`;
}

const cssVar = (name) => `var(${name})`;
const COL = { primary: cssVar('--aeos-primary'), success: cssVar('--aeos-success'), warning: cssVar('--aeos-warning'), danger: cssVar('--aeos-danger') };

const shortDate = (iso) => { try { return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return ''; } };

/* ---------------------------------------------------------------- panel head */
function PanelHead({ title, sub, right, live }) {
  return (
    <div className="dash-ph">
      <div>
        <h3 className="dash-ph__t">{live && <span className="dash-live" style={{ marginRight: 6 }} />}{title}</h3>
        {sub && <div className="dash-ph__s">{sub}</div>}
      </div>
      {right}
    </div>
  );
}

/* ================================================================= KPI band */
function toSpark(chartData = [], key, n = 7) {
  if (!Array.isArray(chartData) || !chartData.length) return [];
  return chartData.slice(-n).map((d) => Number(d?.[key] ?? 0));
}

export function KpiBand({ coreStats, activity, security, storage }) {
  const { data: stats, loading } = useWidgetRefresh('coreStats', coreStats);
  const { data: sec } = useWidgetRefresh('securityOverview', security);
  const { data: stor } = useWidgetRefresh('storageAnalytics', storage);
  const chart = activity?.chartData ?? [];

  if (loading || !stats) {
    return (
      <div className="dash-kpis">
        {[0, 1, 2, 3, 4, 5].map((i) => <Card key={i}><CardBody><Skeleton h={92} /></CardBody></Card>)}
      </div>
    );
  }

  const mfa = sec?.mfaAdoptionPercent ?? 0;
  const failed = sec?.failedLoginsLast24h ?? 0;
  const storePct = stor?.usagePercentage ?? 0;
  const storeCapped = (stor?.totalBytes ?? 0) > 0;

  const tiles = [
    {
      label: 'Total users', value: (stats.totalUsers ?? 0).toLocaleString(),
      delta: stats.newUsersThisMonth > 0 ? `↑ ${stats.newUsersThisMonth} this month` : 'No new users this month',
      deltaMod: stats.newUsersThisMonth > 0 ? 'up' : '', spark: toSpark(chart, 'logins'), sparkColor: COL.primary,
    },
    {
      label: 'Online now', live: true, value: (stats.onlineUsers ?? 0).toLocaleString(),
      delta: stats.newUsersThisWeek > 0 ? `↑ ${stats.newUsersThisWeek} joined this week` : 'Active in last 5 min',
      deltaMod: stats.newUsersThisWeek > 0 ? 'up' : '', spark: toSpark(chart, 'activeUsers'), sparkColor: COL.success,
    },
    {
      label: 'Active users', value: (stats.activeUsers ?? 0).toLocaleString(),
      delta: `${stats.inactiveUsers ?? 0} inactive`, spark: toSpark(chart, 'newUsers'), sparkColor: COL.primary,
    },
    {
      label: 'Failed logins 24h', value: (failed ?? 0).toLocaleString(),
      delta: failed > 0 ? '⚠ review security' : 'No failed attempts',
      deltaMod: failed > 0 ? 'warn' : '', spark: [], sparkColor: COL.warning,
    },
    {
      label: 'MFA adoption', value: mfa, unit: '%', delta: `${sec?.recentNewDevices ?? 0} recent devices`,
      bar: { value: mfa, mod: mfa >= 60 ? 'is-ok' : 'is-warn' },
    },
    {
      label: 'Storage used', value: storeCapped ? storePct : fmtBytes(stor?.usedBytes ?? 0), unit: storeCapped ? '%' : '',
      delta: storeCapped ? `${fmtBytes(stor?.usedBytes ?? 0)} of ${fmtBytes(stor?.totalBytes ?? 0)}` : 'No cap',
      bar: storeCapped ? { value: storePct, mod: storePct > 85 ? 'is-warn' : '' } : null,
    },
  ];

  return (
    <div className="dash-kpis">
      {tiles.map((t) => (
        <Card key={t.label}><CardBody className="dash-kpi">
          <div className="dash-kpi__label">{t.live && <span className="dash-live" />}{t.label}</div>
          <div className="dash-kpi__value">{t.value}{t.unit && <small>{t.unit}</small>}</div>
          <div className={`dash-kpi__delta${t.deltaMod ? ` dash-kpi__delta--${t.deltaMod}` : ''}`}>{t.delta}</div>
          {t.bar
            ? <div className="dash-kpi__bar dash-track"><i className={t.bar.mod} style={{ width: `${Math.min(100, t.bar.value)}%` }} /></div>
            : (t.spark?.length > 1 && <AreaSpark className="dash-kpi__spark" data={t.spark} color={t.sparkColor} />)}
        </CardBody></Card>
      ))}
    </div>
  );
}

/* ============================================================ activity panel */
export function ActivityPanel({ activity, loading, period, onPeriod }) {
  const chart = activity?.chartData ?? [];
  const labels = chart.map((d) => shortDate(d.date));
  const series = [
    { key: 'logins', label: 'Logins', color: COL.primary, values: chart.map((d) => d.logins ?? 0) },
    { key: 'activeUsers', label: 'Active users', color: COL.success, fill: false, values: chart.map((d) => d.activeUsers ?? 0) },
  ];
  const peak = (activity?.peakHours ?? []).slice().sort((a, b) => (a.hour ?? 0) - (b.hour ?? 0));
  const peakMax = Math.max(1, ...peak.map((p) => p.count ?? 0));

  const periods = [['week', '7 days'], ['month', '30 days'], ['quarter', '90 days']];

  return (
    <Card><CardBody>
      <PanelHead
        title="User activity & engagement"
        sub="Logins and active users over time"
        right={
          <div className="dash-legend" aria-hidden="true">
            <span><i style={{ background: COL.primary }} />Logins</span>
            <span><i style={{ background: COL.success }} />Active users</span>
          </div>
        }
      />
      <div className="pc-seg" role="tablist" style={{ width: 'fit-content', marginBottom: 'var(--aeos-space-3)' }}>
        {periods.map(([v, l]) => (
          <button key={v} type="button" className="pc-seg__b" aria-pressed={period === v} onClick={() => onPeriod(v)}>{l}</button>
        ))}
      </div>
      {loading && !chart.length
        ? <Skeleton h={170} />
        : <AreaTrend series={series} labels={labels} height={170} ariaLabel="User activity trend" empty="Not enough activity yet." />}

      {peak.length > 0 && (
        <>
          <div className="dash-peak-h">Peak login hours</div>
          <div className="dash-dev">
            {peak.slice(0, 6).map((p) => (
              <div className="dash-dev__r" key={p.hour}>
                <span>{fmtHour(p.hour)}</span>
                <div className="dash-track"><i style={{ width: `${Math.round(((p.count ?? 0) / peakMax) * 100)}%` }} /></div>
                <b>{p.count ?? 0}</b>
              </div>
            ))}
          </div>
        </>
      )}
    </CardBody></Card>
  );
}
const fmtHour = (h) => { const n = Number(h ?? 0); const am = n < 12; const v = n % 12 || 12; return `${v}${am ? 'am' : 'pm'}`; };

/* ================================================================ feed panel */
export function ActivityFeed({ recentAuditLog }) {
  const { data, loading } = useWidgetRefresh('recentAuditLog', recentAuditLog);
  const rows = Array.isArray(data) ? data.slice(0, 6) : [];

  return (
    <Card><CardBody>
      <PanelHead title="Recent activity" sub="Latest business events across the workspace"
        right={<Link href="/audit-logs" className="dash-link">View all audit logs →</Link>} />
      {loading && !rows.length ? <Skeleton h={220} />
        : rows.length === 0 ? <div className="dash-empty">No activity recorded yet.</div>
        : (
          <div className="dash-feed">
            {rows.map((r) => (
              <div className="dash-frow" key={r.id}>
                <span className="dash-av">{initials(r.user)}</span>
                <div className="dash-fmain">
                  <div className="dash-fmain__u">{r.user || 'System'}<span className={`dash-chip dash-chip--${severity(r.action)}`}>{r.action}</span></div>
                  <div className="dash-fmain__m">{r.description || r.auditableType || '—'}</div>
                </div>
                <span className="dash-ftime">{r.timeAgo}</span>
              </div>
            ))}
          </div>
        )}
    </CardBody></Card>
  );
}

/* ============================================================ security panel */
export function SecurityPanel({ security }) {
  const { data, loading } = useWidgetRefresh('securityOverview', security);
  const mfa = data?.mfaAdoptionPercent ?? 0;
  const ev = data?.lastSecurityEvent;

  return (
    <Card><CardBody>
      <PanelHead title="Security posture" right={<Link href="/audit-logs?tab=security" className="dash-link">Open →</Link>} />
      {loading && !data ? <Skeleton h={140} /> : (
        <>
          <div className="dash-donutrow">
            <Donut size={84} thickness={11}
              segments={[{ color: mfa >= 60 ? COL.success : COL.warning, value: mfa }, { color: 'transparent', value: Math.max(0, 100 - mfa) }]}
              centerValue={`${mfa}%`} centerLabel="MFA" />
            <div className="dash-drows">
              <div className="dash-drow"><span>Failed logins (24h)</span><b className={data?.failedLoginsLast24h > 0 ? 'is-warn' : ''}>{data?.failedLoginsLast24h ?? 0}</b></div>
              <div className="dash-drow"><span>Failed logins (7d)</span><b>{data?.failedLoginsWeek ?? 0}</b></div>
              <div className="dash-drow"><span>Active sessions</span><b>{data?.activeSessions ?? 0}</b></div>
              <div className="dash-drow"><span>Recent devices</span><b>{data?.recentNewDevices ?? 0}</b></div>
            </div>
          </div>
          {ev && (
            <div className="dash-note">Last event · <b>{ev.action}</b>{ev.actor_name ? ` by ${ev.actor_name}` : ''}{ev.created_at ? ` · ${timeAgo(ev.created_at)}` : ''}</div>
          )}
        </>
      )}
    </CardBody></Card>
  );
}
const timeAgo = (iso) => { try { const s = (Date.now() - new Date(iso).getTime()) / 1000; if (s < 60) return 'just now'; if (s < 3600) return `${Math.floor(s / 60)}m ago`; if (s < 86400) return `${Math.floor(s / 3600)}h ago`; return `${Math.floor(s / 86400)}d ago`; } catch { return ''; } };

/* ============================================================ sessions panel */
const DEVICE_ORDER = ['desktop', 'mobile', 'tablet', 'other'];
export function SessionsPanel({ sessionsData }) {
  const { data, loading } = useWidgetRefresh('sessionsData', sessionsData);
  const recent = (data?.recentSessions ?? []).slice(0, 4);
  const dev = data?.deviceBreakdown ?? {};
  const devTotal = Object.values(dev).reduce((a, b) => a + Number(b || 0), 0) || 1;
  const devRows = Object.entries(dev)
    .sort((a, b) => DEVICE_ORDER.indexOf(a[0]) - DEVICE_ORDER.indexOf(b[0]))
    .slice(0, 4);

  return (
    <Card><CardBody>
      <PanelHead title="Live sessions" live right={<Link href="/settings/system" className="dash-link">Manage →</Link>} />
      {loading && !data ? <Skeleton h={160} /> : (
        <>
          <div className="dash-mini3">
            <div className="dash-mini"><div className="dash-mini__v">{data?.onlineNow ?? 0}</div><div className="dash-mini__l">Online now</div></div>
            <div className="dash-mini"><div className="dash-mini__v">{data?.activeToday ?? 0}</div><div className="dash-mini__l">Today</div></div>
            <div className="dash-mini"><div className="dash-mini__v">{data?.activeThisWeek ?? 0}</div><div className="dash-mini__l">This week</div></div>
          </div>
          {devRows.length > 0 && (
            <div className="dash-dev">
              {devRows.map(([k, v]) => {
                const pct = Math.round((Number(v) / devTotal) * 100);
                return (
                  <div className="dash-dev__r" key={k}>
                    <span style={{ textTransform: 'capitalize' }}>{k}</span>
                    <div className="dash-track"><i style={{ width: `${pct}%` }} /></div>
                    <b>{pct}%</b>
                  </div>
                );
              })}
            </div>
          )}
          {recent.length > 0 && (
            <div className="dash-slist">
              {recent.map((s, i) => (
                <div className="dash-srow" key={i}>
                  <span className="dash-srow__dot" style={{ background: s.isOnline ? COL.success : cssVar('--aeos-text-muted'), boxShadow: s.isOnline ? `0 0 0 3px var(--aeos-success-tint, rgba(34,197,94,.12))` : 'none' }} />
                  <span className="dash-srow__nm">{s.user}</span>
                  <span className="dash-srow__ip">{s.ip}</span>
                  <span className="dash-srow__tm">{s.timeAgo}</span>
                </div>
              ))}
            </div>
          )}
        </>
      )}
    </CardBody></Card>
  );
}

/* ======================================================= storage & plan panel */
export function StoragePlanPanel({ storageAnalytics, subscriptionInfo, mode }) {
  const { data: stor, loading: sl } = useWidgetRefresh('storageAnalytics', storageAnalytics);
  const { data: sub, loading: subl } = useWidgetRefresh('subscriptionInfo', subscriptionInfo);
  const loading = sl || subl;

  const isStandalone = mode === 'standalone' || sub?.plan?.slug === 'self-hosted';
  const usedBytes = stor?.usedBytes ?? 0;
  const totalBytes = stor?.totalBytes ?? 0;
  const storeCapped = totalBytes > 0;
  const storePct = storeCapped ? Math.round((usedBytes / totalBytes) * 100) : 0;

  const seats = sub?.quotaUsage?.users ?? {};
  const usedSeats = seats.used ?? 0;
  const seatLimit = seats.limit;
  const seatsCapped = typeof seatLimit === 'number' && seatLimit > 0;
  const seatPct = seatsCapped ? Math.round((usedSeats / seatLimit) * 100) : 0;

  const isOnTrial = sub?.isOnTrial ?? false;
  const daysLeft = sub?.daysRemaining ?? null;
  const planName = sub?.plan?.name ?? 'Free';

  return (
    <Card><CardBody>
      <PanelHead
        title={isStandalone ? 'Storage & license' : 'Storage & plan'}
        sub={isStandalone ? 'Self-hosted deployment' : (isOnTrial && daysLeft !== null ? `${planName} · Trial ends in ${daysLeft} days` : `${planName} plan`)}
        right={<span className={`dash-pill dash-pill--${isStandalone ? 'neutral' : isOnTrial ? 'amber' : 'ok'}`}><span className="dash-pill__dot" />{isStandalone ? 'Licensed' : isOnTrial ? 'Trial' : planName}</span>}
      />
      {loading && !stor ? <Skeleton h={120} /> : (
        <>
          <div style={{ marginBottom: 'var(--aeos-space-3)' }}>
            <ProgressRow label={storeCapped ? `Storage · ${fmtBytes(usedBytes)} / ${fmtBytes(totalBytes)}` : `Storage · ${fmtBytes(usedBytes)} used (no cap)`}
              value={storePct} max={100} intent={storePct > 85 ? 'amber' : 'cyan'} />
          </div>
          <ProgressRow label={seatsCapped ? `User seats · ${usedSeats} / ${seatLimit}` : `User seats · ${usedSeats} (unlimited)`}
            value={seatsCapped ? seatPct : 100} max={100} intent={seatsCapped && seatPct > 85 ? 'amber' : 'success'} />

          {!isStandalone && isOnTrial && (
            <Link href={safeRoute('core.settings.usage.index', '/subscription')} className="pc-btn pc-btn--primary dash-upgrade">Upgrade plan →</Link>
          )}
          {!isStandalone && !isOnTrial && (
            <Link href={safeRoute('core.settings.usage.index', '/subscription')} className="pc-btn dash-upgrade">Manage plan →</Link>
          )}
        </>
      )}
    </CardBody></Card>
  );
}

/* ============================================================== health panel */
const HEALTH_LABEL = { healthy: 'Operational', degraded: 'Degraded', unhealthy: 'Down', unknown: 'Unknown' };
export function HealthPanel({ systemHealth }) {
  const { data, loading } = useWidgetRefresh('systemHealth', systemHealth);
  const overall = data?.overall ?? 'unknown';
  const services = data?.services ?? [];

  return (
    <Card><CardBody>
      <PanelHead title="System health"
        right={<span className={`dash-pill dash-pill--${overall === 'healthy' ? 'ok' : overall === 'degraded' ? 'amber' : 'danger'}`}><span className="dash-pill__dot" />{overall === 'healthy' ? 'Healthy' : overall}</span>} />
      {loading && !data ? <Skeleton h={140} /> : (
        <>
          {services.map((s) => {
            const st = s.status || 'unknown';
            return (
              <div className="dash-hrow" key={s.name}>
                <span>{s.name}</span>
                <span className={`dash-hstat dash-hstat--${st}`}><span className="dash-hstat__dot" />{HEALTH_LABEL[st] ?? st}</span>
              </div>
            );
          })}
          <div className="dash-hrow"><span>Failed jobs</span><b>{data?.failedJobs ?? 0}</b></div>
          <div className="dash-hrow"><span>Errors today</span><b>{data?.errorCountToday ?? 0}</b></div>
        </>
      )}
    </CardBody></Card>
  );
}

/* ========================================================== onboarding panel */
export function OnboardingPanel({ onboardingProgress, mode }) {
  const { data } = useWidgetRefresh('onboardingProgress', onboardingProgress);
  // Standalone (no tenant) or already-complete / >30-day tenants → service returns null.
  if (mode === 'standalone' || !data || data.completed) return null;

  return (
    <Card><CardBody>
      <PanelHead title="Finish setup" right={<span className="dash-ph__s" style={{ fontFamily: 'var(--aeos-font-mono)' }}>{data.completedCount} / {data.totalSteps}</span>} />
      <div className="dash-track"><i className="is-ok" style={{ width: `${data.percentage}%` }} /></div>
      <div style={{ marginTop: 'var(--aeos-space-2)' }}>
        {(data.steps ?? []).map((s) => (
          <div className={`dash-chk${s.completed ? ' dash-chk--done' : ''}`} key={s.key}>
            <span className={`dash-chk__box${s.completed ? ' dash-chk__box--done' : ''}`}>{s.completed ? '✓' : ''}</span>
            <span className="dash-chk__lbl">{s.label}</span>
            {!s.completed && s.route && <Link href={safeRoute(s.route, '#')} className="dash-link">Go →</Link>}
          </div>
        ))}
      </div>
    </CardBody></Card>
  );
}

/* ======================================================= announcements panel */
const ANN_TAG = { info: 'info', success: 'info', warning: 'warn', danger: 'warn', maintenance: 'warn' };
export function AnnouncementsPanel({ announcements }) {
  const { data } = useWidgetRefresh('announcements', announcements);
  const items = Array.isArray(data) ? data : [];
  if (!items.length) return null;

  return (
    <Card><CardBody>
      <PanelHead title="Announcements" right={<Link href="/notifications" className="dash-link">All →</Link>} />
      <div className="dash-ann">
        {items.slice(0, 4).map((a) => (
          <div className={`dash-arow${a.isPinned ? ' dash-arow--pinned' : ''}`} key={a.id}>
            <span className={`dash-atag dash-atag--${a.isPinned ? '' : (ANN_TAG[a.type] ?? 'info')}`.trim()}>
              {a.isPinned ? '📌 Pinned' : (a.type ? a.type[0].toUpperCase() + a.type.slice(1) : 'Notice')}
            </span>
            <div style={{ minWidth: 0, flex: 1 }}>
              <div className="dash-atitle">{a.title}</div>
              {a.body && <div className="dash-abody">{a.body}</div>}
              <div className="dash-ameta">{a.authorName || 'System'} · {timeAgo(a.createdAt)}</div>
            </div>
          </div>
        ))}
      </div>
    </CardBody></Card>
  );
}

/* ---------------------------------------------------------------- safe route */
export function safeRoute(name, fallback = '#') {
  try {
    if (typeof route === 'function' && route().has(name)) return route(name);
  } catch { /* ziggy missing name */ }
  return fallback;
}
