import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text, Mono,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  paid:     'success',
  pending:  'warning',
  overdue:  'danger',
};

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString();
}

function formatAmount(amount) {
  if (amount == null) return '—';
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

function paidThisYear(invoices) {
  const year = new Date().getFullYear();
  return (invoices?.data ?? [])
    .filter(inv => inv.status === 'paid' && new Date(inv.date).getFullYear() === year)
    .reduce((sum, inv) => sum + (Number(inv.amount) || 0), 0);
}

export default function InvoicesIndex({ invoices }) {
  const inv = invoices ?? { data: [], total: 0, prev_page_url: null, next_page_url: null };

  const totalPaid = paidThisYear(inv);

  const handleDownload = invoiceId => {
    window.open(route('core.subscription.invoices.download', invoiceId), '_blank');
  };

  const columns = [
    {
      key: 'number',
      label: 'Invoice #',
      width: '16%',
      render: row => <Mono size="sm">{row.number ?? '—'}</Mono>,
    },
    {
      key: 'date',
      label: 'Date',
      width: '14%',
      render: row => formatDate(row.date),
    },
    {
      key: 'period',
      label: 'Period',
      width: '20%',
      render: row => (
        row.period_start && row.period_end
          ? <Text size="sm">{formatDate(row.period_start)} – {formatDate(row.period_end)}</Text>
          : <Text tone="secondary" size="sm">—</Text>
      ),
    },
    {
      key: 'amount',
      label: 'Amount',
      width: '14%',
      render: row => <Text size="sm">{formatAmount(row.amount)}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      width: '12%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      width: '12%',
      align: 'right',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          leftIcon="arrowDownTray"
          onClick={() => handleDownload(row.id)}
        >
          PDF
        </Button>
      ),
    },
  ];

  const goPage = url => {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
  };

  const currentPage  = inv.current_page  ?? 1;
  const lastPage     = inv.last_page     ?? 1;

  return (
    <IndexPageLayout
      title="Invoices"
      breadcrumb={[
        { label: 'Dashboard',    href: route('core.dashboard') },
        { label: 'Subscription', href: route('core.subscription.index') },
        { label: 'Invoices' },
      ]}
      description="View and download your billing history."
      actions={
        <Button
          intent="ghost"
          leftIcon="arrowLeft"
          onClick={() => router.get(route('core.subscription.index'))}
        >
          Back to Subscription
        </Button>
      }
      kpis={[
        <Stat
          key="total"
          title="Total Invoices"
          value={inv.total ?? 0}
          icon="documentText"
          iconTone="indigo"
        />,
        <Stat
          key="paid-year"
          title={`Paid This Year (${new Date().getFullYear()})`}
          value={formatAmount(totalPaid)}
          icon="currencyDollar"
          iconTone="success"
        />,
      ]}
      table={
        <VStack gap={3}>
          <DataTable
            columns={columns}
            rows={inv.data ?? []}
            empty="No invoices found."
          />
        </VStack>
      }
      pagination={
        lastPage > 1 && (
          <Pagination
            page={currentPage}
            total={lastPage}
            onChange={page => {
              const base = route('core.subscription.invoices');
              router.get(base, { page }, { preserveState: true, preserveScroll: true });
            }}
          />
        )
      }
    />
  );
}

InvoicesIndex.layout = page => (
  <App title="Invoices">{page}</App>
);
