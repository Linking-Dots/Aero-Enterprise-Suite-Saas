/**
 * Tenant/Dashboard — Main tenant admin dashboard.
 *
 * Renders KPI tiles, recent activity, quick actions, and system status.
 * All data from DashboardController via Inertia props.
 */
import { usePage, Link } from '@inertiajs/react';
import {
  DashboardLayout,
  KPI, Card, VStack, HStack, Box, Text, Mono, Eyebrow, Badge, Button, Icon, Avatar,
} from '@aero/ui';
import App from '../App.jsx';

// ─── Accent intent mapping ─────────────────────────────────────────────────
function accentIntent(accent) {
  if (accent === 'indigo') return 'neutral';
  if (accent === 'amber')  return 'amber';
  return 'success';
}

// ─── Recent Activity Item ──────────────────────────────────────────────────
function ActivityItem({ event }) {
  const { action, actor, subject, created_at, type } = event ?? {};
  const typeColor = {
    created: 'var(--aeos-success)',
    updated: 'var(--aeos-primary)',
    deleted: 'var(--aeos-destructive)',
    login:   'var(--aeos-tertiary)',
  }[type] ?? 'var(--aeos-text-tertiary)';

  return (
    <HStack gap={3} align="center">
      <div className="dash-activity-dot" style={{ background: typeColor }} aria-hidden="true" />
      <VStack gap={0}>
        <Text as="span">
          <Text as="span" tone="primary">{actor}</Text>
          {' '}{action}{subject ? ` · ${subject}` : ''}
        </Text>
        <Mono tone="tertiary" size="sm">{created_at}</Mono>
      </VStack>
    </HStack>
  );
}

// ─── Quick action card ─────────────────────────────────────────────────────
function QuickAction({ icon, label, description, href }) {
  return (
    <Card interactive as={Link} href={href}>
      <HStack gap={3} align="center">
        <div className="dash-quick-icon">
          <Icon name={icon} size={18} />
        </div>
        <VStack gap={0}>
          <Text as="span">{label}</Text>
          <Text tone="secondary" as="span" size="sm">{description}</Text>
        </VStack>
        <Icon name="arrowRight" size={14} tone="tertiary" />
      </HStack>
    </Card>
  );
}

