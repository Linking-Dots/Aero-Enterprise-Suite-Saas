import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
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
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  pending:   'warning',
  active:    'success',
  suspended: 'danger',
};

const COMMISSION_STATUS_INTENT = {
  pending:  'warning',
  approved: 'info',
  paid:     'success',
};

export default function PartnersShow({ partner, commissions, tenants, payoutSummary }) {
  const toast       = useToast();
  const canApprove  = useHRMAC('reseller-partners.partners.approve');
  const canSuspend  = useHRMAC('reseller-partners.partners.suspend');
  const canPayout   = useHRMAC('reseller-partners.partner-commissions.payout');
  const canReassign = useHRMAC('reseller-partners.partner-tenants.reassign');
  const canPortal   = useHRMAC('reseller-partners.partner-portal.configure');

  const [approveOpen,   setApproveOpen]   = useState(false);
  const [suspendOpen,   setSuspendOpen]   = useState(false);
  const [payoutOpen,    setPayoutOpen]    = useState(false);
  const [reassignTarget, setReassignTarget] = useState(null);
  const [portalOpen,    setPortalOpen]    = useState(false);
  const [submitting,    setSubmitting]    = useState(false);

  const portalForm = useForm({
    portal_slug: partner?.portal_slug ?? '',
  });

  const [newPartnerId, setNewPartnerId] = useState('');

  function doApprove() {
    setSubmitting(true);
    router.post(
      route('platform.admin.partners.approve', partner.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { setApproveOpen(false); toast.success('Partner approved.'); },
        onError:   () => toast.error('Failed to approve.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function doSuspend() {
    setSubmitting(true);
    router.post(
      route('platform.admin.partners.suspend', partner.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { setSuspendOpen(false); toast.success('Partner suspended.'); },
        onError:   () => toast.error('Failed to suspend.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function doPayout() {
    setSubmitting(true);
    router.post(
      route('platform.admin.partners.commissions.payout', partner.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { setPayoutOpen(false); toast.success('Payout processed.'); },
        onError:   () => toast.error('Failed to process payout.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function doReassign() {
    if (!reassignTarget || !newPartnerId) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.partners.tenants.reassign', reassignTarget),
      { partner_id: newPartnerId },
      {
        preserveState: true,
        onSuccess: () => { setReassignTarget(null); setNewPartnerId(''); toast.success('Tenant reassigned.'); },
        onError:   () => toast.error('Failed to reassign tenant.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function savePortal(e) {
    e.preventDefault();
    portalForm.put(
      route('platform.admin.partners.portal.update', partner.id),
      {
        preserveState: true,
        onSuccess: () => { setPortalOpen(false); toast.success('Portal config saved.'); },
        onError:   () => toast.error('Failed to save portal config.'),
      }
    );
  }

  const commissionColumns = [
    {
      key: 'tenant_id',
      label: 'Tenant',
      render: (row) => <Mono>{row.tenant_id}</Mono>,
    },
    {
      key: 'amount',
      label: 'Amount',
      render: (row) => <Mono>${Number(row.amount).toFixed(2)}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={COMMISSION_STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'paid_at',
      label: 'Paid At',
      render: (row) => <Mono tone="secondary">{row.paid_at ?? '—'}</Mono>,
    },
  ];

  const tenantColumns = [
    {
      key: 'name',
      label: 'Tenant',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.tenants.show', row.id))}
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.status === 'active' ? 'success' : 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canReassign && (
          <Button intent="ghost" size="sm" onClick={() => setReassignTarget(row.id)}>
            Reassign
          </Button>
        ),
    },
  ];

  const pendingCommissions = (commissions ?? []).filter(c => c.status === 'pending');
  const pendingTotal = pendingCommissions.reduce((sum, c) => sum + Number(c.amount), 0);

  return (
    <DetailPageLayout
      title={partner.name}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Partners', href: route('platform.admin.partners.index') },
        { label: partner.name },
      ]}
      description={partner.email}
      actions={
        <HStack gap={2}>
          <Badge intent={STATUS_INTENT[partner.status] ?? 'neutral'}>{partner.status}</Badge>
          {canApprove && partner.status !== 'active' && (
            <Button intent="soft" onClick={() => setApproveOpen(true)}>Approve</Button>
          )}
          {canSuspend && partner.status === 'active' && (
            <Button intent="danger" onClick={() => setSuspendOpen(true)}>Suspend</Button>
          )}
          {canPortal && (
            <Button intent="ghost" onClick={() => setPortalOpen(true)}>Portal Config</Button>
          )}
          <Button intent="ghost" onClick={() => router.get(route('platform.admin.partners.index'))}>
            Back
          </Button>
        </HStack>
      }
    >
      <VStack gap={4}>

        {/* KPIs */}
        <HStack gap={4}>
          <KPI label="Commission Rate" value={`${(Number(partner.commission_rate) * 100).toFixed(1)}%`} />
          <KPI label="Total Tenants" value={tenants?.length ?? 0} />
          <KPI label="Pending Payout" value={`$${pendingTotal.toFixed(2)}`} />
        </HStack>

        {/* Commission History */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3}>
                <Eyebrow>Commission History</Eyebrow>
                {canPayout && pendingTotal > 0 && (
                  <Button intent="soft" size="sm" leftIcon="currencyDollar" onClick={() => setPayoutOpen(true)}>
                    Process Payout
                  </Button>
                )}
              </HStack>
              <DataTable
                columns={commissionColumns}
                rows={commissions ?? []}
                empty="No commissions recorded yet."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* Partner Tenants */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Assigned Tenants</Eyebrow>
              <DataTable
                columns={tenantColumns}
                rows={tenants ?? []}
                empty="No tenants assigned to this partner."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>

      {/* Approve Modal */}
      <Modal
        open={approveOpen}
        onClose={() => setApproveOpen(false)}
        title={`Approve "${partner.name}"?`}
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
          This activates the partner portal and enables commission earning.
        </Text>
      </Modal>

      {/* Suspend Modal */}
      <Modal
        open={suspendOpen}
        onClose={() => setSuspendOpen(false)}
        title={`Suspend "${partner.name}"?`}
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
          Suspending disables portal access. Existing commission records are unaffected.
        </Text>
      </Modal>

      {/* Payout Modal */}
      <Modal
        open={payoutOpen}
        onClose={() => setPayoutOpen(false)}
        title="Process Pending Payout"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setPayoutOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doPayout}>
              Process Payout
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            This will mark all pending commissions as paid for this partner.
          </Text>
          <HStack gap={2}>
            <Text tone="secondary">Pending amount:</Text>
            <Mono>${pendingTotal.toFixed(2)}</Mono>
          </HStack>
        </VStack>
      </Modal>

      {/* Reassign Tenant Modal */}
      <Modal
        open={!!reassignTarget}
        onClose={() => { setReassignTarget(null); setNewPartnerId(''); }}
        title="Reassign Tenant"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => { setReassignTarget(null); setNewPartnerId(''); }}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting || !newPartnerId} onClick={doReassign}>
              Reassign
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">Enter the new partner ID to reassign this tenant.</Text>
          <Field label="New Partner ID" htmlFor="reassign_partner">
            <Input
              id="reassign_partner"
              type="number"
              value={newPartnerId}
              onChange={e => setNewPartnerId(e.target.value)}
              placeholder="Partner ID"
            />
          </Field>
        </VStack>
      </Modal>

      {/* Portal Config Modal */}
      <Modal
        open={portalOpen}
        onClose={() => setPortalOpen(false)}
        title="Partner Portal Configuration"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setPortalOpen(false)}>Cancel</Button>
            <Button
              intent="primary"
              loading={portalForm.processing}
              disabled={portalForm.processing}
              onClick={savePortal}
            >
              Save Portal Config
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Field label="Portal Slug" htmlFor="portal_slug" error={portalForm.errors.portal_slug} hint="Unique URL slug for the partner portal">
            <Input
              id="portal_slug"
              value={portalForm.data.portal_slug}
              onChange={e => portalForm.setData('portal_slug', e.target.value)}
              placeholder="partner-co"
              error={portalForm.errors.portal_slug}
            />
          </Field>
        </VStack>
      </Modal>
    </DetailPageLayout>
  );
}

PartnersShow.layout = page => <App title="Partner Detail">{page}</App>;
