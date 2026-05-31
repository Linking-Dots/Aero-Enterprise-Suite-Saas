import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Avatar,
  Icon,
  Badge,
} from '@aero/ui';

export default function ActivityFeedIndex({ title }) {
  const [activities, setActivities] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchActivities = async () => {
    setLoading(true);
    try {
      // For now, this is a placeholder. The actual activity feed would need
      // a backend service to track and return user activities across the system.
      // This could be implemented using the existing audit logs or a dedicated activity log table.
      const response = await fetch(route('core.audit-logs.activity.index'));
      const data = await response.json();
      setActivities(data.data || []);
    } catch (error) {
      console.error('Failed to load activities:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchActivities();
  }, []);

  const getActivityIcon = (action) => {
    switch (action) {
      case 'created':
        return 'plus-circle';
      case 'updated':
        return 'pencil';
      case 'deleted':
        return 'trash';
      case 'viewed':
        return 'eye';
      default:
        return 'information-circle';
    }
  };

  const getActivityColor = (action) => {
    switch (action) {
      case 'created':
        return 'success';
      case 'updated':
        return 'warning';
      case 'deleted':
        return 'danger';
      default:
        return 'neutral';
    }
  };

  return (
    <DashboardLayout title={title}>
      <VStack gap={4}>
        {loading ? (
          <Text tone="secondary">Loading activity feed...</Text>
        ) : activities.length === 0 ? (
          <Card>
            <CardContent>
              <VStack gap={4} align="center">
                <Icon name="chart-bar" className="w-12 h-12" tone="secondary" />
                <Text tone="secondary">No recent activity</Text>
              </VStack>
            </CardContent>
          </Card>
        ) : (
          activities.map((activity) => (
            <Card key={activity.id}>
              <CardContent>
                <HStack gap={3} align="center">
                  <Avatar
                    src={activity.user?.avatar_url}
                    alt={activity.user?.name}
                    size="sm"
                  />
                  <VStack gap={0} className="aeos-flex-1">
                    <HStack gap={2} align="center">
                      <Text weight="medium">{activity.user?.name}</Text>
                      <Text tone="secondary">
                        {activity.action} {activity.subject_type}
                      </Text>
                      <Badge intent={getActivityColor(activity.action)}>
                        {activity.action}
                      </Badge>
                    </HStack>
                    <Text tone="secondary" size="sm">
                      {new Date(activity.created_at).toLocaleString()}
                    </Text>
                  </VStack>
                  <Icon
                    name={getActivityIcon(activity.action)}
                    className="w-5 h-5"
                    tone="secondary"
                  />
                </HStack>
              </CardContent>
            </Card>
          ))
        )}
      </VStack>
    </DashboardLayout>
  );
}
