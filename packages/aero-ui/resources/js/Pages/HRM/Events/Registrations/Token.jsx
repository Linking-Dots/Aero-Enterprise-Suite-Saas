import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  DetailPageLayout, VStack, HStack, Text, Eyebrow,
  Badge, Button, Card, CardBody, Mono,
} from '@aero/ui';

const STATUS_INTENT = {
  registered: 'success',
  cancelled:  'danger',
  attended:   'info',
  no_show:    'neutral',
};

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

export default function RegistrationToken({ registration, event, tokenUrl }) {
  return (
    <DetailPageLayout
      title="Attendance Token"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Events', href: route('hrm.events.index') },
        { label: event.title, href: route('hrm.events.show', event.slug ?? '') },
        {
          label: 'Registrations',
          href: route('hrm.events.registrations.index', event.slug ?? ''),
        },
        { label: 'Token' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() =>
              router.get(
                route('hrm.events.registrations.index', event.slug ?? ''),
              )
            }
          >
            Back to Registrations
          </Button>
        </HStack>
      }
    >
      <VStack gap={5}>
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Eyebrow>Attendee</Eyebrow>
                <Badge intent={STATUS_INTENT[registration.status] ?? 'neutral'}>
                  {registration.status
                    ? registration.status.charAt(0).toUpperCase() +
                      registration.status.slice(1)
                    : '—'}
                </Badge>
              </HStack>

              <VStack gap={1}>
                <Text size="xs" tone="tertiary">Name</Text>
                <Text>{registration.attendee_name || '—'}</Text>
              </VStack>

              <VStack gap={1}>
                <Text size="xs" tone="tertiary">Email</Text>
                <Text>{registration.attendee_email || '—'}</Text>
              </VStack>

              <VStack gap={1}>
                <Text size="xs" tone="tertiary">Registered At</Text>
                <Text>{fmtDatetime(registration.registered_at)}</Text>
              </VStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Event</Eyebrow>

              <VStack gap={1}>
                <Text size="xs" tone="tertiary">Event Title</Text>
                <Text>{event.title || '—'}</Text>
              </VStack>

              <HStack gap={6} wrap>
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Starts At</Text>
                  <Text>{fmtDatetime(event.starts_at)}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Ends At</Text>
                  <Text>{fmtDatetime(event.ends_at)}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Location</Text>
                  <Text>{event.location || '—'}</Text>
                </VStack>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Attendance Token</Eyebrow>

              <VStack gap={1}>
                <Text size="xs" tone="tertiary">Token</Text>
                <Mono>{registration.token || '—'}</Mono>
              </VStack>

              <VStack gap={1}>
                <Text size="xs" tone="tertiary">Token URL</Text>
                <Mono>{tokenUrl || '—'}</Mono>
              </VStack>

              <Text tone="secondary">
                Present this token at the event check-in desk to verify attendance.
              </Text>
            </VStack>
          </CardBody>
        </Card>
      </VStack>
    </DetailPageLayout>
  );
}

RegistrationToken.layout = page => <App title="Attendance Token">{page}</App>;
