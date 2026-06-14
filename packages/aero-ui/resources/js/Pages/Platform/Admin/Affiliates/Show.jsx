import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
  DataTable,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  KPI,
  Field,
  Input,
  Select,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  pending:   'warning',
  approved:  'success',
  suspended: 'danger',
};

const REF_STATUS_INTENT = {
  pending:  'warning',
  approved: 'success',
  paid:     'neutral',
};

const PAYOUT_STATUS_INTENT = {
  pending:    'warning',
  processing: 'primary',
  completed:  'success',
};

const PAYOUT_METHOD_OPTIONS = [
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'paypal',        label: 'PayPal' },
  { value: 'crypto',        label: 'Crypto' },
];

export default function AffiliatesShow({ affiliate, referrals, payouts }) {
  const toast         = useToast();
  const canApprove    = useHRMAC('affiliate-program.affiliate-list.approve');
  const canSuspend    = useHRMAC('affiliate-program.affiliate-list.suspend');
  const canApproveRef = useHRMAC('affiliate-program.affiliate-referrals.approve-commission');
  const canPayout     = useHRMAC('affiliate-program.affiliate-payouts.create');
  const canProcess    = useHRMAC('affiliate-program.affiliate-payouts.process');

  const [approveOpen,  setApproveOpen]  = useState(false);
  const [suspendOpen,  setSuspendOpen]  = useState(false);
  const [refTarget,    setRefTarget]    = useState(null);
  const [refApproveOpen, setRefApproveOpen] = useState(false);
  const [payoutOpen,   setPayoutOpen]   = useState(false);
  const [processTarget, setProcessTarget] = useState(null);
  const [processOpen,  setProcessOpen]  = useState(false);
  const [submitting,   setSubmitting]   = useState(false);

  const [newPayout, setNewPayout] = useState({ amount: '', currency: 'USD', method: 'bank_transfer' });

  function doApprove() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.approve', affiliate.id),
      {},
      {
        onSuccess: () => { setApproveOpen(false); toast.success('Affiliate approved.'); },
        onError:   () => toast.error('Failed to approve.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doSuspend() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.suspend', affiliate.id),
      {},
      {
        onSuccess: () => { setSuspendOpen(false); toast.success('Affiliate suspended.'); },
        onError:   () => toast.error('Failed to suspend.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doApproveRef() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.referrals.approve', refTarget.id),
      {},
      {
        onSuccess: () => { setRefApproveOpen(false); toast.success('Commission approved.'); },
        onError:   () => toast.error('Failed to approve commission.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doCreatePayout() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.payouts.store', affiliate.id),
      newPayout,
      {
        onSuccess: () => {
          setPayoutOpen(false);
          setNewPayout({ amount: '', currency: 'USD', method: 'bank_transfer' });
          toast.success('Payout created.');
        },
        onError: () => toast.error('Failed to create payout.'),
        onFinish: () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doProcessPayout() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.payouts.process', processTarget.id),
      {},
      {
        onSuccess: () => { setProcessOpen(false); toast.success('Payout processed.'); },
        onError:   () => toast.error('Failed to process payout.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  const referralColumns = [
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.tenants.show', row.tenant_id))}
        >
          {row.tenant?.name ?? `#${row.tenant_id}`}
        </Button>
      ),
    },
    {
      key: 'commission_amount',
      label: 'Commission',
      render: (row) => <Mono>${Number(row.commission_amount ?? 0).toFixed(2)}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={REF_STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'attributed_at',
      label: 'Attributed',
      render: (row) => <Mono tone="secondary">{row.attributed_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canApproveRef && row.status === 'pending' ? (
          <Button
            intent="ghost"
            size="sm"
            onClick={() => { setRefTarget(row); setRefApproveOpen(true); }}
          >
            Approve
          </Button>
        ) : null,
    },
  ];

  const payoutColumns = [
    {
      key: 'amount',
      label: 'Amount',
      render: (row) => <Mono>${Number(row.amount ?? 0).toFixed(2)} {row.currency}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={PAYOUT_STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'processed_at',
      label: 'Processed',
      render: (row) => <Mono tone="secondary">{row.processed_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canProcess && row.status === 'pending' ? (
          <Button
            intent="ghost"
            size="sm"
            onClick={() => { setProcessTarget(row); setProcessOpen(true); }}
          >
            Process
          </Button>
        ) : null,
    },
  ];

  return (
    <DetailPageLayout
      title={affiliate.name}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Affiliates', href: route('platform.admin.affiliates.index') },
        { label: affiliate.name },
      ]}
      description={affiliate.company ?? ''}
      actions={
        <HStack gap={2}>
          <Badge intent={STATUS_INTENT[affiliate.status] ?? 'neutral'}>{affiliate.status}</Badge>
          {canApprove && affiliate.status !== 'approved' && (
            <Button intent="soft" onClick={() => setApproveOpen(true)}>Approve</Button>
          )}
          {canSuspend && affiliate.status === 'approved' && (
            <Button intent="danger" onClick={() => setSuspendOpen(true)}>Suspend</Button>
          )}
          <Button intent="ghost" onClick={() => router.get(route('platform.admin.affiliates.index'))}>
            Back
          </Button>
        </HStack>
      }
    >
      <VStack gap={4}>

        {/* KPIs */}
        <HStack gap={4}>
          <KPI label="Total Earnings" value={`$${Number(affiliate.total_earnings ?? 0).toFixed(2)}`} />
          <KPI label="Total Referrals" value={referrals?.length ?? 0} />
          <KPI label="Commission Rate" value={`${affiliate.commission_rate}%`} />
        </HStack>

        {/* Affiliate Details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Affiliate Details</Eyebrow>
              <HStack gap={4} wrap>
                <VStack gap={0}>
                  <Text tone="secondary">Referral Code</Text>
                  <Mono>{affiliate.code}</Mono>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Email</Text>
                  <Mono>{affiliate.email}</Mono>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Payout Method</Text>
                  <Text>{affiliate.payout_method ?? '—'}</Text>
                </VStack>
                {affiliate.approved_at && (
                  <VStack gap={0}>
                    <Text tone="secondary">Approved At</Text>
                    <Mono tone="secondary">{affiliate.approved_at}</Mono>
                  </VStack>
                )}
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* Referrals */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Referrals</Eyebrow>
              <DataTable
                columns={referralColumns}
                rows={referrals ?? []}
                empty="No referrals recorded yet."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* Payouts */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3}>
                <Eyebrow>Payout History</Eyebrow>
                {canPayout && (
                  <Button intent="soft" size="sm" leftIcon="plus" onClick={() => setPayoutOpen(true)}>
                    Create Payout
                  </Button>
                )}
              </HStack>
              <DataTable
                columns={payoutColumns}
                rows={payouts ?? []}
                empty="No payouts yet."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>

      {/* Approve Modal */}
      <Modal
        open={approveOpen}
        onClose={() => setApproveOpen(false)}
        title={`Approve "${affiliate.name}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setApproveOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doApprove}>
              Approve
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This activates the affiliate referral code and enables commission earning.
        </Text>
      </Modal>

      {/* Suspend Modal */}
      <Modal
        open={suspendOpen}
        onClose={() => setSuspendOpen(false)}
        title={`Suspend "${affiliate.name}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setSuspendOpen(false)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doSuspend}>
              Suspend
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          Suspending deactivates the affiliate referral code. Existing commissions are unaffected.
        </Text>
      </Modal>

      {/* Approve Commission Modal */}
      <Modal
        open={refApproveOpen}
        onClose={() => setRefApproveOpen(false)}
        title="Approve commission?"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setRefApproveOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doApproveRef}>
              Approve Commission
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          Approving marks the commission as ready for payout.
          Amount: ${Number(refTarget?.commission_amount ?? 0).toFixed(2)}
        </Text>
      </Modal>

      {/* Create Payout Modal */}
      <Modal
        open={payoutOpen}
        onClose={() => setPayoutOpen(false)}
        title="Create Payout"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setPayoutOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doCreatePayout}>
              Create Payout
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <HStack gap={3}>
            <Field label="Amount" htmlFor="payout_amount" required>
              <Input
                id="payout_amount"
                type="number"
                value={newPayout.amount}
                onChange={e => setNewPayout({ ...newPayout, amount: e.target.value })}
                placeholder="0.00"
              />
            </Field>
            <Field label="Currency" htmlFor="payout_currency">
              <Input
                id="payout_currency"
                value={newPayout.currency}
                onChange={e => setNewPayout({ ...newPayout, currency: e.target.value })}
                placeholder="USD"
              />
            </Field>
          </HStack>
          <Field label="Payout Method" htmlFor="payout_method">
            <Select
              id="payout_method"
              value={newPayout.method}
              onChange={e => setNewPayout({ ...newPayout, method: e.target.value })}
              options={PAYOUT_METHOD_OPTIONS}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Process Payout Modal */}
      <Modal
        open={processOpen}
        onClose={() => setProcessOpen(false)}
        title="Process payout?"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setProcessOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doProcessPayout}>
              Mark as Processing
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This marks the payout as processing. Amount: ${Number(processTarget?.amount ?? 0).toFixed(2)} {processTarget?.currency}
        </Text>
      </Modal>

    </DetailPageLayout>
  );
}

AffiliatesShow.layout = page => <App title="Affiliate Detail">{page}</App>;
