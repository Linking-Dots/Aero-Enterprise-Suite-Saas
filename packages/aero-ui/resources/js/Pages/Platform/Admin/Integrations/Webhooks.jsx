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
  Drawer,
  Tabs,
  Tab,
  Pagination,
  Card,
  CardBody,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function DeliveryLogRow({ log }) {
  const [expanded, setExpanded] = useState(false);
  const isSuccess = log.response_status >= 200 && log.response_status < 300;

  return (
    <VStack gap={2}>
      <HStack gap={3}>
        <Mono>{log.delivered_at}</Mono>
        <Badge intent={isSuccess ? 'success' : 'danger'}>{log.response_status}</Badge>
        <Text tone="secondary">{log.event_type}</Text>
        <Button intent="ghost" size="sm" onClick={() => setExpanded(v => !v)}>
          {expanded ? 'Hide' : 'Expand'}
        </Button>
      </HStack>
      {expanded && (
        <Card>
          <CardBody>
            <VStack gap={3}>
              <VStack gap={1}>
                <Text tone="secondary">Payload</Text>
                <pre className="overflow-auto max-h-48 text-xs font-mono aeos-glass rounded p-3">
                  {JSON.stringify(log.payload, null, 2)}
                </pre>
              </VStack>
              <VStack gap={1}>
                <Text tone="secondary">Response</Text>
                <pre className="overflow-auto max-h-32 text-xs font-mono aeos-glass rounded p-3">
                  {log.response_body ?? '—'}
                </pre>
              </VStack>
            </VStack>
          </CardBody>
        </Card>
      )}
    </VStack>
  );
}

const EVENT_OPTIONS = [
  { value: 'tenant.created',      label: 'tenant.created' },
  { value: 'tenant.deleted',      label: 'tenant.deleted' },
  { value: 'subscription.created', label: 'subscription.created' },
  { value: 'subscription.cancelled', label: 'subscription.cancelled' },
  { value: 'payment.succeeded',   label: 'payment.succeeded' },
  { value: 'payment.failed',      label: 'payment.failed' },
  { value: 'broadcast.published', label: 'broadcast.published' },
];

