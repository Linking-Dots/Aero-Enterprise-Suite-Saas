import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, HStack, Badge, Select, Input,
  Pagination, Text, Mono,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '',           label: 'All Statuses' },
  { value: 'scheduled',  label: 'Scheduled' },
  { value: 'completed',  label: 'Completed' },
  { value: 'cancelled',  label: 'Cancelled' },
];

function statusIntent(status) {
  switch (status) {
    case 'completed':  return 'success';
    case 'cancelled':  return 'danger';
    case 'scheduled':  return 'warning';
    default:           return 'neutral';
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export default function InterviewsIndex({ interviews, filters }) {
  const totalPages  = interviews.last_page    ?? 1;
  const currentPage = interviews.current_page ?? 1;

  function applyFilters(patch) {
    router.get(
      route('hrm.recruitment.interviews.index'),
      { ...filters, ...patch },
      { preserveState: true },
    );
  }

  const columns = [
    {
      key: 'scheduled_at',
      label: 'Scheduled At',
      render: row => <Mono>{row.scheduled_at}</Mono>,
    },
    {
      key: 'candidate',
      label: 'Candidate',
      render: row => <Text>{row.application?.applicant?.name ?? '—'}</Text>,
    },
    {
      key: 'job',
      label: 'Job',
      render: row => <Text>{row.application?.job?.title ?? '—'}</Text>,
    },
    {
      key: 'type',
      label: 'Type',
      render: row => <Text>{row.type ?? '—'}</Text>,
    },
    {
      key: 'interviewers',
      label: 'Interviewers',
      render: row => (
        <Text>
          {(row.interviewers ?? []).map(iv => iv.user?.name).filter(Boolean).join(', ') || '—'}
        </Text>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>{statusLabel(row.status)}</Badge>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Interviews"
      breadcrumb={[{ label: 'HRM' }, { label: 'Recruitment' }, { label: 'Interviews' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Input
            type="date"
            defaultValue={filters?.from ?? ''}
            onBlur={e => applyFilters({ from: e.target.value, page: 1 })}
            placeholder="From date"
          />
          <Input
            type="date"
            defaultValue={filters?.to ?? ''}
            onBlur={e => applyFilters({ to: e.target.value, page: 1 })}
            placeholder="To date"
          />
          <Select
            options={STATUS_OPTIONS}
            value={filters?.status ?? ''}
            onChange={e => applyFilters({ status: e.target.value, page: 1 })}
          />
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={interviews.data ?? []}
          empty="No interviews found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page =>
              router.get(
                route('hrm.recruitment.interviews.index'),
                { ...filters, page },
                { preserveState: true },
              )
            }
          />
        )
      }
    />
  );
}

InterviewsIndex.layout = page => <App title="Interviews">{page}</App>;
