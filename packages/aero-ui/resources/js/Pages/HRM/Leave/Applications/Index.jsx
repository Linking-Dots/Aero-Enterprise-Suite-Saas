import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, Field, Input, Select,
  Pagination, Badge, Text, Stat, Avatar,
} from '@aero/ui';
import LeaveRail from '../LeaveRail.jsx';

const STATUS_INTENT = {
  pending:   'warning',
  approved:  'success',
  rejected:  'danger',
  cancelled: 'neutral',
};

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

export default function ApplicationsIndex({ applications, filters, leaveTypes, stats }) {
  const [status,   setStatus]   = useState(filters?.status        ?? '');
  const [typeId,   setTypeId]   = useState(filters?.leave_type_id ?? '');
  const [dateFrom, setDateFrom] = useState(filters?.from          ?? '');
  const [dateTo,   setDateTo]   = useState(filters?.to            ?? '');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.leave.applications.index'),
      {
        ...filters,
        status:        status   || undefined,
        leave_type_id: typeId   || undefined,
        from:          dateFrom || undefined,
        to:            dateTo   || undefined,
        ...overrides,
      },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const statusOptions = [
    { value: '',          label: 'All Statuses' },
    { value: 'pending',   label: 'Pending' },
    { value: 'approved',  label: 'Approved' },
    { value: 'rejected',  label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
  ];

  const typeOptions = [
    { value: '', label: 'All Types' },
    ...(leaveTypes ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => row.employee?.user?.name ? (
        <HStack gap={2} align="center">
          <Avatar name={row.employee.user.name} size={28} />
          <Text>{row.employee.user.name}</Text>
        </HStack>
      ) : <Text tone="secondary">—</Text>,
    },
    {
      key: 'leave_type', label: 'Type',
      render: row => row.leave_type ? (
        <HStack gap={2} align="center">
          <span className="lapp-type-swatch" style={{ background: row.leave_type.color }} />
          <Text>{row.leave_type.name}</Text>
        </HStack>
      ) : <Text tone="secondary">—</Text>,
    },
    { key: 'start_date', label: 'From',  render: row => fmtDate(row.start_date) },
    { key: 'end_date',   label: 'To',    render: row => fmtDate(row.end_date) },
    { key: 'total_days', label: 'Days',  render: row => row.total_days },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status.charAt(0).toUpperCase() + row.status.slice(1)}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.leave.applications.show', row.id))}
        >
          View
        </Button>
      ),
    },
  ];

  const totalPages  = applications.last_page    ?? 1;
  const currentPage = applications.current_page ?? 1;

  return (
    <>
      <style>{`
        .lapp-type-swatch {
          display: inline-block;
          width: 0.75rem;
          height: 0.75rem;
          border-radius: var(--aeos-r-sm);
          flex-shrink: 0;
        }
      `}</style>

      <IndexPageLayout
        title="Leave Applications"
        breadcrumb={[{ label: 'HRM' }, { label: 'Leave' }, { label: 'Applications' }]}
        kpis={[
          <Stat key="total"    title="Total Requests" value={stats?.total    ?? 0} icon="document"    />,
          <Stat key="pending"  title="Pending"        value={stats?.pending  ?? 0} icon="clock"       iconTone="amber" />,
          <Stat key="approved" title="Approved"       value={stats?.approved ?? 0} icon="checkCircle" iconTone="success" />,
        ]}
        actions={
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.leave.applications.create'))}
          >
            Apply for Leave
          </Button>
        }
        filters={
          <HStack gap={3} wrap>
            <Field label="Status">
              <Select
                value={status}
                onChange={e => { setStatus(e.target.value); applyFilters({ status: e.target.value || undefined, page: 1 }); }}
              >
                {statusOptions.map(o => (
                  <option key={o.value} value={o.value}>{o.label}</option>
                ))}
              </Select>
            </Field>

            <Field label="Leave Type">
              <Select
                value={typeId}
                onChange={e => { setTypeId(e.target.value); applyFilters({ leave_type_id: e.target.value || undefined, page: 1 }); }}
              >
                {typeOptions.map(o => (
                  <option key={o.value} value={o.value}>{o.label}</option>
                ))}
              </Select>
            </Field>

            <Field label="From">
              <Input
                type="date"
                value={dateFrom}
                onChange={e => setDateFrom(e.target.value)}
                onBlur={() => applyFilters({ page: 1 })}
              />
            </Field>

            <Field label="To">
              <Input
                type="date"
                value={dateTo}
                onChange={e => setDateTo(e.target.value)}
                onBlur={() => applyFilters({ page: 1 })}
              />
            </Field>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={applications.data ?? []}
            empty="No leave applications found."
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
    </>
  );
}

ApplicationsIndex.layout = page => (
  <App title="Leave Applications" railTitle="Leave management" rail={<LeaveRail />}>{page}</App>
);
