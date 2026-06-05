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
  Toggle,
  Modal,
  Textarea,
  Pagination,
  Card,
  CardBody,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  draft:     'neutral',
  published: 'success',
  dismissed: 'warning',
};

const TARGET_OPTIONS = [
  { value: 'all',      label: 'All Tenants' },
  { value: 'specific', label: 'Specific Tenants' },
];

export default function Broadcasts({ broadcasts, tenants, filters }) {
  const toast       = useToast();
  const canCreate   = useHRMAC('tenant-communications.broadcasts.create');
  const canPublish  = useHRMAC('tenant-communications.broadcasts.publish');
  const canDismiss  = useHRMAC('tenant-communications.broadcasts.dismiss-all');

  const [showCreate,      setShowCreate]      = useState(false);
  const [publishTarget,   setPublishTarget]   = useState(null);
  const [dismissTarget,   setDismissTarget]   = useState(null);
  const [tenantIdsInput,  setTenantIdsInput]  = useState('');

  const form = useForm({
    title:             '',
    body:              '',
    target:            'all',
    target_tenant_ids: [],
  });

  const tenantOptions = [
    { value: '', label: 'Select tenant to add…' },
    ...(tenants ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  const [addTenantId, setAddTenantId] = useState('');

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
    form.post(route('platform.admin.tenant-comms.broadcasts.store'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('Broadcast created.');
        setShowCreate(false);
        form.reset();
      },
      onError: () => toast.error('Failed to create broadcast.'),
    });
  }

  function confirmPublish() {
    if (!publishTarget) return;
    router.post(
      route('platform.admin.tenant-comms.broadcasts.publish', publishTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Broadcast "${publishTarget.title}" published.`);
          setPublishTarget(null);
        },
        onError: () => toast.error('Publish failed.'),
      }
    );
  }

  function confirmDismissAll() {
    if (!dismissTarget) return;
    router.post(
      route('platform.admin.tenant-comms.broadcasts.dismiss-all', dismissTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('All dismissals recorded.');
          setDismissTarget(null);
        },
        onError: () => toast.error('Dismiss failed.'),
      }
    );
  }

  const columns = [
    {
      key: 'title',
      label: 'Title',
      render: (row) => <Text>{row.title}</Text>,
    },
    {
      key: 'target',
      label: 'Target',
      render: (row) => (
        <Badge intent="neutral">
          {row.target === 'all' ? 'All Tenants' : `Specific (${(row.target_tenant_ids ?? []).length})`}
        </Badge>
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
      key: 'published_at',
      label: 'Published',
      render: (row) => <Mono>{row.published_at ?? '—'}</Mono>,
    },
    {
      key: 'dismissed_count',
      label: 'Dismissed',
      render: (row) => <Text tone="secondary">{row.dismissed_count ?? 0}</Text>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canPublish && row.status === 'draft' && (
            <Button intent="soft" size="sm" onClick={() => setPublishTarget(row)}>
              Publish
            </Button>
          )}
          {canDismiss && row.status === 'published' && (
            <Button intent="ghost" size="sm" onClick={() => setDismissTarget(row)}>
              Dismiss All
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Broadcasts"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenant Comms' },
        { label: 'Broadcasts' },
      ]}
      description="Send in-app broadcast messages to tenants."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
            New Broadcast
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={broadcasts?.data ?? []}
          empty="No broadcasts found."
        />

        {(broadcasts?.last_page ?? 1) > 1 && (
          <Pagination
            page={broadcasts.current_page}
            total={broadcasts.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.tenant-comms.broadcasts.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['broadcasts'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create Modal */}
      <Modal
        open={showCreate}
        onClose={() => { setShowCreate(false); form.reset(); }}
        title="New Broadcast"
        size="lg"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleCreate}
            >
              Create Broadcast
            </Button>
            <Button intent="ghost" onClick={() => { setShowCreate(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Title" htmlFor="bc_title" error={form.errors.title} required>
            <Input
              id="bc_title"
              value={form.data.title}
              onChange={e => form.setData('title', e.target.value)}
              placeholder="Platform Maintenance Tonight"
              error={form.errors.title}
            />
          </Field>

          <Field label="Message Body" htmlFor="bc_body" error={form.errors.body} hint="HTML is supported." required>
            <Textarea
              id="bc_body"
              value={form.data.body}
              onChange={e => form.setData('body', e.target.value)}
              placeholder="We will be performing scheduled maintenance…"
              error={form.errors.body}
              rows={5}
            />
          </Field>

          <Field label="Target" htmlFor="bc_target" error={form.errors.target}>
            <Select
              id="bc_target"
              value={form.data.target}
              onChange={e => form.setData('target', e.target.value)}
              options={TARGET_OPTIONS}
            />
          </Field>

          {form.data.target === 'specific' && (
            <Field label="Target Tenants" htmlFor="bc_tenant_add" error={form.errors.target_tenant_ids}>
              <HStack gap={2}>
                <Select
                  id="bc_tenant_add"
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

      {/* Publish Confirm */}
      <Modal
        open={!!publishTarget}
        onClose={() => setPublishTarget(null)}
        title="Publish Broadcast"
        description={`Publish "${publishTarget?.title}"? It will become visible to ${publishTarget?.target === 'all' ? 'all tenants' : 'the selected tenants'} immediately.`}
        footer={
          <HStack gap={2}>
            <Button intent="primary" onClick={confirmPublish}>
              Publish
            </Button>
            <Button intent="ghost" onClick={() => setPublishTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Dismiss All Confirm */}
      <Modal
        open={!!dismissTarget}
        onClose={() => setDismissTarget(null)}
        title="Dismiss Broadcast for All"
        description={`Mark broadcast "${dismissTarget?.title}" as dismissed for all tenants who have not yet dismissed it?`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmDismissAll}>
              Dismiss All
            </Button>
            <Button intent="ghost" onClick={() => setDismissTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

Broadcasts.layout = page => <App title="Broadcasts">{page}</App>;
