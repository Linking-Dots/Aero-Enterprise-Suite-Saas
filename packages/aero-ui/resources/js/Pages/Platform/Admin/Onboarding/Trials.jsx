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

function daysLeft(dateStr) {
  if (!dateStr) return null;
  const diff = new Date(dateStr) - new Date();
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function trialIntent(days) {
  if (days == null) return 'neutral';
  if (days <= 3)   return 'danger';
  if (days <= 7)   return 'warning';
  return 'success';
}

export default function OnboardingTrials({ tenants }) {
  const toast = useToast();
  const canExtend  = useHRMAC('platform.admin.onboarding.extend');
  const canConvert = useHRMAC('platform.admin.onboarding.convert');

  const [extendOpen,   setExtendOpen]   = useState(false);
  const [extendTenant, setExtendTenant] = useState(null);
  const [extendDays,   setExtendDays]   = useState('7');
  const [submitting,   setSubmitting]   = useState(false);

  function openExtendModal(tenant) {
    setExtendTenant(tenant);
    setExtendDays('7');
    setExtendOpen(true);
  }

  function closeExtendModal() {
    setExtendOpen(false);
    setExtendTenant(null);
    setExtendDays('7');
  }

  function submitExtend() {
    if (!extendTenant) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.onboarding.extend', extendTenant.id),
      { days: Number(extendDays) },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Trial extended by ${extendDays} days.`);
          closeExtendModal();
        },
        onError:  () => toast.error('Failed to extend trial.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  function convertTenant(id) {
    router.post(
      route('platform.admin.onboarding.convert', id),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Tenant converted to paid plan.'),
        onError:   () => toast.error('Failed to convert tenant.'),
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
      key: 'trial_ends_at',
      label: 'Trial Ends',
      render: (row) => {
        const trialDate = row.stripe_trial_ends_at ?? row.trial_ends_at;
        if (!trialDate) return <Text tone="secondary">—</Text>;
        return (
          <Mono tone="secondary">{new Date(trialDate).toLocaleDateString()}</Mono>
        );
      },
    },
    {
      key: 'days_left',
      label: 'Days Left',
      render: (row) => {
        const trialDate = row.stripe_trial_ends_at ?? row.trial_ends_at;
        const days = daysLeft(trialDate);
        if (days == null) return <Text tone="secondary">—</Text>;
        return (
          <Badge intent={trialIntent(days)}>
            {days <= 0 ? 'Expired' : `${days}d`}
          </Badge>
        );
      },
    },
    {
      key: 'actions',
      label: '',
      align: 'right',
      render: (row) => (
        <HStack gap={2}>
          {canExtend && (
            <Button
              intent="soft"
              size="sm"
              onClick={() => openExtendModal(row)}
            >
              Extend
            </Button>
          )}
          {canConvert && (
            <Button
              intent="primary"
              size="sm"
              onClick={() => convertTenant(row.id)}
            >
              Convert
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Trial Management"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Onboarding' },
        { label: 'Trials' },
      ]}
      description="Monitor active trials and manage conversions."
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={tenants?.data ?? []}
          empty="No active trials found."
        />

        {tenants?.last_page > 1 && (
          <Pagination
            page={tenants.current_page}
            total={tenants.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.onboarding.trials'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['tenants'] }
              )
            }
          />
        )}
      </VStack>

      <Modal
        open={extendOpen}
        onClose={closeExtendModal}
        title={`Extend Trial: ${extendTenant?.name ?? ''}`}
        description="Enter the number of additional days to add to this tenant's trial period."
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={submitting}
              disabled={submitting || !extendDays || Number(extendDays) < 1}
              onClick={submitExtend}
            >
              Extend Trial
            </Button>
            <Button intent="ghost" onClick={closeExtendModal}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field
          label="Additional Days"
          htmlFor="extend_days"
          hint="Minimum 1 day, maximum 90 days."
        >
          <Input
            id="extend_days"
            type="number"
            value={extendDays}
            onChange={e => setExtendDays(e.target.value)}
            placeholder="7"
          />
        </Field>
      </Modal>
    </IndexPageLayout>
  );
}

OnboardingTrials.layout = page => <App title="Trial Management">{page}</App>;
