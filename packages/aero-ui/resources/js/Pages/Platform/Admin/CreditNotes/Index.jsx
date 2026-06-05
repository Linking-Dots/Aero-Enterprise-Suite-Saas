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
  { value: '',                  label: 'All Statuses' },
  { value: 'open',              label: 'Open' },
  { value: 'partially_applied', label: 'Partially Applied' },
  { value: 'fully_applied',     label: 'Fully Applied' },
  { value: 'voided',            label: 'Voided' },
];

const STATUS_INTENT = {
  open:              'success',
  partially_applied: 'warning',
  fully_applied:     'neutral',
  voided:            'danger',
};

export default function CreditNotesIndex({ creditNotes, filters }) {
  const toast     = useToast();
  const canCreate = useHRMAC('refunds-credits.credit-notes.create');
  const canApply  = useHRMAC('refunds-credits.credit-notes.apply');

  const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');
  const [drawerOpen,   setDrawerOpen]   = useState(false);
  const [applyTarget,  setApplyTarget]  = useState(null);
  const [applying,     setApplying]     = useState(false);

  const form = useForm({
    tenant_id: '',
    amount:    '',
    currency:  'USD',
    reason:    '',
  });

  const applyForm = useForm({
    invoice_id: '',
    amount:     '',
  });

  function applyFilters() {
    router.get(
      route('platform.admin.credit-notes.index'),
      { status: statusFilter },
      { preserveState: true, preserveScroll: true, only: ['creditNotes', 'filters'] }
    );
  }

  function resetFilters() {
    setStatusFilter('');
    router.get(route('platform.admin.credit-notes.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['creditNotes', 'filters'],
    });
  }

  function handleCreate(e) {
    e.preventDefault();
    form.post(route('platform.admin.credit-notes.store'), {
      preserveState: true,
      onSuccess: () => { toast.success('Credit note issued.'); setDrawerOpen(false); form.reset(); },
      onError: () => toast.error('Failed to issue credit note.'),
    });
  }

  function openApply(note) {
    setApplyTarget(note);
    applyForm.reset();
  }

  function confirmApply(e) {
    e.preventDefault();
    if (!applyTarget) return;
    setApplying(true);
    applyForm.post(
      route('platform.admin.credit-notes.apply', applyTarget.id),
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Credit note ${applyTarget.reference} applied.`);
          setApplyTarget(null);
          applyForm.reset();
        },
        onError: () => toast.error('Failed to apply credit note.'),
        onFinish: () => setApplying(false),
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
      render: (row) => <Mono>${Number(row.amount).toFixed(2)} {row.currency}</Mono>,
    },
    {
      key: 'amount_used',
      label: 'Used',
      render: (row) => <Mono>${Number(row.amount_used).toFixed(2)}</Mono>,
    },
    {
      key: 'balance',
      label: 'Balance',
      render: (row) => (
        <Mono>
          ${(Number(row.amount) - Number(row.amount_used)).toFixed(2)}
        </Mono>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status.replace(/_/g, ' ')}</Badge>
      ),
    },
    {
      key: 'reason',
      label: 'Reason',
      render: (row) => <Text tone="secondary">{row.reason}</Text>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canApply && (row.status === 'open' || row.status === 'partially_applied') && (
            <Button intent="soft" size="sm" onClick={() => openApply(row)}>
              Apply
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Credit Notes"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Credit Notes' },
      ]}
      description="Issue and apply credit notes to tenant accounts."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setDrawerOpen(true)}>
            Issue Credit Note
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
          rows={creditNotes?.data ?? []}
          empty="No credit notes found."
        />

        {(creditNotes?.last_page ?? 1) > 1 && (
          <Pagination
            page={creditNotes.current_page}
            total={creditNotes.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.credit-notes.index'),
                { page, status: statusFilter },
                { preserveState: true, preserveScroll: true, only: ['creditNotes'] }
              )
            }
          />
        )}
      </VStack>

      {/* Issue Credit Note Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title="Issue Credit Note"
      >
        <VStack gap={4}>
          <Field label="Tenant ID" htmlFor="cn_tenant" error={form.errors.tenant_id} required>
            <Input
              id="cn_tenant"
              value={form.data.tenant_id}
              onChange={e => form.setData('tenant_id', e.target.value)}
              placeholder="tenant-slug"
              error={form.errors.tenant_id}
            />
          </Field>

          <Field label="Amount" htmlFor="cn_amount" error={form.errors.amount} required>
            <Input
              id="cn_amount"
              type="number"
              value={form.data.amount}
              onChange={e => form.setData('amount', e.target.value)}
              placeholder="0.00"
              leftIcon="dollar"
              error={form.errors.amount}
            />
          </Field>

          <Field label="Currency" htmlFor="cn_currency" error={form.errors.currency}>
            <Input
              id="cn_currency"
              value={form.data.currency}
              onChange={e => form.setData('currency', e.target.value)}
              placeholder="USD"
              error={form.errors.currency}
            />
          </Field>

          <Field label="Reason" htmlFor="cn_reason" error={form.errors.reason} required>
            <Input
              id="cn_reason"
              value={form.data.reason}
              onChange={e => form.setData('reason', e.target.value)}
              placeholder="Reason for credit note"
              error={form.errors.reason}
            />
          </Field>

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleCreate}
            >
              Issue
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Apply Credit Note Modal */}
      <Modal
        open={!!applyTarget}
        onClose={() => { setApplyTarget(null); applyForm.reset(); }}
        title={`Apply Credit Note — ${applyTarget?.reference}`}
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={applying}
              disabled={applying}
              onClick={confirmApply}
            >
              Apply
            </Button>
            <Button intent="ghost" onClick={() => { setApplyTarget(null); applyForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Text tone="secondary">
            Available balance: ${(Number(applyTarget?.amount ?? 0) - Number(applyTarget?.amount_used ?? 0)).toFixed(2)} {applyTarget?.currency ?? 'USD'}
          </Text>

          <Field label="Invoice ID" htmlFor="ap_invoice" error={applyForm.errors.invoice_id} required hint="The invoice to apply this credit against.">
            <Input
              id="ap_invoice"
              type="number"
              value={applyForm.data.invoice_id}
              onChange={e => applyForm.setData('invoice_id', e.target.value)}
              placeholder="Invoice ID"
              error={applyForm.errors.invoice_id}
            />
          </Field>

          <Field label="Amount to Apply" htmlFor="ap_amount" error={applyForm.errors.amount} required hint="Cannot exceed available balance.">
            <Input
              id="ap_amount"
              type="number"
              value={applyForm.data.amount}
              onChange={e => applyForm.setData('amount', e.target.value)}
              placeholder="0.00"
              leftIcon="dollar"
              error={applyForm.errors.amount}
            />
          </Field>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

CreditNotesIndex.layout = page => <App title="Credit Notes">{page}</App>;
