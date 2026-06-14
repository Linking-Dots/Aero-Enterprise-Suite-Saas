import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Select, Pagination, Badge, Text,
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

export default function MyClaimsIndex({ claims, filters }) {
  const canCreate = useHRMAC('hrm.expenses.my-expense-claims.create');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.expenses.my.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
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
      key: 'title', label: 'Title',
      render: row => <Text>{row.title ?? '—'}</Text>,
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
  ];

  return (
    <IndexPageLayout
      title="My Expense Claims"
      breadcrumb={[{ label: 'HRM' }, { label: 'Expenses' }, { label: 'My Claims' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Select
            options={STATUS_OPTIONS}
            value={filters?.status ?? ''}
            onChange={e => applyFilters({ status: e.target.value, page: 1 })}
          />
          {canCreate && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.expenses.my.create'))}
            >
              New Claim
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={claims.data ?? []}
          empty="You have no expense claims yet."
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

MyClaimsIndex.layout = page => <App title="My Expense Claims">{page}</App>;
