import {
  DashboardLayout,
  KPI, Stat,
  Card, CardContent,
  HStack, VStack,
  Text, Badge,
  Button,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TYPE_INTENT = { info: 'primary', warning: 'warning', success: 'success', danger: 'danger' };

export default function DashboardIndex({ stats, announcements }) {
  const s = stats ?? {};

  return (
    <DashboardLayout
      title="Dashboard"
      breadcrumb={[{ label: 'Dashboard' }]}
      cols={{ base: 1, md: 2, lg: 4 }}
    >
      {/* Stat cards */}
      <Stat
        icon="users"
        title="Total Users"
        value={Number(s.total_users ?? 0).toLocaleString()}
        description="All registered accounts"
      />
      <Stat
        icon="users"
        iconTone="success"
        title="Active Users"
        value={Number(s.active_users ?? 0).toLocaleString()}
        description="Currently enabled"
      />
      <Stat
        icon="shield"
        iconTone="indigo"
        title="Roles"
        value={Number(s.total_roles ?? 0).toLocaleString()}
        description="Configured roles"
      />
      <Stat
        icon="cube"
        iconTone="amber"
        title="Modules Enabled"
        value={Number(s.modules_enabled ?? 0).toLocaleString()}
        description="Active feature modules"
      />

      {/* Announcements */}
      {announcements && announcements.length > 0 && (
        <div className="aeos-dashboard-full-row">
          <Card>
            <CardContent>
              <VStack gap={3}>
                <HStack gap={2} align="center">
                  <Text size="sm" tone="secondary">Announcements</Text>
                </HStack>
                <VStack gap={2}>
                  {announcements.map(a => (
                    <HStack key={a.id} gap={3} align="start">
                      <Badge intent={TYPE_INTENT[a.type] ?? 'neutral'} size="sm">{a.type}</Badge>
                      <VStack gap={0}>
                        <Text size="sm">{a.title}</Text>
                        <Text size="sm" tone="secondary">
                          {a.author?.name}
                          {a.published_at
                            ? ` · ${new Date(a.published_at).toLocaleDateString()}`
                            : ''}
                        </Text>
                      </VStack>
                      {a.is_pinned && <Badge intent="warning" size="sm">Pinned</Badge>}
                    </HStack>
                  ))}
                </VStack>
              </VStack>
            </CardContent>
          </Card>
        </div>
      )}
    </DashboardLayout>
  );
}

DashboardIndex.layout = page => (
  <App title="Dashboard">{page}</App>
);
