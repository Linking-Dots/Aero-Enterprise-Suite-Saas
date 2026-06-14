import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Icon,
  Button,
  Badge,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function ActivityShow({ title, activity }) {
  const getActionColor = (action) => {
    switch (action) {
      case 'created': return 'success';
      case 'updated': return 'info';
      case 'deleted': return 'danger';
      case 'login': return 'success';
      case 'logout': return 'neutral';
      default: return 'secondary';
    }
  };

  const getActionIcon = (action) => {
    switch (action) {
      case 'created': return 'plus-circle';
      case 'updated': return 'pencil';
      case 'deleted': return 'trash';
      case 'login': return 'arrow-right-on-rectangle';
      case 'logout': return 'arrow-left-on-rectangle';
      case 'export': return 'download';
      case 'import': return 'upload';
      default: return 'document';
    }
  };

  return (
    <DashboardLayout
      title={title}
      actions={
        <Button onClick={() => router.visit(route('core.activity.index'))}>
          <Icon name="arrow-left" className="w-4 h-4" />
          Back to Feed
        </Button>
      }
    >
      <VStack gap={4}>
        {/* Activity Header */}
        <Card>
          <CardContent>
            <VStack gap={3}>
              <HStack gap={3} align="center">
                <Icon name={getActionIcon(activity.action)} className="w-12 h-12" tone="secondary" />
                <VStack gap={0}>
                  <HStack gap={2} align="center">
                    <Text size="xl" weight="medium">{activity.description}</Text>
                    <Badge intent={getActionColor(activity.action)}>{activity.action}</Badge>
                    {activity.module && <Badge intent="secondary">{activity.module}</Badge>}
                  </HStack>
                  <Text tone="secondary" size="sm">
                    {new Date(activity.created_at).toLocaleString()}
                  </Text>
                </VStack>
              </HStack>
            </VStack>
          </CardContent>
        </Card>

        {/* Activity Details */}
        <Card>
          <CardContent>
            <VStack gap={4}>
              <Text weight="medium" size="lg">Activity Details</Text>
              
              <VStack gap={2}>
                <Text tone="secondary" size="sm">User</Text>
                <Text>{activity.user?.name || 'System'}</Text>
              </VStack>

              {activity.tenant && (
                <VStack gap={2}>
                  <Text tone="secondary" size="sm">Tenant</Text>
                  <Text>{activity.tenant.name}</Text>
                </VStack>
              )}

              {activity.module && (
                <VStack gap={2}>
                  <Text tone="secondary" size="sm">Module</Text>
                  <Text>{activity.module}</Text>
                </VStack>
              )}

              {activity.entity_type && (
                <VStack gap={2}>
                  <Text tone="secondary" size="sm">Entity Type</Text>
                  <Text>{activity.entity_type}</Text>
                </VStack>
              )}

              {activity.entity_id && (
                <VStack gap={2}>
                  <Text tone="secondary" size="sm">Entity ID</Text>
                  <Text>{activity.entity_id}</Text>
                </VStack>
              )}

              <VStack gap={2}>
                <Text tone="secondary" size="sm">IP Address</Text>
                <Text>{activity.ip_address || 'N/A'}</Text>
              </VStack>

              <VStack gap={2}>
                <Text tone="secondary" size="sm">User Agent</Text>
                <Text size="sm">{activity.user_agent || 'N/A'}</Text>
              </VStack>

              {activity.metadata && Object.keys(activity.metadata).length > 0 && (
                <VStack gap={2}>
                  <Text tone="secondary" size="sm">Metadata</Text>
                  <Card>
                    <CardContent>
                      <VStack gap={2}>
                        {Object.entries(activity.metadata).map(([key, value]) => (
                          <HStack key={key} gap={2}>
                            <Text tone="secondary" size="sm">{key}:</Text>
                            <Text size="sm">{String(value)}</Text>
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
    </DashboardLayout>
  );
}

ActivityShow.layout = page => <App title={page.props.title}>{page}</App>;
