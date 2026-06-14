import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
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
  Field,
  Input,
  Select,
  Modal,
  Drawer,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: '',           label: 'All Statuses' },
  { value: 'pending',    label: 'Pending' },
  { value: 'approved',   label: 'Approved' },
  { value: 'processed',  label: 'Processed' },
  { value: 'failed',     label: 'Failed' },
];

const STATUS_INTENT = {
  pending:   'warning',
  approved:  'info',
  processed: 'success',
  failed:    'danger',
};

export default function RefundsIndex({ refunds, filters }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('refunds-credits.refunds.create');
  const canApprove = useHRMAC('refunds-credits.refunds.approve');
  const canProcess = useHRMAC('refunds-credits.refunds.process');

  const [statusFilter,   setStatusFilter]   = useState(filters?.status ?? '');
  const [drawerOpen,     setDrawerOpen]     = useState(false);
  const [approveTarget,  setApproveTarget]  = useState(null);
  const [approving,      setApproving]      = useState(false);
  const [processTarget,  setProcessTarget]  = useState(null);
  const [processing,     setProcessingState] = useState(false);

  const form = useForm({
    tenant_id:  '',
    invoice_id: '',
    amount:     '',
    currency:   'USD',
    reason:     '',
  });

  function applyFilters() {
    router.get(
      route('platform.admin.refunds.index'),
      { status: statusFilter },
      { preserveState: true, preserveScroll: true, only: ['refunds', 'filters'] }
    );
  }

  function resetFilters() {
    setStatusFilter('');
    router.get(route('platform.admin.refunds.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['refunds', 'filters'],
    });
  }

  function handleCreateRefund(e) {
    e.preventDefault();
    form.post(route('platform.admin.refunds.store'), {
      preserveState: true,
      onSuccess: () => { toast.success('Refund request created.'); setDrawerOpen(false); form.reset(); },
      onError: () => toast.error('Failed to create refund request.'),
    });
  }

  function confirmApprove() {
    if (!approveTarget) return;
    setApproving(true);
    router.post(
      route('platform.admin.refunds.approve', approveTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Refund ${approveTarget.reference} approved.`);
          setApproveTarget(null);
        },
        onError: () => toast.error('Failed to approve refund.'),
        onFinish: () => setApproving(false),
      }
    );
  }

  function confirmProcess() {
    if (!processTarget) return;
    setProcessingState(true);
    router.post(
      route('platform.admin.refunds.process', processTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Refund ${processTarget.reference} processed.`);
          setProcessTarget(null);
        },
        onError: () => toast.error('Failed to process refund.'),
        onFinish: () => setProcessingState(false),
      }
    );
  }

  const columns = [
    {
      key: 'reference',
      label: 'Reference',
      render: (row) => <Mono>{row.reference}</Mono>,
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
        <Mono>${Number(row.amount).toFixed(2)} {row.currency}</Mono>
      ),
    },
    {
      key: 'invoice',
      label: 'Invoice',
      render: (row) => row.invoice ? <Mono>{row.invoice.number ?? row.invoice_id}</Mono> : <Text tone="secondary">—</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'requested_at',
      label: 'Requested',
      render: (row) => (
        <Mono>{row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'}</Mono>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canApprove && row.status === 'pending' && (
            <Button intent="soft" size="sm" onClick={() => setApproveTarget(row)}>
              Approve
            </Button>
          )}
          {canProcess && row.status === 'approved' && (
            <Button intent="primary" size="sm" onClick={() => setProcessTarget(row)}>
              Process
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Refunds"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Refunds' },
      ]}
      description="Review and process refund requests against tenant invoices."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setDrawerOpen(true)}>
            New Refund
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <HStack gap={3}>
          <Select
            value={statusFilter}
            onChange={e => setStatusFilter(e.target.value)}
            options={STATUS_OPTIONS}
          />
          <Button intent="soft" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>

        <DataTable
          columns={columns}
          rows={refunds?.data ?? []}
          empty="No refunds found."
        />

        {(refunds?.last_page ?? 1) > 1 && (
          <Pagination
            page={refunds.current_page}
            total={refunds.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.refunds.index'),
                { page, status: statusFilter },
                { preserveState: true, preserveScroll: true, only: ['refunds'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create Refund Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title="New Refund Request"
      >
        <VStack gap={4}>
          <Field label="Tenant ID" htmlFor="rf_tenant" error={form.errors.tenant_id} required>
            <Input
              id="rf_tenant"
              value={form.data.tenant_id}
              onChange={e => form.setData('tenant_id', e.target.value)}
              placeholder="tenant-slug"
              error={form.errors.tenant_id}
            />
          </Field>

          <Field label="Invoice ID" htmlFor="rf_invoice" error={form.errors.invoice_id} hint="Leave blank if refund is not linked to a specific invoice.">
            <Input
              id="rf_invoice"
              type="number"
              value={form.data.invoice_id}
              onChange={e => form.setData('invoice_id', e.target.value)}
              placeholder="Optional"
              error={form.errors.invoice_id}
            />
          </Field>

          <Field label="Amount" htmlFor="rf_amount" error={form.errors.amount} required>
            <Input
              id="rf_amount"
              type="number"
              value={form.data.amount}
              onChange={e => form.setData('amount', e.target.value)}
              placeholder="0.00"
              leftIcon="dollar"
              error={form.errors.amount}
            />
          </Field>

          <Field label="Currency" htmlFor="rf_currency" error={form.errors.currency}>
            <Input
              id="rf_currency"
              value={form.data.currency}
              onChange={e => form.setData('currency', e.target.value)}
              placeholder="USD"
              error={form.errors.currency}
            />
          </Field>

          <Field label="Reason" htmlFor="rf_reason" error={form.errors.reason} required>
            <Input
              id="rf_reason"
              value={form.data.reason}
              onChange={e => form.setData('reason', e.target.value)}
              placeholder="Reason for refund"
              error={form.errors.reason}
            />
          </Field>

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleCreateRefund}
            >
              Submit Request
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Approve Confirm Modal */}
      <Modal
        open={!!approveTarget}
        onClose={() => setApproveTarget(null)}
        title="Approve Refund"
        description={`Approve refund ${approveTarget?.reference} for $${Number(approveTarget?.amount ?? 0).toFixed(2)} ${approveTarget?.currency ?? 'USD'}? This marks it ready for processing.`}
        footer={
          <HStack gap={2}>
            <Button intent="primary" loading={approving} disabled={approving} onClick={confirmApprove}>
              Approve
            </Button>
            <Button intent="ghost" onClick={() => setApproveTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Process Confirm Modal */}
      <Modal
        open={!!processTarget}
        onClose={() => setProcessTarget(null)}
        title="Process Refund"
        description={`Process refund ${processTarget?.reference} for $${Number(processTarget?.amount ?? 0).toFixed(2)} ${processTarget?.currency ?? 'USD'}? This will initiate the actual payment reversal through the gateway. This action is irreversible.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={processing} disabled={processing} onClick={confirmProcess}>
              Process Refund
            </Button>
            <Button intent="ghost" onClick={() => setProcessTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

RefundsIndex.layout = page => <App title="Refunds">{page}</App>;
