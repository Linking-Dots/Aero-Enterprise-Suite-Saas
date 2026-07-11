/**
 * Tenant/Dashboard — workspace command centre.
 *
 * A single operating view: personalized masthead, a 6-KPI band, a hero activity
 * analytics column with an audit feed + announcements, and a right stack of live
 * operational panels (security, sessions, storage & plan, health, onboarding).
 *
 * Rides the pc-* command-centre language (products.css) + dashboard.css. Reuses
 * the existing backend end-to-end: Inertia::lazy props from AdminDashboardService
 * plus per-widget refresh through /dashboard/widget/{key} (useWidgetRefresh).
 *
 * Dual-mode: `aero.mode` gates subscription/onboarding framing (SaaS vs standalone).
 */
import { useState, useEffect, useRef } from 'react';
import { Link, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import AiUsageCard from '@/aeon/AiUsageCard.jsx';
import DashboardRail from './Dashboard/DashboardRail.jsx';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import {
  KpiBand, ActivityPanel, ActivityFeed, AnnouncementsPanel,
  SecurityPanel, SessionsPanel, StoragePlanPanel, HealthPanel, OnboardingPanel,
  safeRoute,
} from './Dashboard/panels.jsx';

import '../Platform/Admin/Products/products.css';
import './Dashboard/dashboard.css';

/* ------------------------------------------------------------------ masthead */
function Masthead({ welcomeData, subscriptionInfo, systemHealth, quickActions, mode }) {
  const { data: sub } = useWidgetRefresh('subscriptionInfo', subscriptionInfo);
  const { data: health } = useWidgetRefresh('systemHealth', systemHealth);
  const [menu, setMenu] = useState(false);

  const isStandalone = mode === 'standalone' || sub?.plan?.slug === 'self-hosted';
  const overall = health?.overall ?? 'unknown';
  const healthMod = overall === 'healthy' ? 'ok' : overall === 'degraded' ? 'amber' : overall === 'unknown' ? 'neutral' : 'danger';

  const isOnTrial = sub?.isOnTrial ?? false;
  const daysLeft = sub?.daysRemaining ?? null;
  const planName = sub?.plan?.name ?? 'Free';

  const actionGroups = Array.isArray(quickActions) ? quickActions.filter((g) => g.items?.length) : [];
  const hasActions = actionGroups.length > 0;

  return (
    <div className="pc-head">
      <div>
        <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Workspace · Overview</div>
        <h1 className="pc-title">{welcomeData?.greeting ?? 'Welcome back'}{welcomeData?.userName ? `, ${welcomeData.userName.split(' ')[0]}` : ''}</h1>
        <div className="pc-sub">{welcomeData?.date ?? ''} · Here's everything happening across your workspace today.</div>
      </div>

      <div className="pc-actions" style={{ alignItems: 'center', flexWrap: 'wrap' }}>
        {isStandalone
          ? <span className="dash-pill dash-pill--neutral"><span className="dash-pill__dot" /> Self-hosted · Licensed</span>
          : <span className={`dash-pill dash-pill--${isOnTrial ? 'amber' : 'ok'}`}><span className="dash-pill__dot" />{planName}{isOnTrial && daysLeft !== null ? ` · Trial ${daysLeft}d` : ''}</span>}

        <span className={`dash-pill dash-pill--${healthMod}`}><span className="dash-pill__dot" />{overall === 'healthy' ? 'All systems healthy' : `System ${overall}`}</span>

        {welcomeData?.time && <span className="dash-pill dash-pill--neutral" style={{ fontFamily: 'var(--aeos-font-mono)' }}>{welcomeData.time}</span>}

        {hasActions && (
          <div className="dash-menu-wrap">
            <button type="button" className="pc-btn pc-btn--primary" onClick={() => setMenu((m) => !m)} aria-expanded={menu} aria-haspopup="menu">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round"><path d="M12 5v14M5 12h14" /></svg>
              <span>New</span>
            </button>
            {menu && (
              <>
                <div style={{ position: 'fixed', inset: 0, zIndex: 39 }} onClick={() => setMenu(false)} aria-hidden="true" />
                <div className="dash-menu" role="menu">
                  {actionGroups.map((g) => (
                    <div key={g.group}>
                      <div className="dash-menu__grp">{g.group}</div>
                      {g.items.map((it) => (
                        <Link key={it.label} href={safeRoute(it.route, '#')} className="dash-menu__item" role="menuitem" onClick={() => setMenu(false)}>
                          <span className="dash-live" style={{ background: 'var(--aeos-primary)', boxShadow: 'none' }} />{it.label}
                        </Link>
                      ))}
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

/* ================================================================= page */
export default function Dashboard({
  welcomeData = {},
  coreStats = null,
  onboardingProgress = null,
  announcements = null,
  userActivity = null,
  sessionsData = null,
  securityOverview = null,
  storageAnalytics = null,
  subscriptionInfo = null,
  recentAuditLog = null,
  quickActions = [],
  systemHealth = null,
}) {
  const mode = usePage().props?.aero?.mode ?? 'saas';

  const [period, setPeriod] = useState('week');
  const activity = useWidgetRefresh('userActivity', userActivity, { extraParams: `period=${period}` });
  const firstRun = useRef(true);
  useEffect(() => {
    if (firstRun.current) { firstRun.current = false; return; }
    activity.refresh();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [period]);

  return (
    <div className="dash">
      <Masthead
        welcomeData={welcomeData}
        subscriptionInfo={subscriptionInfo}
        systemHealth={systemHealth}
        quickActions={quickActions}
        mode={mode}
      />

      <KpiBand coreStats={coreStats} activity={activity.data} security={securityOverview} storage={storageAnalytics} />

      <div className="dash-split">
        {/* LEFT · analytics + narrative */}
        <div className="dash-col">
          <ActivityPanel activity={activity.data} loading={activity.loading} period={period} onPeriod={setPeriod} />
          <ActivityFeed recentAuditLog={recentAuditLog} />
          <AnnouncementsPanel announcements={announcements} />
        </div>

        {/* RIGHT · live operational panels */}
        <div className="dash-col">
          <SecurityPanel security={securityOverview} />
          <SessionsPanel sessionsData={sessionsData} />
          <StoragePlanPanel storageAnalytics={storageAnalytics} subscriptionInfo={subscriptionInfo} mode={mode} />
          <AiUsageCard />
          <HealthPanel systemHealth={systemHealth} />
          <OnboardingPanel onboardingProgress={onboardingProgress} mode={mode} />
        </div>
      </div>
    </div>
  );
}

Dashboard.layout = (page) => (
  <App
    title="Dashboard"
    railTitle="At a glance"
    rail={<DashboardRail quickActions={page.props.quickActions} />}
  >
    {page}
  </App>
);
