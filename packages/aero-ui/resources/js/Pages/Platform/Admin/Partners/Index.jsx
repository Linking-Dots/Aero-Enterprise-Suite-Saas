import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Modal,
  Drawer,
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

export default function PartnersIndex({ partners }) {
  const toast       = useToast();
  const canCreate   = useHRMAC('reseller-partners.partners.create');
  const canUpdate   = useHRMAC('reseller-partners.partners.update');
  const canApprove  = useHRMAC('reseller-partners.partners.approve');
  const canSuspend  = useHRMAC('reseller-partners.partners.suspend');

  const [drawerOpen,     setDrawerOpen]     = useState(false);
  const [editTarget,     setEditTarget]     = useState(null);
  const [approveTarget,  setApproveTarget]  = useState(null);
  const [suspendTarget,  setSuspendTarget]  = useState(null);
  const [submitting,     setSubmitting]     = useState(false);

  const form = useForm({
    name:            '',
    email:           '',
    commission_rate: '',
  });

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(partner) {
    setEditTarget(partner);
    form.setData({
      name:            partner.name,
      email:           partner.email,
      commission_rate: String(Number(partner.commission_rate) * 100),
    });
    setDrawerOpen(true);
  }

  function handleSave(e) {
    e.preventDefault();
    const payload = {
      ...form.data,
      commission_rate: Number(form.data.commission_rate) / 100,
    };
    if (editTarget) {
      form.put(
        route('platform.admin.partners.update', editTarget.id),
        {
          preserveState: true,
          onSuccess: () => { toast.success('Partner updated.'); setDrawerOpen(false); },
          onError:   () => toast.error('Failed to update partner.'),
        }
      );
    } else {
      router.post(
        route('platform.admin.partners.store'),
        payload,
        {
          preserveState: true,
          onSuccess: () => { toast.success('Partner created.'); setDrawerOpen(false); form.reset(); },
          onError:   () => toast.error('Failed to create partner.'),
        }
      );
    }
  }

  function doApprove() {
    if (!approveTarget) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.partners.approve', approveTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Partner approved.'); setApproveTarget(null); },
        onError:   () => toast.error('Failed to approve partner.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function doSuspend() {
    if (!suspendTarget) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.partners.suspend', suspendTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Partner suspended.'); setSuspendTarget(null); },
        onError:   () => toast.error('Failed to suspend partner.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  const columns = [
    {
      key: 'name',
      label: 'Partner',
      render: (row) => (
        <VStack gap={0}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('platform.admin.partners.show', row.id))}
          >
            {row.name}
          </Button>
          <Mono tone="secondary">{row.email}</Mono>
        </VStack>
      ),
    },
    {
      key: 'commission_rate',
      label: 'Commission %',
      render: (row) => <Mono>{(Number(row.commission_rate) * 100).toFixed(1)}%</Mono>,
    },
    {
      key: 'tenant_count',
      label: 'Tenants',
      render: (row) => <Mono>{row.tenant_count ?? 0}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'approved_at',
      label: 'Approved',
      render: (row) => <Mono tone="secondary">{row.approved_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('platform.admin.partners.show', row.id))}
          >
            View
          </Button>
          {canUpdate && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          {canApprove && row.status !== 'active' && (
            <Button intent="soft" size="sm" onClick={() => setApproveTarget(row)}>
              Approve
            </Button>
          )}
          {canSuspend && row.status === 'active' && (
            <Button intent="danger" size="sm" onClick={() => setSuspendTarget(row)}>
              Suspend
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Reseller Partners"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Partners' },
      ]}
      description="Manage reseller and channel partners, commission rates, and approval status."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            Add Partner
          </Button>
        )
      }
    >
      <DataTable
        columns={columns}
        rows={partners ?? []}
        empty="No partners registered yet."
      />

      {/* Create / Edit Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Partner' : 'New Partner'}
      >
        <VStack gap={4}>
          <Field label="Name" htmlFor="pt_name" error={form.errors.name} required>
            <Input
              id="pt_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="Partner Co."
              error={form.errors.name}
            />
          </Field>

          <Field label="Email" htmlFor="pt_email" error={form.errors.email} required>
            <Input
              id="pt_email"
              type="email"
              value={form.data.email}
              onChange={e => form.setData('email', e.target.value)}
              placeholder="partner@example.com"
              error={form.errors.email}
            />
          </Field>

          <Field label="Commission %" htmlFor="pt_commission" error={form.errors.commission_rate} required hint="Enter as percentage (e.g. 10 for 10%)">
            <Input
              id="pt_commission"
              type="number"
              value={form.data.commission_rate}
              onChange={e => form.setData('commission_rate', e.target.value)}
              placeholder="10"
              error={form.errors.commission_rate}
            />
          </Field>

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleSave}
            >
              {editTarget ? 'Update Partner' : 'Create Partner'}
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Approve Modal */}
      <Modal
        open={!!approveTarget}
        onClose={() => setApproveTarget(null)}
        title={`Approve "${approveTarget?.name}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setApproveTarget(null)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doApprove}>
              Approve Partner
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          Approving activates this partner's portal access and enables commission earning on referred tenants.
        </Text>
      </Modal>

      {/* Suspend Modal */}
      <Modal
        open={!!suspendTarget}
        onClose={() => setSuspendTarget(null)}
        title={`Suspend "${suspendTarget?.name}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setSuspendTarget(null)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doSuspend}>
              Suspend Partner
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          Suspending disables the partner portal. Existing commission records are unaffected.
        </Text>
      </Modal>
    </IndexPageLayout>
  );
}

PartnersIndex.layout = page => <App title="Reseller Partners">{page}</App>;
