import { DataTable, Button, Badge, Pagination, VStack, Text, Mono, EmptyState } from '@aero/ui';
import { money } from '../money.js';

const STATUS_INTENT = { paid: 'success', issued: 'neutral', overdue: 'danger', void: 'neutral', refunded: 'warning', draft: 'neutral' };

function fmtDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; }

export default function InvoicesPanel({ invoices, loading, onPage }) {
  const inv = invoices ?? { data: [], total: 0, current_page: 1, last_page: 1 };

  const columns = [
    { key: 'number', label: 'Invoice', width: '18%', render: r => <Mono size="sm">{r.number ?? '—'}</Mono> },
    { key: 'date', label: 'Date', width: '14%', render: r => <Text size="sm">{fmtDate(r.date)}</Text> },
    { key: 'period', label: 'Period', width: '24%',
      render: r => (r.period_start && r.period_end
        ? <Text size="sm">{fmtDate(r.period_start)} – {fmtDate(r.period_end)}</Text>
        : <Text tone="tertiary" size="sm">—</Text>) },
    { key: 'amount', label: 'Amount', width: '14%', render: r => <Text size="sm">{money(r.amount, r.currency)}</Text> },
    { key: 'status', label: 'Status', width: '12%',
      render: r => <Badge intent={STATUS_INTENT[r.status] ?? 'neutral'} size="sm">{r.status ?? '—'}</Badge> },
    { key: 'actions', label: '', width: '18%', align: 'right',
      render: r => r.has_pdf
        ? <Button intent="ghost" size="sm" type="button" leftIcon="download"
            onClick={() => window.open(route('core.subscription.invoices.download', r.id), '_blank')}>PDF</Button>
        : <Text tone="tertiary" size="sm">—</Text> },
  ];

  if (!loading && (inv.data ?? []).length === 0) {
    return (
      <EmptyState
        icon="document"
        title="No invoices yet"
        description="Your invoices will appear here once your billing history begins."
      />
    );
  }

  return (
    <VStack gap={3}>
      <div className="aeos-table-section">
        <DataTable columns={columns} rows={inv.data ?? []} loading={loading} />
      </div>
      {inv.last_page > 1 && (
        <div className="aeos-pagination-bar">
          <Pagination page={inv.current_page} total={inv.last_page} onChange={onPage} />
        </div>
      )}
    </VStack>
  );
}
