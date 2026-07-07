import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Eyebrow,
  Card, CardBody,
  useToast,
  useTheme,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import { startTour as startGuidedTour } from '@/tour/useTour.jsx';

export default function OnboardingTours({ tours = [] }) {
  const toast = useToast();
  const theme = useTheme();

  const startTour = id => {
    router.post(`/help/tours/${id}/start`, {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Tour started.'),
      onError:   () => toast.error('Failed to start tour.'),
    });
  };

  return (
    <IndexPageLayout
      title="Onboarding Tours"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Help Center', href: '/help' },
        { label: 'Onboarding Tours' },
      ]}
      description="Step-by-step guides to help you get up and running quickly."
      actions={
        <HStack gap={2}>
          <Button intent="primary" leftIcon="sparkles" onClick={() => startGuidedTour(theme.motion !== 'full')}>
            Start interactive tour
          </Button>
          <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get('/help')}>
            Back to Help Center
          </Button>
        </HStack>
      }
      table={
        <VStack gap={4}>
          {tours.length === 0 && (
            <Text tone="secondary">No tours available.</Text>
          )}
          <HStack gap={4} wrap>
            {tours.map(tour => (
              <Card key={tour.id}>
                <CardBody>
                  <VStack gap={3}>
                    <HStack gap={2} align="center" wrap>
                      <Text size="md">{tour.title}</Text>
                      {tour.completed && (
                        <Badge intent="success" size="sm">Completed</Badge>
                      )}
                    </HStack>

                    <HStack gap={2} align="center">
                      <Eyebrow>{tour.steps ?? 0} steps</Eyebrow>
                    </HStack>

                    <Button
                      intent={tour.completed ? 'soft' : 'primary'}
                      size="sm"
                      leftIcon={tour.completed ? 'arrowPath' : 'play'}
                      onClick={() => startTour(tour.id)}
                    >
                      {tour.completed ? 'Restart Tour' : 'Start Tour'}
                    </Button>
                  </VStack>
                </CardBody>
              </Card>
            ))}
          </HStack>
        </VStack>
      }
    />
  );
}

OnboardingTours.layout = page => (
  <App title="Onboarding Tours">{page}</App>
);
