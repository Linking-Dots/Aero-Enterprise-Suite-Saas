import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Box,
  HStack, VStack,
  Text, Mono,
  Field,
  Input,
  Toggle,
  Modal,
  useToast,
  useHRMAC,
  Pagination,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ALL_EVENTS = [
  { value: 'user.created',      label: 'user.created' },
  { value: 'user.updated',      label: 'user.updated' },
  { value: 'user.deleted',      label: 'user.deleted' },
  { value: 'invoice.sent',      label: 'invoice.sent' },
  { value: 'invoice.paid',      label: 'invoice.paid' },
  { value: 'payroll.run',       label: 'payroll.run' },
  { value: 'leave.approved',    label: 'leave.approved' },
  { value: 'leave.rejected',    label: 'leave.rejected' },
  { value: 'role.assigned',     label: 'role.assigned' },
  { value: 'tenant.updated',    label: 'tenant.updated' },
];

const MAX_VISIBLE_EVENTS = 2;

function EventCheckboxes({ selected, onChange, disabled }) {
  const toggle = value => {
    if (disabled) return;
    onChange(
      selected.includes(value)
        ? selected.filter(v => v !== value)
        : [...selected, value]
    );
  };

  return (
    <VStack gap={2}>
      {ALL_EVENTS.map(evt => (
        <label key={evt.value} className="wh-event-label">
          <input
            type="checkbox"
            checked={selected.includes(evt.value)}
            onChange={() => toggle(evt.value)}
            disabled={disabled}
          />
          <Mono size="sm">{evt.label}</Mono>
        </label>
      ))}
    </VStack>
  );
}

