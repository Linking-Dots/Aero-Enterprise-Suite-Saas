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
  Modal,
  Alert,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const COMPONENT_STATUS_OPTIONS = [
  { value: 'operational',    label: 'Operational' },
  { value: 'degraded',       label: 'Degraded Performance' },
  { value: 'partial_outage', label: 'Partial Outage' },
  { value: 'major_outage',   label: 'Major Outage' },
];

const COMPONENT_STATUS_INTENT = {
  operational:    'success',
  degraded:       'warning',
  partial_outage: 'amber',
  major_outage:   'danger',
};

const INCIDENT_STATUS_INTENT = {
  investigating: 'danger',
  identified:    'warning',
  monitoring:    'amber',
  resolved:      'success',
};

export default function StatusIndex({ components, activeIncidents, config }) {
  const toast         = useToast();
  const canManage     = useHRMAC('status-incidents.service-components.manage');
  const canSetStatus  = useHRMAC('status-incidents.service-components.set-status');
  const canConfigure  = useHRMAC('status-incidents.status-page.configure');

  const [setStatusTarget, setSetStatusTarget]   = useState(null);
  const [newStatus,       setNewStatus]         = useState('operational');
  const [settingStatus,   setSettingStatus]     = useState(false);
  const [showAdd,         setShowAdd]           = useState(false);

  const addForm = useForm({ name: '', description: '' });

  const [configForm, setConfigForm] = useState({
    page_title:       config?.page_title       ?? '',
    support_url:      config?.support_url      ?? '',
    subscriber_email: config?.subscriber_email ?? '',
  });
  const [savingConfig, setSavingConfig] = useState(false);

  function openSetStatus(component) {
    setSetStatusTarget(component);
    setNewStatus(component.status);
  }

  function handleSetStatus() {
    if (!setStatusTarget) return;
    setSettingStatus(true);
    router.post(
      route('platform.admin.status.components.set-status', setStatusTarget.id),
      { status: newStatus },
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`${setStatusTarget.name} set to ${newStatus}.`);
          setSetStatusTarget(null);
        },
        onError:  () => toast.error('Status update failed.'),
        onFinish: () => setSettingStatus(false),
      }
    );
  }

  function handleAddComponent(e) {
    e.preventDefault();
    addForm.post(route('platform.admin.status.components.store'), {
      onSuccess: () => {
        setShowAdd(false);
        addForm.reset();
        toast.success('Component added.');
      },
      onError: () => toast.error('Failed to add component.'),
    });
  }

  function saveConfig() {
    setSavingConfig(true);
    router.post(
      route('platform.admin.status.configure'),
      configForm,
      {
        preserveState: true,
        onSuccess: () => toast.success('Status page settings saved.'),
        onError:   () => toast.error('Save failed.'),
        onFinish:  () => setSavingConfig(false),
      }
    );
  }

  const componentColumns = [
    {
      key: 'name',
      label: 'Component',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'description',
      label: 'Description',
      render: (row) => <Text tone="secondary">{row.description ?? '—'}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={COMPONENT_STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status?.replace('_', ' ')}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canSetStatus ? (
          <Button intent="soft" size="sm" onClick={() => openSetStatus(row)}>
            Set Status
          </Button>
        ) : null,
    },
  ];

  const hasActiveIncidents = (activeIncidents ?? []).length > 0;

  return (
    <IndexPageLayout
      title="Status Page"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Status & Incidents' },
        { label: 'Status Page' },
      ]}
      description="Configure the public status page and manage service component statuses."
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() => router.get(route('platform.admin.status.incidents.index'))}
          >
            View Incidents
          </Button>
          {canManage && (
            <Button intent="primary" leftIcon="plus" onClick={() => setShowAdd(true)}>
              Add Component
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={5}>
        {/* Active Incidents Banner */}
        {hasActiveIncidents && (
          <Alert intent="danger" title={`${activeIncidents.length} Active Incident${activeIncidents.length > 1 ? 's' : ''}`}>
            <VStack gap={2}>
              {activeIncidents.map(inc => (
                <HStack key={inc.id} gap={3}>
                  <Badge intent={INCIDENT_STATUS_INTENT[inc.status] ?? 'neutral'}>
                    {inc.status}
                  </Badge>
                  <Text>{inc.title}</Text>
                  <Badge intent={inc.impact === 'critical' ? 'danger' : inc.impact === 'major' ? 'warning' : 'neutral'}>
                    {inc.impact}
                  </Badge>
                  <Button
                    intent="ghost"
                    size="sm"
                    onClick={() => router.get(route('platform.admin.status.incidents.show', inc.id))}
                  >
                    View
                  </Button>
                </HStack>
              ))}
            </VStack>
          </Alert>
        )}

        {/* Service Components */}
        <VStack gap={3}>
          <Eyebrow>Service Components</Eyebrow>
          <DataTable
            columns={componentColumns}
            rows={components ?? []}
            empty="No service components configured."
          />
        </VStack>

        {/* Status Page Configuration */}
        {canConfigure && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <HStack gap={2}>
                  <Eyebrow>Page Settings</Eyebrow>
                </HStack>
                <HStack gap={4} wrap>
                  <Field label="Page Title" htmlFor="page_title">
                    <Input
                      id="page_title"
                      value={configForm.page_title}
                      onChange={e => setConfigForm({ ...configForm, page_title: e.target.value })}
                      placeholder="AEOS Status"
                    />
                  </Field>
                  <Field label="Support URL" htmlFor="support_url">
                    <Input
                      id="support_url"
                      type="url"
                      value={configForm.support_url}
                      onChange={e => setConfigForm({ ...configForm, support_url: e.target.value })}
                      placeholder="https://support.example.com"
                    />
                  </Field>
                  <Field label="Subscriber Notification Email" htmlFor="subscriber_email">
                    <Input
                      id="subscriber_email"
                      type="email"
                      value={configForm.subscriber_email}
                      onChange={e => setConfigForm({ ...configForm, subscriber_email: e.target.value })}
                      placeholder="status@example.com"
                    />
                  </Field>
                </HStack>
                <HStack gap={2}>
                  <Button
                    intent="primary"
                    loading={savingConfig}
                    disabled={savingConfig}
                    onClick={saveConfig}
                  >
                    Save Settings
                  </Button>
                </HStack>
              </VStack>
            </CardBody>
          </Card>
        )}
      </VStack>

      {/* Add Component Modal */}
      <Modal
        open={showAdd}
        onClose={() => { setShowAdd(false); addForm.reset(); }}
        title="Add Service Component"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={addForm.processing}
              disabled={addForm.processing}
              onClick={handleAddComponent}
            >
              Add Component
            </Button>
            <Button intent="ghost" onClick={() => { setShowAdd(false); addForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Name" htmlFor="comp_name" error={addForm.errors.name} required>
            <Input
              id="comp_name"
              value={addForm.data.name}
              onChange={e => addForm.setData('name', e.target.value)}
              placeholder="API Gateway"
              error={addForm.errors.name}
            />
          </Field>
          <Field label="Description" htmlFor="comp_desc" error={addForm.errors.description}>
            <Input
              id="comp_desc"
              value={addForm.data.description}
              onChange={e => addForm.setData('description', e.target.value)}
              placeholder="Handles all tenant API traffic"
              error={addForm.errors.description}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Set Status Modal */}
      <Modal
        open={!!setStatusTarget}
        onClose={() => setSetStatusTarget(null)}
        title={`Set Status: ${setStatusTarget?.name}`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={settingStatus}
              disabled={settingStatus}
              onClick={handleSetStatus}
            >
              Update Status
            </Button>
            <Button intent="ghost" onClick={() => setSetStatusTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field label="New Status" htmlFor="new_status">
          <Select
            id="new_status"
            value={newStatus}
            onChange={e => setNewStatus(e.target.value)}
            options={COMPONENT_STATUS_OPTIONS}
          />
        </Field>
      </Modal>
    </IndexPageLayout>
  );
}

StatusIndex.layout = page => <App title="Status Page">{page}</App>;
