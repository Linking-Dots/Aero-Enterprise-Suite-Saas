import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Mono,
  Eyebrow,
  Card, CardBody,
  Divider,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function WhatsNew({ changelog = [] }) {
  return (
    <IndexPageLayout
      title="What's New"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Help Center', href: '/help' },
        { label: "What's New" },
      ]}
      description="Latest updates, improvements, and new features."
      actions={
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get('/help')}>
          Back to Help Center
        </Button>
      }
      table={
        <VStack gap={4}>
          {changelog.length === 0 && (
            <Text tone="secondary">No changelog entries available.</Text>
          )}
          {changelog.map((entry, idx) => (
            <Card key={idx}>
              <CardBody>
                <VStack gap={3}>
                  <HStack gap={3} align="center" wrap>
                    <Badge intent={idx === 0 ? 'success' : 'neutral'} size="sm">
                      v{entry.version}
                    </Badge>
                    {idx === 0 && (
                      <Badge intent="primary" size="sm">Latest</Badge>
                    )}
                    {entry.date && (
                      <Mono size="sm" tone="secondary">
                        {new Date(entry.date).toLocaleDateString(undefined, {
                          year: 'numeric', month: 'long', day: 'numeric',
                        })}
                      </Mono>
                    )}
                  </HStack>

                  {entry.highlights?.length > 0 && (
                    <>
                      <Divider />
                      <VStack gap={2}>
                        <Eyebrow>Highlights</Eyebrow>
                        {entry.highlights.map((item, i) => (
                          <HStack key={i} gap={2} align="start">
                            <Text tone="secondary" size="sm">•</Text>
                            <Text size="sm">{item}</Text>
                          </HStack>
                        ))}
                      </VStack>
                    </>
                  )}
                </VStack>
              </CardBody>
            </Card>
          ))}
        </VStack>
      }
    />
  );
}

WhatsNew.layout = page => (
  <App title="What's New">{page}</App>
);
