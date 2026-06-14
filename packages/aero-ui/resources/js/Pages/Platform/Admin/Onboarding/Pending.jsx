import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Badge,
  Button,
  Field,
  Input,
  Modal,
  Pagination,
  HStack,
  VStack,
  Text,
  Mono,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function OnboardingPending({ tenants }) {
  const toast = useToast();
  const canApprove = useHRMAC('platform.admin.onboarding.approve');
  const canReject  = useHRMAC('platform.admin.onboarding.reject');

  const [rejectOpen,    setRejectOpen]    = useState(false);
  const [rejectTenant,  setRejectTenant]  = useState(null);
  const [rejectReason,  setRejectReason]  = useState('');
  const [submitting,    setSubmitting]    = useState(false);

  function openRejectModal(tenant) {
    setRejectTenant(tenant);
    setRejectReason('');
    setRejectOpen(true);
  }

  function closeRejectModal() {
    setRejectOpen(false);
    setRejectTenant(null);
    setRejectReason('');
  }

  function approveTenant(id) {
    router.post(
      route('platform.admin.onboarding.approve', id),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Tenant approved and provisioning started.'),
        onError:   () => toast.error('Failed to approve tenant.'),
      }
    );
  }

  function submitReject() {
    if (!rejectTenant) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.onboarding.reject', rejectTenant.id),
      { reason: rejectReason },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Tenant rejected.');
          closeRejectModal();
        },
        onError:  () => toast.error('Failed to reject tenant.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  const columns = [
    {
      key: 'name',
      label: 'Tenant',
      render: (row) => (
        <VStack gap={0}>
          <Text>{row.name}</Text>
          <Text tone="secondary">{row.email}</Text>
        </VStack>
      ),
    },
    {
      key: 'plan',
      label: 'Plan',
      render: (row) => (
        <Badge intent="neutral">{row.plan?.name ?? '—'}</Badge>
      ),
    },
    {
      key: 'product',
      label: 'Product',
      render: (row) => {
        if (!row.pending_product) return <Text tone="secondary">—</Text>;
        return <Badge intent="amber">{row.pending_product.name}</Badge>;
      },
    },
    {
      key: 'created_at',
      label: 'Registered',
      render: (row) => (
        <Mono tone="secondary">{new Date(row.created_at).toLocaleString()}</Mono>
      ),
    },
    {
      key: 'actions',
      label: '',
      align: 'right',
      render: (row) => (
        <HStack gap={2}>
          {canApprove && (
            <Button
              intent="soft"
              size="sm"
              onClick={() => approveTenant(row.id)}
            >
              Approve
            </Button>
          )}
          {canReject && (
            <Button
              intent="danger"
              size="sm"
              onClick={() => openRejectModal(row)}
            >
              Reject
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Pending Approvals"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Onboarding' },
        { label: 'Pending Approvals' },
      ]}
      description="Review and approve new tenant registrations. Approval activates both the plan subscription and the initial ProductSubscription for module access."
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={tenants?.data ?? []}
          empty="No tenants pending approval."
        />

        {tenants?.last_page > 1 && (
          <Pagination
            page={tenants.current_page}
            total={tenants.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.onboarding.pending'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['tenants'] }
              )
            }
          />
        )}
      </VStack>

      <Modal
        open={rejectOpen}
        onClose={closeRejectModal}
        title={`Reject Tenant: ${rejectTenant?.name ?? ''}`}
        description="Provide a reason for rejection. This will be sent to the applicant."
        footer={
          <HStack gap={2}>
            <Button
              intent="danger"
              loading={submitting}
              disabled={submitting}
              onClick={submitReject}
            >
              Confirm Rejection
            </Button>
            <Button intent="ghost" onClick={closeRejectModal}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field label="Rejection Reason" htmlFor="reject_reason">
          <Input
            id="reject_reason"
            value={rejectReason}
            onChange={e => setRejectReason(e.target.value)}
            placeholder="Incomplete information, policy violation, etc."
          />
        </Field>
      </Modal>
    </IndexPageLayout>
  );
}

OnboardingPending.layout = page => <App title="Pending Approvals">{page}</App>;
