import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Select, Pagination, Badge, Text,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '',          label: 'All Statuses' },
  { value: 'draft',     label: 'Draft' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'approved',  label: 'Approved' },
  { value: 'rejected',  label: 'Rejected' },
  { value: 'paid',      label: 'Paid' },
];

const STATUS_INTENT = {
  draft:     'neutral',
  submitted: 'info',
  approved:  'success',
  rejected:  'danger',
  paid:      'amber',
};

function statusLabel(status) {
  return status
    ? status.charAt(0).toUpperCase() + status.slice(1)
    : '—';
}

export default function ClaimsIndex({ claims, filters }) {
  const canApprove = useHRMAC('hrm.expenses.expense-claims.approve');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.expenses.claims.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function approve(id) {
    router.post(
      route('hrm.expenses.claims.approve', id),
      {},
      { preserveScroll: true },
    );
  }

  function reject(id) {
    router.get(route('hrm.expenses.claims.show', id));
  }

  const totalPages  = claims.last_page    ?? 1;
  const currentPage = claims.current_page ?? 1;

  const columns = [
    {
      key: 'reference', label: 'Reference',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.expenses.claims.show', row.id))}
        >
          {row.reference ?? `#${row.id}`}
        </Button>
      ),
    },
    {
      key: 'employee', label: 'Employee',
      render: row => <Text>{row.employee?.name ?? '—'}</Text>,
    },
    {
      key: 'claim_date', label: 'Date',
      render: row => <Text>{row.claim_date ?? '—'}</Text>,
    },
    {
      key: 'total_amount', label: 'Amount',
      render: row => (
        <Text>{row.currency} {Number(row.total_amount ?? 0).toFixed(2)}</Text>
      ),
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {statusLabel(row.status)}
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
            onClick={() => router.get(route('hrm.expenses.claims.show', row.id))}
          >
            View
          </Button>
          {canApprove && row.status === 'submitted' && (
            <>
              <Button intent="primary" size="sm" onClick={() => approve(row.id)}>
                Approve
              </Button>
              <Button intent="danger" size="sm" onClick={() => reject(row.id)}>
                Reject
              </Button>
            </>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Expense Claims"
      breadcrumb={[{ label: 'HRM' }, { label: 'Expenses' }, { label: 'Claims' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Input
            placeholder="Search claims..."
            defaultValue={filters?.q ?? ''}
            onBlur={e => applyFilters({ q: e.target.value, page: 1 })}
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
          rows={claims.data ?? []}
          empty="No expense claims found."
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

ClaimsIndex.layout = page => <App title="Expense Claims">{page}</App>;
