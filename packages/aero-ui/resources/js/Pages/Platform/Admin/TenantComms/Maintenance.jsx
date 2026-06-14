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
  Select,
  Textarea,
  Toggle,
  Modal,
  Pagination,
  Card,
  CardBody,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  scheduled: 'warning',
  active:    'danger',
  cancelled: 'neutral',
  completed: 'success',
};

const AFFECTED_OPTIONS = [
  { value: 'all',      label: 'All Tenants' },
  { value: 'specific', label: 'Specific Tenants' },
];

export default function Maintenance({ windows, tenants, filters }) {
  const toast       = useToast();
  const canSchedule = useHRMAC('tenant-communications.maintenance-windows.schedule');
  const canCancel   = useHRMAC('tenant-communications.maintenance-windows.cancel');

  const [showCreate,   setShowCreate]   = useState(false);
  const [cancelTarget, setCancelTarget] = useState(null);
  const [viewTarget,   setViewTarget]   = useState(null);
  const [addTenantId,  setAddTenantId]  = useState('');

  const form = useForm({
    title:            '',
    message:          '',
    starts_at:        '',
    ends_at:          '',
    affected_tenants: 'all',
    target_tenant_ids: [],
  });

  const tenantOptions = [
    { value: '', label: 'Select tenant to add…' },
    ...(tenants ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  function addTenant() {
    if (addTenantId && !form.data.target_tenant_ids.includes(addTenantId)) {
      form.setData('target_tenant_ids', [...form.data.target_tenant_ids, addTenantId]);
    }
    setAddTenantId('');
  }

  function removeTenant(id) {
    form.setData('target_tenant_ids', form.data.target_tenant_ids.filter(x => x !== id));
  }

  function tenantName(id) {
    const t = (tenants ?? []).find(t => String(t.id) === String(id));
    return t ? t.name : `#${id}`;
  }

  function handleCreate(e) {
    e.preventDefault();
    form.post(route('platform.admin.tenant-comms.maintenance.store'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('Maintenance window scheduled.');
        setShowCreate(false);
        form.reset();
      },
      onError: () => toast.error('Failed to schedule maintenance window.'),
    });
  }

  function confirmCancel() {
    if (!cancelTarget) return;
    router.post(
      route('platform.admin.tenant-comms.maintenance.cancel', cancelTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Maintenance window "${cancelTarget.title}" cancelled.`);
          setCancelTarget(null);
        },
        onError: () => toast.error('Cancel failed.'),
      }
    );
  }

  const columns = [
    {
      key: 'title',
      label: 'Title',
      render: (row) => (
        <Button intent="ghost" size="sm" onClick={() => setViewTarget(row)}>
          {row.title}
        </Button>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'affected',
      label: 'Affected',
      render: (row) => (
        <Badge intent="neutral">
          {row.affected_tenants === 'all' ? 'All Tenants' : 'Specific'}
        </Badge>
      ),
    },
    {
      key: 'starts_at',
      label: 'Starts',
      render: (row) => <Mono>{row.starts_at}</Mono>,
    },
    {
      key: 'ends_at',
      label: 'Ends',
      render: (row) => <Mono>{row.ends_at}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canCancel && (row.status === 'scheduled' || row.status === 'active') ? (
          <Button intent="danger" size="sm" onClick={() => setCancelTarget(row)}>
            Cancel
          </Button>
        ) : null,
    },
  ];

  return (
    <IndexPageLayout
      title="Maintenance Windows"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenant Comms' },
        { label: 'Maintenance' },
      ]}
      description="Schedule and manage platform maintenance windows visible to tenants."
      actions={
        canSchedule && (
          <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
            Schedule Maintenance
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={windows?.data ?? []}
          empty="No maintenance windows scheduled."
        />

        {(windows?.last_page ?? 1) > 1 && (
          <Pagination
            page={windows.current_page}
            total={windows.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.tenant-comms.maintenance.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['windows'] }
              )
            }
          />
        )}
      </VStack>

      {/* Schedule Modal */}
      <Modal
        open={showCreate}
        onClose={() => { setShowCreate(false); form.reset(); }}
        title="Schedule Maintenance Window"
        size="lg"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleCreate}
            >
              Schedule
            </Button>
            <Button intent="ghost" onClick={() => { setShowCreate(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Title" htmlFor="mw_title" error={form.errors.title} required>
            <Input
              id="mw_title"
              value={form.data.title}
              onChange={e => form.setData('title', e.target.value)}
              placeholder="Database Migration — Read-only Mode"
              error={form.errors.title}
            />
          </Field>

          <Field label="Message" htmlFor="mw_message" error={form.errors.message} hint="Shown to tenants during the window." required>
            <Textarea
              id="mw_message"
              value={form.data.message}
              onChange={e => form.setData('message', e.target.value)}
              placeholder="We are performing scheduled maintenance. Some features may be unavailable."
              rows={4}
              error={form.errors.message}
            />
          </Field>

          <HStack gap={4}>
            <Field label="Starts At" htmlFor="mw_starts" error={form.errors.starts_at} required>
              <Input
                id="mw_starts"
                type="datetime-local"
                value={form.data.starts_at}
                onChange={e => form.setData('starts_at', e.target.value)}
                error={form.errors.starts_at}
              />
            </Field>

            <Field label="Ends At" htmlFor="mw_ends" error={form.errors.ends_at} required>
              <Input
                id="mw_ends"
                type="datetime-local"
                value={form.data.ends_at}
                onChange={e => form.setData('ends_at', e.target.value)}
                error={form.errors.ends_at}
              />
            </Field>
          </HStack>

          <Field label="Affected Tenants" htmlFor="mw_affected" error={form.errors.affected_tenants}>
            <Select
              id="mw_affected"
              value={form.data.affected_tenants}
              onChange={e => form.setData('affected_tenants', e.target.value)}
              options={AFFECTED_OPTIONS}
            />
          </Field>

          {form.data.affected_tenants === 'specific' && (
            <Field label="Select Tenants" htmlFor="mw_tenant_add" error={form.errors.target_tenant_ids}>
              <HStack gap={2}>
                <Select
                  id="mw_tenant_add"
                  value={addTenantId}
                  onChange={e => setAddTenantId(e.target.value)}
                  options={tenantOptions}
                />
                <Button intent="soft" size="sm" onClick={addTenant}>
                  Add
                </Button>
              </HStack>
              {form.data.target_tenant_ids.length > 0 && (
                <HStack gap={1} wrap>
                  {form.data.target_tenant_ids.map(id => (
                    <HStack key={id} gap={1}>
                      <Badge intent="neutral">{tenantName(id)}</Badge>
                      <Button intent="ghost" size="sm" onClick={() => removeTenant(id)}>
                        x
                      </Button>
                    </HStack>
                  ))}
                </HStack>
              )}
            </Field>
          )}
        </VStack>
      </Modal>

      {/* Cancel Confirm */}
      <Modal
        open={!!cancelTarget}
        onClose={() => setCancelTarget(null)}
        title="Cancel Maintenance Window"
        description={`Cancel maintenance window "${cancelTarget?.title}"? Tenants will no longer see the maintenance notice.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmCancel}>
              Confirm Cancel
            </Button>
            <Button intent="ghost" onClick={() => setCancelTarget(null)}>
              Keep
            </Button>
          </HStack>
        }
      />

      {/* View Detail Modal */}
      <Modal
        open={!!viewTarget}
        onClose={() => setViewTarget(null)}
        title={viewTarget?.title}
        size="md"
        footer={
          <Button intent="ghost" onClick={() => setViewTarget(null)}>Close</Button>
        }
      >
        {viewTarget && (
          <VStack gap={3}>
            <HStack gap={4} wrap>
              <VStack gap={0}>
                <Text tone="secondary">Status</Text>
                <Badge intent={STATUS_INTENT[viewTarget.status] ?? 'neutral'}>
                  {viewTarget.status}
                </Badge>
              </VStack>
              <VStack gap={0}>
                <Text tone="secondary">Affected</Text>
                <Badge intent="neutral">
                  {viewTarget.affected_tenants === 'all' ? 'All Tenants' : 'Specific'}
                </Badge>
              </VStack>
            </HStack>
            <HStack gap={4} wrap>
              <VStack gap={0}>
                <Text tone="secondary">Starts At</Text>
                <Mono>{viewTarget.starts_at}</Mono>
              </VStack>
              <VStack gap={0}>
                <Text tone="secondary">Ends At</Text>
                <Mono>{viewTarget.ends_at}</Mono>
              </VStack>
              {viewTarget.cancelled_at && (
                <VStack gap={0}>
                  <Text tone="secondary">Cancelled At</Text>
                  <Mono>{viewTarget.cancelled_at}</Mono>
                </VStack>
              )}
            </HStack>
            <VStack gap={0}>
              <Text tone="secondary">Message</Text>
              <Text>{viewTarget.message}</Text>
            </VStack>
          </VStack>
        )}
      </Modal>
    </IndexPageLayout>
  );
}

Maintenance.layout = page => <App title="Maintenance Windows">{page}</App>;
