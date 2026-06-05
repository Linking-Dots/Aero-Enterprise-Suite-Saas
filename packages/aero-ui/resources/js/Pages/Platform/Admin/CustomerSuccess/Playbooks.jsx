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
  Card,
  CardBody,
  Field,
  Input,
  Select,
  Modal,
  Drawer,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TRIGGER_OPTIONS = [
  { value: 'health_score_drop', label: 'Health Score Drop' },
  { value: 'churn_risk_high',   label: 'Churn Risk: High' },
  { value: 'no_login_30d',      label: 'No Login 30 Days' },
  { value: 'trial_expiring',    label: 'Trial Expiring' },
  { value: 'manual',            label: 'Manual' },
];

function stepStatusBadge(status) {
  if (status === 'completed') return 'success';
  if (status === 'running')   return 'warning';
  if (status === 'failed')    return 'danger';
  return 'neutral';
}

export default function Playbooks({ playbooks, runHistory }) {
  const toast = useToast();
  const canCreate  = useHRMAC('customer-success.success-playbooks.create');
  const canUpdate  = useHRMAC('customer-success.success-playbooks.update');
  const canExecute = useHRMAC('customer-success.success-playbooks.execute');

  const [drawerOpen,     setDrawerOpen]     = useState(false);
  const [editTarget,     setEditTarget]     = useState(null);
  const [executeTarget,  setExecuteTarget]  = useState(null);
  const [historyTarget,  setHistoryTarget]  = useState(null);

  const form = useForm({
    name:        '',
    description: '',
    trigger:     'manual',
    steps:       [],
  });

  const [stepInput, setStepInput] = useState('');

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(row) {
    setEditTarget(row);
    form.setData({
      name:        row.name,
      description: row.description ?? '',
      trigger:     row.trigger,
      steps:       row.steps ?? [],
    });
    setDrawerOpen(true);
  }

  function addStep() {
    const s = stepInput.trim();
    if (s && !form.data.steps.includes(s)) {
      form.setData('steps', [...form.data.steps, s]);
      setStepInput('');
    }
  }

  function removeStep(idx) {
    form.setData('steps', form.data.steps.filter((_, i) => i !== idx));
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(route('platform.admin.customer-success.playbooks.update', editTarget.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Playbook updated.'); setDrawerOpen(false); },
        onError:   () => toast.error('Save failed.'),
      });
    } else {
      form.post(route('platform.admin.customer-success.playbooks.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Playbook created.'); setDrawerOpen(false); form.reset(); },
        onError:   () => toast.error('Create failed.'),
      });
    }
  }

  function confirmExecute() {
    if (!executeTarget) return;
    router.post(
      route('platform.admin.customer-success.playbooks.execute', executeTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Playbook execution started.'); setExecuteTarget(null); },
        onError:   () => toast.error('Execution failed.'),
      }
    );
  }

  const playbookColumns = [
    {
      key: 'name',
      label: 'Playbook',
      render: r => <Text>{r.name}</Text>,
    },
    {
      key: 'trigger',
      label: 'Trigger',
      render: r => (
        <Badge intent="neutral">
          {TRIGGER_OPTIONS.find(o => o.value === r.trigger)?.label ?? r.trigger}
        </Badge>
      ),
    },
    {
      key: 'steps',
      label: 'Steps',
      render: r => <Text tone="secondary">{(r.steps ?? []).length}</Text>,
    },
    {
      key: 'last_run',
      label: 'Last Run',
      render: r => <Mono>{r.last_run_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          {canUpdate && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(r)}>
              Edit
            </Button>
          )}
          {canExecute && (
            <Button intent="soft" size="sm" onClick={() => setExecuteTarget(r)}>
              Execute
            </Button>
          )}
          <Button intent="ghost" size="sm" onClick={() => setHistoryTarget(r)}>
            History
          </Button>
        </HStack>
      ),
    },
  ];

  const historyRows = (runHistory ?? []).filter(
    h => historyTarget && h.playbook_id === historyTarget.id
  );

  const historyColumns = [
    {
      key: 'run_at',
      label: 'Run At',
      render: r => <Mono>{r.started_at}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: r => <Badge intent={stepStatusBadge(r.status)}>{r.status}</Badge>,
    },
    {
      key: 'steps_done',
      label: 'Progress',
      render: r => (
        <Text tone="secondary">{r.steps_completed} / {r.steps_total}</Text>
      ),
    },
    {
      key: 'triggered_by',
      label: 'Triggered By',
      render: r => <Text tone="secondary">{r.triggered_by ?? 'system'}</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="Success Playbooks"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Customer Success' },
        { label: 'Playbooks' },
      ]}
      description="Automate customer success workflows with trigger-based playbooks."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            New Playbook
          </Button>
        )
      }
    >
      <DataTable
        columns={playbookColumns}
        rows={playbooks?.data ?? []}
        empty="No playbooks configured."
      />

      {/* Create / Edit Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Playbook' : 'New Playbook'}
      >
        <VStack gap={4}>
          <Field label="Name" htmlFor="pb_name" error={form.errors.name} required>
            <Input
              id="pb_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="e.g. 90-Day Onboarding"
              error={form.errors.name}
            />
          </Field>

          <Field label="Description" htmlFor="pb_desc" error={form.errors.description}>
            <Input
              id="pb_desc"
              value={form.data.description}
              onChange={e => form.setData('description', e.target.value)}
              placeholder="Optional description"
              error={form.errors.description}
            />
          </Field>

          <Field label="Trigger" htmlFor="pb_trigger" error={form.errors.trigger} required>
            <Select
              id="pb_trigger"
              value={form.data.trigger}
              onChange={e => form.setData('trigger', e.target.value)}
              options={TRIGGER_OPTIONS}
            />
          </Field>

          <Field label="Steps" htmlFor="pb_step" error={form.errors.steps}>
            <HStack gap={2}>
              <Input
                id="pb_step"
                value={stepInput}
                onChange={e => setStepInput(e.target.value)}
                placeholder="Step description"
              />
              <Button intent="soft" size="sm" type="button" onClick={addStep}>
                Add
              </Button>
            </HStack>
            {form.data.steps.length > 0 && (
              <VStack gap={1}>
                {form.data.steps.map((step, idx) => (
                  <HStack key={idx} gap={2}>
                    <Text tone="secondary">{idx + 1}. {step}</Text>
                    <Button intent="ghost" size="sm" type="button" onClick={() => removeStep(idx)}>
                      Remove
                    </Button>
                  </HStack>
                ))}
              </VStack>
            )}
          </Field>

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleSave}
            >
              {editTarget ? 'Update' : 'Create'}
            </Button>
            <Button intent="ghost" type="button" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Execute Confirm Modal */}
      <Modal
        open={!!executeTarget}
        onClose={() => setExecuteTarget(null)}
        title={`Execute Playbook — ${executeTarget?.name}`}
        description="This will immediately trigger the playbook for all matching tenants. Continue?"
        footer={
          <HStack gap={2}>
            <Button intent="primary" onClick={confirmExecute}>
              Execute Now
            </Button>
            <Button intent="ghost" onClick={() => setExecuteTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Run History Modal */}
      <Modal
        open={!!historyTarget}
        onClose={() => setHistoryTarget(null)}
        title={`Run History — ${historyTarget?.name}`}
        size="lg"
        footer={
          <Button intent="ghost" onClick={() => setHistoryTarget(null)}>
            Close
          </Button>
        }
      >
        <DataTable
          columns={historyColumns}
          rows={historyRows}
          empty="No run history yet."
        />
      </Modal>
    </IndexPageLayout>
  );
}

Playbooks.layout = page => <App title="Success Playbooks">{page}</App>;
