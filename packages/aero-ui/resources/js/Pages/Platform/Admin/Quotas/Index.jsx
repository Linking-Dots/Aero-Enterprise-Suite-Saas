import { useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
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
  Card,
  CardBody,
  KPI,
  Field,
  Input,
  Textarea,
  Select,
  Modal,
  useToast,
  useHRMAC,
  Pagination,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const RESOURCE_OPTIONS = [
  { value: 'storage_gb',  label: 'storage_gb' },
  { value: 'api_calls',   label: 'api_calls' },
  { value: 'users',       label: 'users' },
  { value: 'modules',     label: 'modules' },
  { value: 'ai_messages', label: 'ai_messages (AI Assistant / month)' },
];

export default function QuotasIndex({ overrides, analytics, filters }) {
  const { errors } = usePage().props;
  const toast = useToast();
  const canOverride = useHRMAC('quota-management.quota-dashboard.override');

  const [removeTarget, setRemoveTarget] = useState(null);
  const [overrideModal, setOverrideModal] = useState(false);
  const [selectedTenant, setSelectedTenant] = useState(null);

  const form = useForm({
    resource:    'storage_gb',
    limit_value: 0,
    reason:      '',
    expires_at:  '',
  });

  function openOverride(tenant) {
    setSelectedTenant(tenant);
    form.reset();
    setOverrideModal(true);
  }

  function submitOverride(e) {
    e.preventDefault();
    form.post(route('platform.admin.quotas.override', selectedTenant.tenant_id), {
      onSuccess: () => {
        setOverrideModal(false);
        toast.success('Override saved.');
      },
      onError: () => toast.error('Failed to save override.'),
    });
  }

  function confirmRemove() {
    if (!removeTarget) return;
    router.delete(
      route('platform.admin.quotas.override.remove', {
        tenant:   removeTarget.tenant_id,
        resource: removeTarget.resource,
      }),
      {
        onSuccess: () => {
          toast.success('Override removed.');
          setRemoveTarget(null);
        },
        onError: () => toast.error('Failed to remove override.'),
      }
    );
  }

  const columns = [
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
      key: 'resource',
      label: 'Resource',
      render: (row) => <Mono>{row.resource}</Mono>,
    },
    {
      key: 'limit_value',
      label: 'Limit',
      render: (row) => <Mono>{row.limit_value.toLocaleString()}</Mono>,
    },
    {
      key: 'expires_at',
      label: 'Expires',
      render: (row) => (
        <Mono tone="secondary">
          {row.expires_at ? new Date(row.expires_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
    {
      key: 'set_by',
      label: 'Set By',
      render: (row) => <Text tone="secondary">{row.setter?.name ?? '—'}</Text>,
    },
    {
      key: 'reason',
      label: 'Reason',
      render: (row) => <Text tone="secondary">{row.reason ?? '—'}</Text>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canOverride && (
            <>
              <Button intent="ghost" size="sm" onClick={() => openOverride(row)}>
                Edit
              </Button>
              <Button intent="danger" size="sm" onClick={() => setRemoveTarget(row)}>
                Remove
              </Button>
            </>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Quota Management"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Quotas' },
      ]}
      description="Per-tenant quota overrides and enforcement analytics."
      actions={
        canOverride && (
          <HStack gap={2}>
            <Button
              intent="soft"
              onClick={() => router.get(route('platform.admin.quotas.settings'))}
            >
              Enforcement Settings
            </Button>
          </HStack>
        )
      }
    >
      <VStack gap={6}>

        {/* Analytics KPIs */}
        <HStack gap={4} wrap>
          <KPI label="Total Overrides" value={analytics?.overrides_count ?? 0} />
          <KPI
            label="Expiring Soon (7d)"
            value={analytics?.expiring_soon ?? 0}
            intent={analytics?.expiring_soon > 0 ? 'warning' : 'success'}
          />
          {Object.entries(analytics?.by_resource ?? {}).map(([resource, count]) => (
            <KPI key={resource} label={`${resource} overrides`} value={count} />
          ))}
        </HStack>

        {/* Overrides table */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Tenant Quota Overrides</Eyebrow>
              <DataTable
                columns={columns}
                rows={overrides?.data ?? []}
                empty="No quota overrides set."
              />
              {overrides?.last_page > 1 && (
                <Pagination
                  page={overrides.current_page}
                  total={overrides.last_page}
                  onChange={page =>
                    router.get(
                      route('platform.admin.quotas.index'),
                      { ...filters, page },
                      { preserveState: true, preserveScroll: true, only: ['overrides'] }
                    )
                  }
                />
              )}
            </VStack>
          </CardBody>
        </Card>

      </VStack>

      {/* Set Override Modal */}
      <Modal
        open={overrideModal}
        onClose={() => setOverrideModal(false)}
        title={`Set Override — ${selectedTenant?.tenant?.name ?? ''}`}
        description="Override the default quota limit for a specific resource on this tenant."
        footer={
          <HStack gap={2}>
            <Button intent="primary" type="submit" form="override-form" loading={form.processing}>
              Save Override
            </Button>
            <Button intent="ghost" onClick={() => setOverrideModal(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form id="override-form" onSubmit={submitOverride}>
          <VStack gap={4}>
            <Field label="Resource" htmlFor="resource" error={errors.resource} required>
              <Select
                value={form.data.resource}
                onChange={e => form.setData('resource', e.target.value)}
                options={RESOURCE_OPTIONS}
              />
            </Field>
            <Field label="Limit Value" htmlFor="limit_value" error={errors.limit_value} required>
              <Input
                id="limit_value"
                type="number"
                value={String(form.data.limit_value)}
                onChange={e => form.setData('limit_value', Number(e.target.value))}
                error={!!errors.limit_value}
              />
            </Field>
            <Field label="Reason" htmlFor="reason" error={errors.reason}>
              <Textarea
                id="reason"
                value={form.data.reason}
                onChange={e => form.setData('reason', e.target.value)}
                placeholder="Optional reason for this override..."
              />
            </Field>
            <Field label="Expires At" htmlFor="expires_at" error={errors.expires_at} hint="Leave blank for no expiry.">
              <Input
                id="expires_at"
                type="datetime-local"
                value={form.data.expires_at}
                onChange={e => form.setData('expires_at', e.target.value)}
              />
            </Field>
          </VStack>
        </form>
      </Modal>

      {/* Remove Confirmation Modal */}
      <Modal
        open={!!removeTarget}
        onClose={() => setRemoveTarget(null)}
        title="Remove Quota Override"
        description={`Remove the ${removeTarget?.resource} override for ${removeTarget?.tenant?.name}? The tenant will revert to the default quota limit.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmRemove}>
              Remove Override
            </Button>
            <Button intent="ghost" onClick={() => setRemoveTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

    </IndexPageLayout>
  );
}

QuotasIndex.layout = page => <App title="Quota Management">{page}</App>;