// ─── Dashboard ─────────────────────────────────────────────────────────────
export default function Dashboard({ dashboardStats = {}, recentActivity = [], tenantInfo = {} }) {
  const { auth } = usePage().props;
  const user = auth?.user;
  const firstName = (tenantInfo.name ?? user?.name ?? 'there').split(' ')[0];

  const kpis = [
    dashboardStats.totalUsers,
    dashboardStats.activeUsers,
    dashboardStats.storageUsed,
    dashboardStats.billingStatus,
  ].filter(Boolean);

  const quickActions = [
    { icon: 'users',    label: 'Manage Users',    description: 'Invite, edit, or deactivate users',  href: '/users'    },
    { icon: 'shield',   label: 'Manage Roles',    description: 'Configure access permissions',         href: '/roles'    },
    { icon: 'settings', label: 'System Settings', description: 'Configure your workspace',             href: '/settings/system' },
    { icon: 'chart',    label: 'Audit Logs',      description: 'Review system activity',              href: '/audit-logs' },
  ];

  return (
    <DashboardLayout
      title="Dashboard"
      cols={{ base: 1, md: 2, lg: 4 }}
      actions={
        <HStack gap={2}>
          <Badge intent="success">System healthy</Badge>
        </HStack>
      }
    >
      {/* Welcome banner */}
      <Card className="dash-welcome aeos-col-span-4">
        <HStack gap={4} align="center">
          <Avatar
            initials={(user?.name ?? 'U').split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase()}
            size="lg"
          />
          <VStack gap={1}>
            <Eyebrow tone="primary">Good to see you</Eyebrow>
            <Text as="h2" className="dash-welcome-title">Welcome back, {firstName}</Text>
            <Text tone="secondary">
              {tenantInfo.name ?? 'Your workspace'} is running smoothly. Here&apos;s your overview.
            </Text>
          </VStack>
        </HStack>
      </Card>

      {/* KPI tiles */}
      {kpis.map((kpi, i) => (
        <KPI
          key={i}
          label={kpi.label}
          value={kpi.value}
          delta={kpi.delta || undefined}
          deltaTrend={kpi.deltaTrend}
        />
      ))}

      {/* Recent activity */}
      <Card className="aeos-col-span-2">
        <VStack gap={4}>
          <HStack gap={2} align="center">
            <Eyebrow tone="primary">Activity</Eyebrow>
            <Box grow />
            <Button intent="ghost" size="sm" as={Link} href="/audit-logs" rightIcon="arrowRight">
              View all
            </Button>
          </HStack>

          {recentActivity.length > 0 ? (
            <VStack gap={3}>
              {recentActivity.slice(0, 8).map((event, i) => (
                <ActivityItem key={i} event={event} />
              ))}
            </VStack>
          ) : (
            <VStack gap={2} align="center">
              <Icon name="clock" size={32} tone="tertiary" />
              <Text tone="secondary">No recent activity</Text>
            </VStack>
          )}
        </VStack>
      </Card>

      {/* Quick actions */}
      <Card className="aeos-col-span-2">
        <VStack gap={4}>
          <Eyebrow tone="primary">Quick Actions</Eyebrow>
          <VStack gap={2}>
            {quickActions.map(action => (
              <QuickAction key={action.href} {...action} />
            ))}
          </VStack>
        </VStack>
      </Card>

      {/* System info */}
      <Card className="aeos-col-span-4">
        <HStack gap={6} wrap="wrap">
          <VStack gap={1}>
            <Eyebrow tone="primary">Workspace</Eyebrow>
            <Text>{tenantInfo.name ?? '—'}</Text>
            <Mono tone="secondary" size="sm">{tenantInfo.email ?? '—'}</Mono>
          </VStack>
          <div className="dash-divider-v" aria-hidden="true" />
          <VStack gap={1}>
            <Eyebrow tone="primary">Your Role</Eyebrow>
            <Text>{user?.roles?.[0] ?? 'Administrator'}</Text>
            <Mono tone="secondary" size="sm">{user?.email ?? '—'}</Mono>
          </VStack>
          <div className="dash-divider-v" aria-hidden="true" />
          <VStack gap={1}>
            <Eyebrow tone="primary">Access Level</Eyebrow>
            <HStack gap={2} align="center">
              <Badge intent={user?.is_super_admin ? 'success' : 'neutral'}>
                {user?.is_super_admin ? 'Super Admin' : 'Standard'}
              </Badge>
            </HStack>
          </VStack>
          <Box grow />
          <Button intent="ghost" size="sm" as={Link} href="/settings/system">
            Workspace settings
          </Button>
        </HStack>
      </Card>

      <style>{`
        .dash-welcome { grid-column: 1 / -1 !important; }
        .dash-welcome-title {
          font-family: var(--aeos-font-display);
          font-size: 1.4rem; font-weight: 700;
          letter-spacing: -.02em; color: var(--aeos-text-primary); margin: 0;
        }
        .dash-activity-dot {
          width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        }
        .dash-quick-icon {
          width: 36px; height: 36px; border-radius: var(--aeos-r-lg); flex-shrink: 0;
          display: flex; align-items: center; justify-content: center;
          background: rgba(0,229,255,.08); border: 1px solid rgba(0,229,255,.15);
          color: var(--aeos-primary);
        }
        .dash-divider-v {
          width: 1px; align-self: stretch;
          background: var(--aeos-divider);
        }
        @media (max-width: 1023px) {
          .dash-welcome, [style*="span 4"], [style*="span 2"] { grid-column: 1 / -1 !important; }
        }
      `}</style>
    </DashboardLayout>
  );
}

Dashboard.layout = page => <App title="Dashboard">{page}</App>;