export default function Webhooks({ endpoints, filters }) {
  const toast     = useToast();
  const canManage = useHRMAC('outbound-webhooks.webhook-endpoints.create');
  const canTest   = useHRMAC('outbound-webhooks.webhook-endpoints.test');
  const canDelete = useHRMAC('outbound-webhooks.webhook-endpoints.delete');
  const canRotate = useHRMAC('outbound-webhooks.webhook-signing.rotate');

  const [drawerOpen,    setDrawerOpen]    = useState(false);
  const [editTarget,    setEditTarget]    = useState(null);
  const [deleteTarget,  setDeleteTarget]  = useState(null);
  const [logsTarget,    setLogsTarget]    = useState(null);
  const [deliveryLogs,  setDeliveryLogs]  = useState([]);
  const [loadingLogs,   setLoadingLogs]   = useState(false);
  const [eventInput,    setEventInput]    = useState(EVENT_OPTIONS[0].value);
  const [rotateTarget,  setRotateTarget]  = useState(null);
  const [newSecret,     setNewSecret]     = useState(null);

  const form = useForm({
    url:         '',
    description: '',
    events:      [],
    is_active:   true,
  });

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(endpoint) {
    setEditTarget(endpoint);
    form.setData({
      url:         endpoint.url,
      description: endpoint.description ?? '',
      events:      endpoint.events ?? [],
      is_active:   endpoint.is_active,
    });
    setDrawerOpen(true);
  }

  function addEvent() {
    if (!form.data.events.includes(eventInput)) {
      form.setData('events', [...form.data.events, eventInput]);
    }
  }

  function removeEvent(ev) {
    form.setData('events', form.data.events.filter(x => x !== ev));
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(route('platform.admin.integrations.webhooks.update', editTarget.id), {
        preserveState: true,
        onSuccess: () => {
          toast.success('Webhook updated.');
          setDrawerOpen(false);
        },
        onError: () => toast.error('Failed to update webhook.'),
      });
    } else {
      form.post(route('platform.admin.integrations.webhooks.store'), {
        preserveState: true,
        onSuccess: () => {
          toast.success('Webhook created.');
          setDrawerOpen(false);
          form.reset();
        },
        onError: () => toast.error('Failed to create webhook.'),
      });
    }
  }

  function handleTest(endpoint) {
    router.post(
      route('platform.admin.integrations.webhooks.test', endpoint.id),
      {},
      {
        preserveState: true,
        onSuccess: () => toast.success('Test payload delivered.'),
        onError: () => toast.error('Test delivery failed.'),
      }
    );
  }

  function confirmDelete() {
    if (!deleteTarget) return;
    router.delete(
      route('platform.admin.integrations.webhooks.destroy', deleteTarget.id),
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('Webhook deleted.');
          setDeleteTarget(null);
        },
        onError: () => toast.error('Delete failed.'),
      }
    );
  }

  function openLogs(endpoint) {
    setLogsTarget(endpoint);
    setLoadingLogs(true);
    router.get(
      route('platform.admin.integrations.webhooks.logs', endpoint.id),
      {},
      {
        preserveState: true,
        only: ['deliveryLogs'],
        onSuccess: (page) => {
          setDeliveryLogs(page.props.deliveryLogs ?? []);
          setLoadingLogs(false);
        },
        onError: () => setLoadingLogs(false),
      }
    );
  }

  function confirmRotate() {
    if (!rotateTarget) return;
    router.post(
      route('platform.admin.integrations.webhooks.rotate-secret', rotateTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: (page) => {
          setNewSecret(page.props.flash?.new_secret);
          setRotateTarget(null);
        },
        onError: () => toast.error('Rotate failed.'),
      }
    );
  }

  const columns = [
    {
      key: 'url',
      label: 'Endpoint URL',
      render: (row) => <Mono>{row.url}</Mono>,
    },
    {
      key: 'events',
      label: 'Events',
      render: (row) => (
        <HStack gap={1} wrap>
          {(row.events ?? []).slice(0, 3).map(e => (
            <Badge key={e} intent="neutral">{e}</Badge>
          ))}
          {(row.events ?? []).length > 3 && (
            <Badge intent="neutral">+{row.events.length - 3}</Badge>
          )}
        </HStack>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'failure_count',
      label: 'Failures',
      render: (row) => (
        <Badge intent={row.failure_count > 0 ? 'warning' : 'neutral'}>
          {row.failure_count}
        </Badge>
      ),
    },
    {
      key: 'last_triggered_at',
      label: 'Last Triggered',
      render: (row) => <Mono>{row.last_triggered_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canManage && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          {canTest && (
            <Button intent="soft" size="sm" onClick={() => handleTest(row)}>
              Test
            </Button>
          )}
          <Button intent="ghost" size="sm" onClick={() => openLogs(row)}>
            Logs
          </Button>
          {canRotate && (
            <Button intent="ghost" size="sm" onClick={() => setRotateTarget(row)}>
              Rotate Secret
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => setDeleteTarget(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Webhooks"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Integrations' },
        { label: 'Webhooks' },
      ]}
      description="Configure outbound webhook endpoints for platform events."
      actions={
        canManage && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            New Endpoint
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={endpoints?.data ?? []}
          empty="No webhook endpoints configured."
        />

        {(endpoints?.last_page ?? 1) > 1 && (
          <Pagination
            page={endpoints.current_page}
            total={endpoints.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.integrations.webhooks.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['endpoints'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create / Edit Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Webhook' : 'New Webhook Endpoint'}
      >
        <VStack gap={4}>
          <Field label="Endpoint URL" htmlFor="wh_url" error={form.errors.url} required>
            <Input
              id="wh_url"
              type="url"
              value={form.data.url}
              onChange={e => form.setData('url', e.target.value)}
              placeholder="https://example.com/webhook"
              leftIcon="link"
              error={form.errors.url}
            />
          </Field>

          <Field label="Description" htmlFor="wh_desc" error={form.errors.description}>
            <Input
              id="wh_desc"
              value={form.data.description}
              onChange={e => form.setData('description', e.target.value)}
              placeholder="Optional description"
              error={form.errors.description}
            />
          </Field>

          <Field label="Events" htmlFor="wh_event" error={form.errors.events}>
            <HStack gap={2}>
              <Select
                id="wh_event"
                value={eventInput}
                onChange={e => setEventInput(e.target.value)}
                options={EVENT_OPTIONS}
              />
              <Button intent="soft" size="sm" onClick={addEvent}>
                Add
              </Button>
            </HStack>
            {form.data.events.length > 0 && (
              <HStack gap={1} wrap>
                {form.data.events.map(ev => (
                  <HStack key={ev} gap={1}>
                    <Badge intent="neutral">{ev}</Badge>
                    <Button intent="ghost" size="sm" onClick={() => removeEvent(ev)}>
                      x
                    </Button>
                  </HStack>
                ))}
              </HStack>
            )}
          </Field>

          <Toggle
            label="Active"
            checked={form.data.is_active}
            onChange={e => form.setData('is_active', e.target.checked)}
          />

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleSave}
            >
              {editTarget ? 'Update' : 'Create'}
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Delete Confirm */}
      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Webhook"
        description={`Delete endpoint "${deleteTarget?.url}"? All delivery logs for this endpoint will also be removed.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmDelete}>
              Delete
            </Button>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Delivery Logs Modal */}
      <Modal
        open={!!logsTarget}
        onClose={() => { setLogsTarget(null); setDeliveryLogs([]); }}
        title={`Delivery Logs — ${logsTarget?.url}`}
        size="lg"
        footer={
          <Button intent="ghost" onClick={() => { setLogsTarget(null); setDeliveryLogs([]); }}>
            Close
          </Button>
        }
      >
        {loadingLogs ? (
          <Text tone="secondary">Loading logs…</Text>
        ) : deliveryLogs.length === 0 ? (
          <Text tone="secondary">No delivery logs yet.</Text>
        ) : (
          <VStack gap={3}>
            {deliveryLogs.map(log => (
              <DeliveryLogRow key={log.id} log={log} />
            ))}
          </VStack>
        )}
      </Modal>

      {/* Rotate Secret Confirm */}
      <Modal
        open={!!rotateTarget}
        onClose={() => setRotateTarget(null)}
        title="Rotate Signing Secret"
        description={`Rotate the signing secret for "${rotateTarget?.url}"? Update your integration to use the new secret immediately after rotation.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmRotate}>
              Rotate Secret
            </Button>
            <Button intent="ghost" onClick={() => setRotateTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* New Secret Display Modal */}
      <Modal
        open={!!newSecret}
        onClose={() => setNewSecret(null)}
        title="New Signing Secret"
        footer={
          <Button intent="primary" onClick={() => setNewSecret(null)}>
            I have saved the secret
          </Button>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            Copy the new signing secret below. It will not be shown again.
          </Text>
          <Card>
            <CardBody>
              <Mono>{newSecret}</Mono>
            </CardBody>
          </Card>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

Webhooks.layout = page => <App title="Webhooks">{page}</App>;
