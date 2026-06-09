import { useCallback, useRef } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardBody } from '@aero/ui';

import AppLayout   from '@/layouts/AppLayout';
import CommandBar  from './widgets/CommandBar';
import KpiStrip    from './widgets/KpiStrip';
import MrrTrendWidget              from './widgets/MrrTrendWidget';
import SystemAlertsWidget          from './widgets/SystemAlertsWidget';
import TenantPipelineWidget        from './widgets/TenantPipelineWidget';
import SubscriptionDistributionWidget from './widgets/SubscriptionDistributionWidget';
import RecentTenantsWidget         from './widgets/RecentTenantsWidget';
import SystemHealthWidget          from './widgets/SystemHealthWidget';
import ModuleAdoptionWidget        from './widgets/ModuleAdoptionWidget';
import RecentActivityWidget        from './widgets/RecentActivityWidget';
import QuickActionsWidget          from './widgets/QuickActionsWidget';
import DraggableDashboard          from './widgets/DraggableDashboard';

import './dashboard.css';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** Derive overall system status from health + alert data */
function resolveSystemStatus(systemHealth, systemAlerts) {
  if (systemAlerts?.hasCritical) return 'critical';
  const h = systemHealth ?? {};
  const cpu    = Number(h.cpu    ?? h.cpuUsage    ?? 0);
  const memory = Number(h.memory ?? h.memoryUsage ?? 0);
  const disk   = Number(h.disk   ?? h.diskUsage   ?? 0);
  if (cpu >= 90 || memory >= 90 || disk >= 90) return 'critical';
  if (cpu >= 70 || memory >= 75 || disk >= 75)  return 'degraded';
  return 'operational';
}

// ─── Component ───────────────────────────────────────────────────────────────

/**
 * Platform Admin Dashboard — Index
 *
 * Props (all optional with safe fallbacks):
 *  - welcome                  : { greeting, userName, date }
 *  - stats                    : PlatformStatsWidget data
 *  - billingOverview          : BillingOverviewWidget data  { revenue, trends }
 *  - systemAlerts             : SystemAlertsWidget data    { alerts[], totalCount, hasCritical }
 *  - systemHealth             : SystemHealthWidget data    { cpu, memory, disk, services }
 *  - recentTenants            : RecentTenantsWidget data   Tenant[]
 *  - moduleUsage              : ModuleUsageWidget data     { modules[], totalTenants }
 *  - subscriptionDistribution : SubscriptionDistributionWidget data { plans[] }
 *  - recentActivity           : RecentActivityWidget data  { activities[] }
 *  - quickActions             : QuickActionsWidget data    { actions[] }
 */
export default function Index({
  welcome,
  stats,
  billingOverview,
  systemAlerts,
  systemHealth,
  recentTenants,
  moduleUsage,
  subscriptionDistribution,
  recentActivity,
  quickActions,
}) {
  const reloadRef = useRef(false);

  const handleRefresh = useCallback(() => {
    if (reloadRef.current) return;
    reloadRef.current = true;
    router.reload({
      only: [
        'stats',
        'billingOverview',
        'systemAlerts',
        'systemHealth',
        'recentTenants',
        'moduleUsage',
        'subscriptionDistribution',
        'recentActivity',
      ],
      onFinish: () => { reloadRef.current = false; },
    });
  }, []);

  const systemStatus   = resolveSystemStatus(systemHealth, systemAlerts);
  const alertCount     = systemAlerts?.totalCount ?? 0;

  // ── Sortable widget definitions ──────────────────────────────────────────
  //    Each entry has a stable `id` and the rendered `node`.
  //    The top-pinned rows (command bar + KPI strip) are NOT draggable.

  const draggableItems = [
    {
      id: 'mrr-alerts',
      node: (
        <div className="aeos-dashboard__grid-2">
          <MrrTrendWidget billingOverview={billingOverview} stats={stats} />
          <SystemAlertsWidget systemAlerts={systemAlerts} />
        </div>
      ),
    },
    {
      id: 'pipeline-dist',
      node: (
        <div className="aeos-dashboard__grid-equal">
          <TenantPipelineWidget stats={stats} />
          <SubscriptionDistributionWidget subscriptionDistribution={subscriptionDistribution} />
        </div>
      ),
    },
    {
      id: 'tenants-health',
      node: (
        <div className="aeos-dashboard__grid-2">
          <RecentTenantsWidget recentTenants={recentTenants} />
          <SystemHealthWidget systemHealth={systemHealth} />
        </div>
      ),
    },
    {
      id: 'module-adoption',
      node: (
        <div className="aeos-dashboard__full">
          <ModuleAdoptionWidget moduleUsage={moduleUsage} />
        </div>
      ),
    },
    {
      id: 'activity-actions',
      node: (
        <div className="aeos-dashboard__grid-bottom">
          <RecentActivityWidget recentActivity={recentActivity} />
          <QuickActionsWidget quickActions={quickActions} />
        </div>
      ),
    },
  ];

  return (
    <AppLayout title="Dashboard">
      <div className="aeos-dashboard">

        {/* ── 1. Command bar — always on top, never draggable ── */}
        <div className="aeos-dashboard__command-bar">
          <Card>
            <CardBody style={{ padding: 'var(--aeos-space-3) var(--aeos-pad-card)' }}>
              <CommandBar
                welcome={welcome}
                systemStatus={systemStatus}
                alertCount={alertCount}
                onRefresh={handleRefresh}
              />
            </CardBody>
          </Card>
        </div>

        {/* ── 2. KPI strip — always pinned below command bar ── */}
        <div className="aeos-dashboard__kpi-strip">
          <KpiStrip stats={stats} billingOverview={billingOverview} />
        </div>

        {/* ── 3–7. Draggable widget rows ── */}
        <DraggableDashboard
          items={draggableItems}
          style={{
            display: 'flex',
            flexDirection: 'column',
            gap: 'var(--aeos-space-5)',
            width: '100%',
            minWidth: 0,
          }}
        />

      </div>
    </AppLayout>
  );
}
