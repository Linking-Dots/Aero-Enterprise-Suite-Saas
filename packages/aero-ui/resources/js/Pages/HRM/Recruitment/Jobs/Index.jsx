import { router } from '@inertiajs/react';
import { useRef } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, Badge, Select, Input,
  Pagination, Text,
} from '@aero/ui';

const ALL_STATUSES = [{ value: '', label: 'All Statuses' }];
const ALL_TYPES    = [{ value: '', label: 'All Types' }];
const ALL_DEPTS    = [{ value: '', label: 'All Departments' }];

function statusIntent(status) {
  switch (status) {
    case 'open':   return 'success';
    case 'draft':  return 'warning';
    case 'closed': return 'neutral';
    default:       return 'neutral';
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export default function JobsIndex({ jobs, filters, departments, statuses, types }) {
  const canCreate = useHRMAC('hrm.recruitment.jobs.create');
  const searchRef = useRef(null);

  const totalPages  = jobs.last_page    ?? 1;
  const currentPage = jobs.current_page ?? 1;

  const statusOptions = [
    ...ALL_STATUSES,
    ...(statuses ?? []).map(s => ({ value: s, label: statusLabel(s) })),
  ];
  const typeOptions = [
    ...ALL_TYPES,
    ...(types ?? []).map(t => ({ value: t, label: t })),
  ];
  const deptOptions = [
    ...ALL_DEPTS,
    ...(departments ?? []).map(d => ({ value: String(d.id), label: d.name })),
  ];

  function applyFilters(patch) {
    router.get(
      route('hrm.recruitment.jobs.index'),
      { ...filters, ...patch },
      { preserveState: true },
    );
  }

  const columns = [
    {
      key: 'title',
      label: 'Title',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.recruitment.jobs.show', row.id))}
        >
          {row.title}
        </Button>
      ),
    },
    {
      key: 'department',
      label: 'Department',
      render: row => <Text>{row.department?.name ?? '—'}</Text>,
    },
    {
      key: 'type',
      label: 'Type',
      render: row => <Text>{row.type ?? '—'}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>{statusLabel(row.status)}</Badge>
      ),
    },
    {
      key: 'applications_count',
      label: 'Applications',
      render: row => <Text>{row.applications_count ?? 0}</Text>,
    },
    {
      key: 'posting_date',
      label: 'Posted',
      render: row => <Text>{row.posting_date ?? '—'}</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="Job Postings"
      breadcrumb={[{ label: 'HRM' }, { label: 'Recruitment' }, { label: 'Jobs' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Input
            ref={searchRef}
            placeholder="Search jobs..."
            defaultValue={filters?.search ?? ''}
            onBlur={e => applyFilters({ search: e.target.value, page: 1 })}
          />
          <Select
            options={statusOptions}
            value={filters?.status ?? ''}
            onChange={e => applyFilters({ status: e.target.value, page: 1 })}
          />
          <Select
            options={deptOptions}
            value={filters?.department_id ? String(filters.department_id) : ''}
            onChange={e => applyFilters({ department_id: e.target.value, page: 1 })}
          />
          <Select
            options={typeOptions}
            value={filters?.type ?? ''}
            onChange={e => applyFilters({ type: e.target.value, page: 1 })}
          />
          {canCreate && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.recruitment.jobs.create'))}
            >
              New Job
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={jobs.data ?? []}
          empty="No job postings found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page =>
              router.get(
                route('hrm.recruitment.jobs.index'),
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

JobsIndex.layout = page => <App title="Job Postings">{page}</App>;
