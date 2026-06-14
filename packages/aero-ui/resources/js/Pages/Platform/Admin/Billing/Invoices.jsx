import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  VStack,
  Text,
  Mono,
  Select,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  paid:    'success',
  open:    'warning',
  draft:   'neutral',
  overdue: 'danger',
  void:    'neutral',
};

export default function Invoices({ invoices, filters }) {
  const toast = useToast();
  const canSend     = useHRMAC('billing-management.invoices.send');
  const canMarkPaid = useHRMAC('billing-management.invoices.mark-paid');
  const canDownload = useHRMAC('billing-management.invoices.download');

  const [status, setStatus] = useState(filters?.status ?? '');

  const [sendTarget,    setSendTarget]    = useState(null);
  const [sending,       setSending]       = useState(false);
  const [markPaidTarget, setMarkPaidTarget] = useState(null);
  const [marking,       setMarking]       = useState(false);

  function applyFilters() {
    router.get(
      route('platform.admin.billing.invoices.index'),
      { status },
      { preserveState: true, preserveScroll: true, only: ['invoices', 'filters'] }
    );
  }

  function resetFilters() {
    setStatus('');
    router.get(route('platform.admin.billing.invoices.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['invoices', 'filters'],
    });
  }

  function confirmSend() {
    if (!sendTarget) return;
    setSending(true);
    router.post(
      route('platform.admin.billing.invoices.send', sendTarget.id),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Invoice #${sendTarget.number ?? sendTarget.id} sent.`);
          setSendTarget(null);
        },
        onError: () => toast.error('Failed to send invoice.'),
        onFinish: () => setSending(false),
      }
    );
  }

  function confirmMarkPaid() {
    if (!markPaidTarget) return;
    setMarking(true);
    router.post(
      route('platform.admin.billing.invoices.mark-paid', markPaidTarget.id),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Invoice #${markPaidTarget.number ?? markPaidTarget.id} marked as paid.`);
          setMarkPaidTarget(null);
        },
        onError: () => toast.error('Failed to mark invoice as paid.'),
        onFinish: () => setMarking(false),
      }
    );
  }

  const statusOptions = [
    { value: '',        label: 'All Statuses' },
    { value: 'draft',   label: 'Draft' },
    { value: 'open',    label: 'Open' },
    { value: 'paid',    label: 'Paid' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'void',    label: 'Void' },
  ];

  const columns = [
    {
      key: 'number',
      label: 'Invoice #',
      render: (row) => <Mono>{row.number ?? row.id}</Mono>,
    },
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.tenants.show', row.tenant_id))}
        >
          {row.tenant?.name ?? row.tenant_id}
        </Button>
      ),
    },
    {
      key: 'amount',
      label: 'Amount',
      render: (row) => (
        <Mono>{row.amount != null ? `$${Number(row.amount).toFixed(2)}` : '—'}</Mono>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'due_date',
      label: 'Due',
      render: (row) => (
        <Mono tone="secondary">
          {row.due_date ? new Date(row.due_date).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canSend && row.status === 'draft' && (
            <Button
              intent="soft"
              size="sm"
              onClick={() => setSendTarget(row)}
            >
              Send
            </Button>
          )}
          {canMarkPaid && row.status !== 'paid' && row.status !== 'void' && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => setMarkPaidTarget(row)}
            >
              Mark Paid
            </Button>
          )}
          {canDownload && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => window.open(route('platform.admin.billing.invoices.download', row.id), '_blank')}
            >
              PDF
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Invoices"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Billing', href: route('platform.admin.billing.invoices.index') },
        { label: 'Invoices' },
      ]}
      description="View and manage all tenant invoices."
    >
      <VStack gap={4}>
        <HStack gap={3}>
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={statusOptions}
          />
          <Button intent="soft" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>

        <DataTable
          columns={columns}
          rows={invoices?.data ?? []}
          empty="No invoices found."
        />

        {invoices?.last_page > 1 && (
          <Pagination
            page={invoices.current_page}
            total={invoices.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.billing.invoices.index'),
                { page, status },
                { preserveState: true, preserveScroll: true, only: ['invoices'] }
              )
            }
          />
        )}
      </VStack>

      {/* Send Confirmation Modal */}
      <Modal
        open={!!sendTarget}
        onClose={() => setSendTarget(null)}
        title="Send Invoice"
        description={`Send invoice #${sendTarget?.number ?? sendTarget?.id} to "${sendTarget?.tenant?.name ?? sendTarget?.tenant_id}"?`}
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={sending}
              disabled={sending}
              onClick={confirmSend}
            >
              Send Invoice
            </Button>
            <Button intent="ghost" onClick={() => setSendTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Mark Paid Confirmation Modal */}
      <Modal
        open={!!markPaidTarget}
        onClose={() => setMarkPaidTarget(null)}
        title="Mark Invoice as Paid"
        description={`Mark invoice #${markPaidTarget?.number ?? markPaidTarget?.id} as paid? This will record a manual payment.`}
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={marking}
              disabled={marking}
              onClick={confirmMarkPaid}
            >
              Confirm Mark Paid
            </Button>
            <Button intent="ghost" onClick={() => setMarkPaidTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

Invoices.layout = page => <App title="Invoices">{page}</App>;
