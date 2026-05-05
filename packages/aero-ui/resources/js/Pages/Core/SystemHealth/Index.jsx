import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  Card,
  CardContent,
  Button,
  Badge,
  VStack,
  HStack,
  Text,
  Icon,
  useHRMAC,
  useToast,
} from '@aero/ui';
import App from '../../App.jsx';
import HealthCard from '../../../components/SystemHealth/HealthCard.jsx';

export default function SystemHealthIndex({ overview, database, queue, cache, services, metrics }) {
  const { toast } = useToast();
  const canViewOverview = useHRMAC('core.system_health.overview.view');
  const canViewDatabase = useHRMAC('core.system_health.database.view');
  const canViewQueue = useHRMAC('core.system_health.queue.view');
  const canViewCache = useHRMAC('core.system_health.cache.view');
  const canViewServices = useHRMAC('core.system_health.services.view');
  const canViewMetrics = useHRMAC('core.system_health.metrics.view');

  const [healthData, setHealthData] = useState({
    overview,
    database,
    queue,
    cache,
    services,
    metrics,
  });
  const [isRefreshing, setIsRefreshing] = useState(false);

  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      const response = await fetch(route('core.system-health.refresh'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
      });
      const data = await response.json();
      setHealthData(data);
      toast({
        title: 'Success',
        description: 'Health data refreshed successfully',
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to refresh health data',
        variant: 'destructive',
      });
    } finally {
      setIsRefreshing(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'healthy':
        return 'success';
      case 'warning':
        return 'warning';
      case 'unhealthy':
        return 'danger';
      default:
        return 'neutral';
    }
  };

  return (
    <DashboardLayout
      title="System Health"
      actions={
        <Button
          intent="secondary"
          onClick={handleRefresh}
          disabled={isRefreshing}
          leftIcon="refresh"
        >
          {isRefreshing ? 'Refreshing...' : 'Refresh'}
        </Button>
      }
    >
      <VStack gap={4}>
        {/* System Overview */}
        {canViewOverview && (
          <HealthCard
            title="System Overview"
            icon="cpu-chip"
            data={healthData.overview}
            status={healthData.overview.cpu_usage > 80 ? 'warning' : 'healthy'}
          />
        )}

        {/* Database Health */}
        {canViewDatabase && (
          <HealthCard
            title="Database Health"
            icon="database"
            data={healthData.database}
            status={healthData.database.status}
          />
        )}

        {/* Queue Health */}
        {canViewQueue && (
          <HealthCard
            title="Queue Health"
            icon="queue-list"
            data={healthData.queue}
            status={healthData.queue.status}
          />
        )}

        {/* Cache Health */}
        {canViewCache && (
          <HealthCard
            title="Cache Health"
            icon="bolt"
            data={healthData.cache}
            status={healthData.cache.status}
          />
        )}

        {/* External Services */}
        {canViewServices && (
          <Card>
            <CardContent>
              <VStack gap={3}>
                <HStack gap={2} align="center">
                  <Icon name="cloud" className="w-5 h-5" />
                  <Text as="h3">External Services</Text>
                </HStack>
                <VStack gap={2}>
                  {Object.entries(healthData.services).map(([key, service]) => (
                    <HStack key={key} gap={2} align="center" justify="space-between">
                      <Text>{service.name}</Text>
                      <Badge intent={getStatusColor(service.status)}>{service.status}</Badge>
                    </HStack>
                  ))}
                </VStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        {/* Performance Metrics */}
        {canViewMetrics && (
          <Card>
            <CardContent>
              <VStack gap={3}>
                <HStack gap={2} align="center">
                  <Icon name="chart-bar" className="w-5 h-5" />
                  <Text as="h3">Performance Metrics</Text>
                </HStack>
                <VStack gap={1}>
                  {Object.entries(healthData.metrics).map(([key, value]) => (
                    <HStack key={key} gap={2} justify="space-between">
                      <Text tone="secondary">{key.replace(/_/g, ' ').toUpperCase()}:</Text>
                      <Text>{value}</Text>
                    </HStack>
                  ))}
                </VStack>
              </VStack>
            </CardContent>
          </Card>
        )}
      </VStack>
    </DashboardLayout>
  );
}

SystemHealthIndex.layout = page => <App title="System Health">{page}</App>;
