import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  KPI,
  Button,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Text,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function OnboardingDashboard({ stats }) {
  const kpis = [
    {
      label:       'Pending Approvals',
      value:       stats?.pending_approvals ?? 0,
      routeName:   'platform.admin.onboarding.pending',
      deltaTrend:  'neutral',
    },
    {
      label:      'Provisioning Running',
      value:      stats?.provisioning ?? 0,
      routeName:  'platform.admin.onboarding.provisioning',
      deltaTrend: 'neutral',
    },
    {
      label:      'Active Trials',
      value:      stats?.trials ?? 0,
      routeName:  'platform.admin.onboarding.trials',
      deltaTrend: 'up',
    },
    {
      label:      "Today's Signups",
      value:      stats?.today_signups ?? 0,
      routeName:  null,
      deltaTrend: 'up',
    },
  ];

  return (
    <DashboardLayout
      title="Onboarding Dashboard"
      breadcrumb={[
        { label: 'Platform Admin' },
        { label: 'Onboarding' },
      ]}
      description="Monitor tenant onboarding health across approval, provisioning, and trial pipelines."
    >
      <VStack gap={6}>
        <Eyebrow>Pipeline Overview</Eyebrow>

        <HStack gap={4} wrap>
          {kpis.map((kpi) => (
            <Card key={kpi.label} className="pa1-stat-card">
              <CardBody>
                <VStack gap={2}>
                  <KPI
                    label={kpi.label}
                    value={kpi.value}
                    deltaTrend={kpi.deltaTrend}
                  />
                  {kpi.routeName && (
                    <Button
                      intent="ghost"
                      size="sm"
                      onClick={() => router.get(route(kpi.routeName))}
                    >
                      View all
                    </Button>
                  )}
                </VStack>
              </CardBody>
            </Card>
          ))}
        </HStack>

        <HStack gap={3} wrap>
          <Button
            intent="soft"
            onClick={() => router.get(route('platform.admin.tenants.index'))}
          >
            All Tenants
          </Button>
          <Button
            intent="soft"
            onClick={() => router.get(route('platform.admin.onboarding.pending'))}
          >
            Pending Approvals
          </Button>
          <Button
            intent="soft"
            onClick={() => router.get(route('platform.admin.onboarding.provisioning'))}
          >
            Provisioning Queue
          </Button>
          <Button
            intent="soft"
            onClick={() => router.get(route('platform.admin.onboarding.trials'))}
          >
            Trial Management
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('platform.admin.tenants.bulk.history'))}
          >
            Bulk Operations
          </Button>
        </HStack>
      </VStack>
    </DashboardLayout>
  );
}

OnboardingDashboard.layout = page => <App title="Onboarding Dashboard">{page}</App>;
