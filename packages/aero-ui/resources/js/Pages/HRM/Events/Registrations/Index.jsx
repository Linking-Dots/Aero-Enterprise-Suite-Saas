import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Pagination, Badge, Text, Modal, VStack,
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

export default function RegistrationsIndex({ event, registrations }) {
  const canCancel = useHRMAC('hrm.events.registrations.edit');

  const [cancelTarget, setCancelTarget] = useState(null);
  const [cancelling,   setCancelling]   = useState(false);

  function confirmCancel() {
    setCancelling(true);
    router.post(
      route('hrm.events.registrations.cancel', cancelTarget.id),
      {},
      {
        preserveScroll: true,
        onFinish: () => {
          setCancelling(false);
          setCancelTarget(null);
        },
      },
    );
  }

  const totalPages  = registrations.last_page    ?? 1;
  const currentPage = registrations.current_page ?? 1;

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.events.registrations.index', event.slug),
      { ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const columns = [
    {
      key: 'attendee_name', label: 'Attendee',
      render: row => <Text>{row.attendee_name || '—'}</Text>,
    },
    {
      key: 'attendee_email', label: 'Email',
      render: row => <Text>{row.attendee_email || '—'}</Text>,
    },
    {
      key: 'registered_at', label: 'Registered At',
      render: row => <Text>{fmtDatetime(row.registered_at)}</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status ? row.status.charAt(0).toUpperCase() + row.status.slice(1) : '—'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <HStack gap={1}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('hrm.events.registrations.token', row.id))}
          >
            Token
          </Button>
          {canCancel && row.status === 'registered' && (
            <Button
              intent="danger"
              size="sm"
              onClick={() => setCancelTarget(row)}
            >
              Cancel
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title={`Registrations — ${event.title}`}
        breadcrumb={[
          { label: 'HRM' },
          { label: 'Events', href: route('hrm.events.index') },
          { label: event.title, href: route('hrm.events.show', event.slug) },
          { label: 'Registrations' },
        ]}
        actions={
          <HStack gap={2} align="center">
            <Text tone="secondary">
              Capacity: {event.capacity ?? 'Unlimited'}
            </Text>
            <Button
              intent="ghost"
              onClick={() => router.get(route('hrm.events.show', event.slug))}
            >
              Back to Event
            </Button>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={registrations.data ?? []}
            empty="No registrations yet."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page => applyFilters({ page })}
            />
          )
        }
      />

      <Modal
        open={!!cancelTarget}
        onClose={() => setCancelTarget(null)}
        title="Cancel Registration"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              type="button"
              intent="danger"
              loading={cancelling}
              onClick={confirmCancel}
            >
              Confirm Cancel
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => setCancelTarget(null)}
            >
              Dismiss
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            Are you sure you want to cancel the registration for{' '}
            <Text>{cancelTarget?.attendee_name}</Text>?
            This action cannot be undone.
          </Text>
        </VStack>
      </Modal>
    </>
  );
}

RegistrationsIndex.layout = page => <App title="Event Registrations">{page}</App>;
