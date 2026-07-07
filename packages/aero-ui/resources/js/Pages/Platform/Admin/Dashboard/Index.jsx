import { useCallback } from 'react';
import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody, Icon } from '@aero/ui';

import CommandStrip     from './sections/CommandStrip.jsx';
import HeroBand         from './sections/HeroBand.jsx';
import RevenueTrend     from './sections/RevenueTrend.jsx';
import LifecycleFunnel  from './sections/LifecycleFunnel.jsx';
import OpsPulse         from './sections/OpsPulse.jsx';
import GrowthPanel      from './sections/GrowthPanel.jsx';
import EngagementPanel  from './sections/EngagementPanel.jsx';
import LiveStream       from './sections/LiveStream.jsx';
import QuickActions     from './sections/QuickActions.jsx';
import RecentTenants    from './sections/RecentTenants.jsx';
import { PulseDot, usePolling, fmtCurrency, fmtNumber } from './lib.jsx';

import './dashboard.css';

/* Command-shell context rail — mirrors the OVERVIEW + QUICK-LINKS pattern. */
function DashboardRail({ kpis, pulse, lifecycle }) {
  const byKey = Object.fromEntries((kpis ?? []).map((k) => [k.key, k]));
  const total = lifecycle?.total ?? 0;
  const links = [
    { label: 'All tenants', href: '/tenants', icon: 'users' },
    { label: 'Billing', href: '/billing', icon: 'folder' },
    { label: 'Analytics', href: '/analytics', icon: 'trending' },
    { label: 'Error logs', href: '/error-logs', icon: 'document' },
  ];
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--aeos-space-4)' }}>
      <PulseDot status={pulse?.status ?? 'operational'} />
      <div>
        <div className="lcc-card-h__title" style={{ marginBottom: 'var(--aeos-space-3)' }}>Overview</div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--aeos-space-2)' }}>
          {[
            ['MRR', fmtCurrency(byKey.mrr?.value)],
            ['Active tenants', fmtNumber(byKey.active?.value)],
            ['Total tenants', fmtNumber(total)],
          ].map(([k, v]) => (
            <div key={k} style={{ display: 'flex', justifyContent: 'space-between', fontSize: 'var(--aeos-text-sm)' }}>
              <span style={{ color: 'var(--aeos-text-secondary)' }}>{k}</span>
              <span style={{ color: 'var(--aeos-text-primary)', fontWeight: 600, fontFamily: 'var(--aeos-font-mono)' }}>{v}</span>
            </div>
          ))}
        </div>
      </div>
      <div>
        <div className="lcc-card-h__title" style={{ marginBottom: 'var(--aeos-space-3)' }}>Quick links</div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--aeos-space-1)' }}>
          {links.map((l) => (
            <button key={l.href} type="button" className="lcc-action" style={{ flexDirection: 'row', alignItems: 'center', padding: 'var(--aeos-space-2) var(--aeos-space-3)' }} onClick={() => router.visit(l.href)}>
              <span className="lcc-action__icon"><Icon name={l.icon} size={16} /></span>
              {l.label}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}

export default function Index({ welcome, overview, live }) {
  const o = overview ?? {};
  const l = live ?? {};

  // Poll the volatile "live" subset every 30s (visibility-gated inside the hook).
  const poll = useCallback(() => {
    router.reload({ only: ['live'], preserveScroll: true, preserveState: true });
  }, []);
  usePolling(poll, 30000);

  const handleRefresh = useCallback(() => {
    router.reload({ only: ['overview', 'live'], preserveScroll: true, preserveState: true });
  }, []);

  return (
    <div className="lcc">
      <CommandStrip welcome={welcome} pulse={l.pulse} onRefresh={handleRefresh} />

      <HeroBand kpis={o.heroKpis} />

      <div className="lcc-row split-60">
        <RevenueTrend revenue={o.revenueTrend} />
        <LifecycleFunnel lifecycle={o.lifecycle} />
      </div>

      <div className="lcc-row split-50">
        <OpsPulse ops={l.ops} pulse={l.pulse} />
        <GrowthPanel growth={o.growth} />
      </div>

      <EngagementPanel engagement={o.engagement} />

      <div className="lcc-row split-60">
        <LiveStream stream={l.stream} />
        <QuickActions />
      </div>

      <RecentTenants tenants={o.recentTenants} />
    </div>
  );
}

Index.layout = (page) => {
  const { overview, live } = page.props;
  return (
    <App
      title="Dashboard"
      railTitle="Command Center"
      rail={<DashboardRail kpis={overview?.heroKpis} pulse={live?.pulse} lifecycle={overview?.lifecycle} />}
    >
      {page}
    </App>
  );
};
