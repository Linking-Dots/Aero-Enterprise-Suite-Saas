import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Badge, Button, Field, Select, Pagination, HStack,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'self_submitted':    return 'warning';
    case 'manager_submitted': return 'primary';
    case 'finalized':         return 'success';
    default:                  return 'neutral'; // draft
  }
}

function statusLabel(status) {
  return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

const STATUS_OPTIONS = [
  { value: '',                   label: 'All statuses' },
  { value: 'draft',              label: 'Draft' },
  { value: 'self_submitted',     label: 'Self Submitted' },
  { value: 'manager_submitted',  label: 'Manager Submitted' },
  { value: 'finalized',          label: 'Finalized' },
];

export default function ReviewsIndex({ reviews, filters }) {
  const [status, setStatus] = useState(filters?.status ?? '');

  function applyFilter(value) {
    setStatus(value);
    router.get(
      route('hrm.performance.reviews.index'),
      { status: value || undefined },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => `${row.employee?.first_name ?? ''} ${row.employee?.last_name ?? ''}`.trim() || '—',
    },
    {
      key: 'cycle', label: 'Cycle',
      render: row => row.cycle?.name ?? '—',
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>{statusLabel(row.status)}</Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.performance.reviews.show', row.id))}
        >
          View
        </Button>
      ),
    },
  ];

  const totalPages  = reviews.last_page    ?? 1;
  const currentPage = reviews.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Performance Reviews"
      breadcrumb={[{ label: 'HRM' }, { label: 'Performance' }, { label: 'Reviews' }]}
      filters={
        <HStack gap={3}>
          <Field label="Status">
            <Select
              options={STATUS_OPTIONS}
              value={status}
              onChange={e => applyFilter(e.target.value)}
            />
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={reviews.data ?? []}
          empty="No reviews found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(
              route('hrm.performance.reviews.index'),
              { status: status || undefined, page },
              { preserveState: true, replace: true }
            )}
          />
        )
      }
    />
  );
}

ReviewsIndex.layout = page => <App title="Performance Reviews">{page}</App>;
