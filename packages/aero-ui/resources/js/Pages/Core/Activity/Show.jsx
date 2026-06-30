import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Mono,
  Badge,
  Button,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ACTION_INTENT = {
  created: 'success', updated: 'neutral', deleted: 'danger',
  login: 'success', logout: 'neutral', export: 'warning', import: 'warning',
};

function Field({ label, children }) {
  return (
    <VStack gap={1}>
      <Text tone="secondary" size="sm">{label}</Text>
      <Text size="sm">{children}</Text>
    </VStack>
  );
}

export default function ActivityShow({ activity }) {
  return (
    <IndexPageLayout
      title="Activity details"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Activity Feed', href: route('core.activity.index') },
        { label: 'Details' },
      ]}
      description={activity?.description || ''}
      actions={
        <Button intent="ghost" type="button" leftIcon="arrowLeft"
          onClick={() => router.visit(route('core.activity.index'))}>Back to feed</Button>
      }
      table={
        <VStack gap={4}>
          <Card>
            <CardContent>
              <HStack gap={2} align="center" wrap>
                <Text size="lg" weight={600}>{activity?.description || '—'}</Text>
                <Badge intent={ACTION_INTENT[activity?.action] ?? 'neutral'}>{activity?.action || '—'}</Badge>
                {activity?.module && <Badge intent="indigo" size="sm">{activity.module}</Badge>}
              </HStack>
              <Text tone="secondary" size="sm">
                {activity?.created_at ? new Date(activity.created_at).toLocaleString() : '—'}
              </Text>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight={600} size="md">Details</Text>
                <Field label="User">{activity?.user?.name || 'System'}</Field>
                {activity?.tenant && <Field label="Tenant">{activity.tenant.name}</Field>}
                {activity?.module && <Field label="Module">{activity.module}</Field>}
                {activity?.entity_type && <Field label="Entity type">{activity.entity_type}</Field>}
                {activity?.entity_id && <Field label="Entity ID"><Mono size="sm">{activity.entity_id}</Mono></Field>}
                <Field label="IP address"><Mono size="sm">{activity?.ip_address || 'N/A'}</Mono></Field>
                <Field label="User agent">{activity?.user_agent || 'N/A'}</Field>
                {activity?.metadata && Object.keys(activity.metadata).length > 0 && (
                  <VStack gap={1}>
                    <Text tone="secondary" size="sm">Metadata</Text>
                    <Card>
                      <CardContent>
                        <VStack gap={2}>
                          {Object.entries(activity.metadata).map(([k, v]) => (
                            <HStack key={k} gap={2}>
                              <Text tone="secondary" size="sm">{k}:</Text>
                              <Text size="sm">{String(v)}</Text>
                            </HStack>
                          ))}
                        </VStack>
                      </CardContent>
                    </Card>
                  </VStack>
                )}
              </VStack>
            </CardContent>
          </Card>
        </VStack>
      }
    />
  );
}

ActivityShow.layout = page => <App title="Activity details">{page}</App>;
