import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../hooks/useHRMAC.js';
import {
  DetailPageLayout, VStack, HStack, Text, Eyebrow,
  Badge, Button, Card, CardBody, DataTable, Alert,
} from '@aero/ui';

const STATUS_INTENT = {
  draft:      'neutral',
  published:  'success',
  cancelled:  'danger',
  completed:  'info',
};

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

function InfoRow({ label, value }) {
  return (
    <VStack gap={0}>
      <Text size="xs" tone="tertiary">{label}</Text>
      <Text>{value ?? '—'}</Text>
    </VStack>
  );
}

export default function EventsShow({ event, can }) {
  const canPublish = useHRMAC('hrm.events.events-list.publish');

  function publish() {
    router.post(
      route('hrm.events.publish', event.slug),
      {},
      { preserveScroll: true },
    );
  }

  const isDraft = event.status === 'draft';

  const sessionColumns = [
    {
      key: 'title', label: 'Session',
      render: row => <Text>{row.title || '—'}</Text>,
    },
    {
      key: 'starts_at', label: 'Starts At',
      render: row => <Text>{fmtDatetime(row.starts_at)}</Text>,
    },
    {
      key: 'ends_at', label: 'Ends At',
      render: row => <Text>{fmtDatetime(row.ends_at)}</Text>,
    },
    {
      key: 'location', label: 'Location',
      render: row => <Text>{row.location || '—'}</Text>,
    },
    {
      key: 'capacity', label: 'Capacity',
      render: row => <Text>{row.capacity ?? 'Unlimited'}</Text>,
    },
  ];

  return (
    <DetailPageLayout
      title={event.title}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Events', href: route('hrm.events.index') },
        { label: event.title },
      ]}
      actions={
        <HStack gap={2}>
          {(can?.publish ?? canPublish) && isDraft && (
            <Button intent="primary" onClick={publish}>
              Publish
            </Button>
          )}
          <Button
            intent="soft"
            onClick={() => router.get(route('hrm.events.registrations.index', event.slug))}
          >
            Registrations
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.events.index'))}
          >
            Back
          </Button>
        </HStack>
      }
    >
      <VStack gap={5}>
        {isDraft && (
          <Alert intent="info" title="Draft">
            This event is not yet published. Publish it to allow registrations.
          </Alert>
        )}

        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Text size="lg">{event.title}</Text>
                <Badge intent={STATUS_INTENT[event.status] ?? 'neutral'}>
                  {event.status ? event.status.charAt(0).toUpperCase() + event.status.slice(1) : '—'}
                </Badge>
                {event.is_public && <Badge intent="info">Public</Badge>}
              </HStack>

              <HStack gap={6} wrap>
                <InfoRow label="Location"  value={event.location} />
                <InfoRow label="Starts At" value={fmtDatetime(event.starts_at)} />
                <InfoRow label="Ends At"   value={fmtDatetime(event.ends_at)} />
                <InfoRow label="Capacity"  value={event.capacity ?? 'Unlimited'} />
                {event.creator && (
                  <InfoRow label="Created By" value={event.creator.name} />
                )}
              </HStack>

              {event.description && (
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Description</Text>
                  <Text tone="secondary">{event.description}</Text>
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        <VStack gap={3}>
          <Eyebrow>Sessions</Eyebrow>
          <DataTable
            columns={sessionColumns}
            rows={event.sessions ?? []}
            empty="No sessions defined for this event."
          />
        </VStack>
      </VStack>
    </DetailPageLayout>
  );
}

EventsShow.layout = page => <App title="Event Details">{page}</App>;
