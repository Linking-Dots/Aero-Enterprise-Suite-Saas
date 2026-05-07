import { Card, CardContent, Badge, VStack, HStack, Text, Icon } from '@aero/ui';

export default function HealthCard({ title, icon, data, status }) {
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
    <Card>
      <CardContent>
        <VStack gap={3}>
          <HStack gap={2} align="center" justify="space-between">
            <HStack gap={2} align="center">
              <Icon name={icon} style={{ width: 'var(--aeos-icon-md)', height: 'var(--aeos-icon-md)' }} />
              <Text as="h3">{title}</Text>
            </HStack>
            <Badge intent={getStatusColor(status)}>{status}</Badge>
          </HStack>
          <VStack gap={1}>
            {Object.entries(data).map(([key, value]) => (
              <HStack key={key} gap={2} justify="space-between">
                <Text tone="secondary">{key.replace(/_/g, ' ').toUpperCase()}:</Text>
                <Text>{typeof value === 'object' ? JSON.stringify(value) : value}</Text>
              </HStack>
            ))}
          </VStack>
        </VStack>
      </CardContent>
    </Card>
  );
}
