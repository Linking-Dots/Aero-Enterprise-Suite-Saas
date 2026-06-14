import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../hooks/useHRMAC.js';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Select, Pagination, Badge, Text,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '',           label: 'All Statuses' },
  { value: 'draft',      label: 'Draft' },
  { value: 'published',  label: 'Published' },
  { value: 'cancelled',  label: 'Cancelled' },
  { value: 'completed',  label: 'Completed' },
];

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

export default function EventsIndex({ events, filters }) {
  const canCreate = useHRMAC('hrm.events.events-list.edit');

  const [status, setStatus] = useState(filters?.status ?? '');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.events.index'),
      { ...filters, status: status || undefined, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = events.last_page    ?? 1;
  const currentPage = events.current_page ?? 1;

  const columns = [
    {
      key: 'title', label: 'Title',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.events.show', row.slug))}
        >
          {row.title}
        </Button>
      ),
    },
    {
      key: 'location', label: 'Location',
      render: row => <Text>{row.location || '—'}</Text>,
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
      key: 'capacity', label: 'Capacity',
      render: row => <Text>{row.capacity ?? 'Unlimited'}</Text>,
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
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.events.show', row.slug))}
        >
          View
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Events"
      breadcrumb={[{ label: 'HRM' }, { label: 'Events' }]}
      actions={
        <HStack gap={3} align="center">
          <Field label="Status">
            <Select
              options={STATUS_OPTIONS}
              value={status}
              onChange={e => {
                setStatus(e.target.value);
                applyFilters({ status: e.target.value || undefined, page: 1 });
              }}
            />
          </Field>
          {canCreate && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.events.create'))}
            >
              New Event
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={events.data ?? []}
          empty="No events found."
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
  );
}

EventsIndex.layout = page => <App title="Events">{page}</App>;