export default function Webhooks({ webhooks, flash }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('core.api.webhooks.create');
  const canEdit    = useHRMAC('core.api.webhooks.edit');
  const canDelete  = useHRMAC('core.api.webhooks.delete');
  const canTest    = useHRMAC('core.api.webhooks.test');

  const [editTarget, setEditTarget] = useState(null); // null = create mode, object = edit mode
  const [showModal,  setShowModal]  = useState(false);
  const [dismissed,  setDismissed]  = useState(false);
  const [copied,     setCopied]     = useState(false);

  const form = useForm({
    name:      '',
    url:       '',
    events:    [],
    is_active: true,
  });

  const openCreate = () => {
    setEditTarget(null);
    form.reset();
    setShowModal(true);
  };

  const openEdit = row => {
    setEditTarget(row);
    form.setData({
      name:      row.name      ?? '',
      url:       row.url       ?? '',
      events:    row.events    ?? [],
      is_active: row.is_active ?? true,
    });
    setShowModal(true);
  };

  const resetModal = () => {
    setShowModal(false);
    setEditTarget(null);
    form.reset();
  };

  const handleSubmit = e => {
    e.preventDefault();
    if (editTarget) {
      router.put(route('core.api.webhooks.update', editTarget.id), form.data, {
        preserveState: true,
        onSuccess: () => { toast.success('Webhook updated.'); resetModal(); },
        onError:   () => toast.error('Failed to update webhook.'),
      });
    } else {
      form.post(route('core.api.webhooks.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Webhook created.'); resetModal(); },
        onError:   () => toast.error('Failed to create webhook.'),
      });
    }
  };

  const handleDelete = row => {
    if (!confirm(`Delete webhook "${row.name}"?`)) return;
    router.delete(route('core.api.webhooks.destroy', row.id), {
      onSuccess: () => toast.success('Webhook deleted.'),
      onError:   () => toast.error('Failed to delete webhook.'),
    });
  };

  const handleTest = row => {
    router.post(route('core.api.webhooks.test', row.id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Test payload sent.'),
      onError:   () => toast.error('Test delivery failed.'),
    });
  };

  const copySecret = async text => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error('Copy failed — please select the secret manually.');
    }
  };

  const columns = [
    {
      key: 'name', label: 'Name', width: '15%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'url', label: 'URL', width: '22%',
      render: row => (
        <Mono size="sm" className="wh-url-cell" title={row.url}>
          {row.url?.length > 45 ? `${row.url.slice(0, 45)}…` : (row.url ?? '—')}
        </Mono>
      ),
    },
    {
      key: 'events', label: 'Events', width: '22%',
      render: row => {
        const evts = row.events ?? [];
        const visible = evts.slice(0, MAX_VISIBLE_EVENTS);
        const remaining = evts.length - MAX_VISIBLE_EVENTS;
        return (
          <HStack gap={1} wrap>
            {visible.map(e => <Badge key={e} intent="neutral" size="sm">{e}</Badge>)}
            {remaining > 0 && <Badge intent="neutral" size="sm">+{remaining} more</Badge>}
            {evts.length === 0 && <Text tone="secondary" size="sm">—</Text>}
          </HStack>
        );
      },
    },
    {
      key: 'status', label: 'Status', width: '10%',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'warning'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'last_triggered_at', label: 'Last Triggered', width: '13%',
      render: row => row.last_triggered_at
        ? <Text size="sm">{new Date(row.last_triggered_at).toLocaleDateString()}</Text>
        : <Text tone="secondary" size="sm">Never</Text>,
    },
    {
      key: 'actions', label: '', width: '18%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canEdit && (
            <Button intent="soft" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          )}
          {canTest && (
            <Button intent="ghost" size="sm" onClick={() => handleTest(row)}>Test</Button>
          )}
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.visit(route('core.api.webhooks.deliveries', row.id))}
          >
            Deliveries
          </Button>
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <style>{`
        .wh-event-label {
          display: flex;
          align-items: center;
          gap: var(--aeos-space-2, 0.5rem);
          cursor: pointer;
          user-select: none;
        }
        .wh-url-cell {
          max-width: 100%;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
          display: block;
        }
        .wh-secret-banner {
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: var(--aeos-space-3, 0.75rem) var(--aeos-space-4, 1rem);
        }
      `}</style>

      <IndexPageLayout
        title="Webhooks"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Webhooks' },
        ]}
        description="Receive real-time event notifications via HTTP endpoints."
        actions={
          canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={openCreate}>
              Create Webhook
            </Button>
          )
        }
        table={
          <VStack gap={3}>
            {flash?.webhook_secret && !dismissed && (
              <div className="wh-secret-banner">
                <VStack gap={2}>
                  <HStack gap={2} align="center" justify="space-between">
                    <Text size="sm" tone="secondary">
                      Webhook signing secret — copy it now. It will not be shown again.
                    </Text>
                    <Button intent="ghost" size="sm" onClick={() => setDismissed(true)}>
                      Dismiss
                    </Button>
                  </HStack>
                  <HStack gap={2} align="center">
                    <Box grow>
                      <Mono size="sm">{flash.webhook_secret}</Mono>
                    </Box>
                    <Button intent="soft" size="sm" leftIcon="clipboard" onClick={() => copySecret(flash.webhook_secret)}>
                      {copied ? 'Copied!' : 'Copy'}
                    </Button>
                  </HStack>
                </VStack>
              </div>
            )}

            <DataTable
              columns={columns}
              rows={webhooks?.data ?? []}
              empty="No webhooks configured."
            />
          </VStack>
        }
        pagination={
          webhooks?.last_page > 1 && (
            <Pagination
              page={webhooks.current_page}
              total={webhooks.last_page}
              onChange={page => router.get(route('core.api.webhooks.index'), { page }, {
                preserveState: true, preserveScroll: true, only: ['webhooks'],
              })}
            />
          )
        }
      />

      {/* Create / Edit Modal */}
      <Modal
        open={showModal}
        title={editTarget ? 'Edit Webhook' : 'Create Webhook'}
        onClose={resetModal}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={resetModal} disabled={form.processing}>Cancel</Button>
            <Button
              intent="primary"
              loading={form.processing}
              onClick={handleSubmit}
              disabled={!form.data.name.trim() || !form.data.url.trim() || form.data.events.length === 0}
            >
              {editTarget ? 'Save Changes' : 'Create Webhook'}
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleSubmit}>
          <VStack gap={4}>
            <Field label="Name" htmlFor="wh-name" error={form.errors.name} required>
              <Input
                id="wh-name"
                placeholder="e.g. Slack Notifications"
                value={form.data.name}
                onChange={e => form.setData('name', e.target.value)}
                error={!!form.errors.name}
              />
            </Field>

            <Field label="URL" htmlFor="wh-url" error={form.errors.url} required>
              <Input
                id="wh-url"
                type="url"
                placeholder="https://example.com/webhook"
                value={form.data.url}
                onChange={e => form.setData('url', e.target.value)}
                error={!!form.errors.url}
              />
            </Field>

            <Field label="Events" error={form.errors.events} required>
              <EventCheckboxes
                selected={form.data.events}
                onChange={events => form.setData('events', events)}
              />
            </Field>

            {editTarget && (
              <Field label="Active">
                <Toggle
                  label="Enabled"
                  checked={form.data.is_active}
                  onChange={e => form.setData('is_active', e.target.checked)}
                />
              </Field>
            )}
          </VStack>
        </form>
      </Modal>
    </>
  );
}

Webhooks.layout = page => (
  <App title="Webhooks">{page}</App>
);
