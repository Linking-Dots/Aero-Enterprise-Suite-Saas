import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Field,
  Input,
  Select,
  Toggle,
  Modal,
  Alert,
  useToast,
  useHRMAC,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ACTION_INTENT   = { delete: 'danger', anonymize: 'warning' };
const ENTITY_OPTIONS  = [
  { value: 'audit_logs',    label: 'Audit Logs' },
  { value: 'users',         label: 'Users' },
  { value: 'employees',     label: 'Employees' },
  { value: 'payroll',       label: 'Payroll' },
  { value: 'leave_requests', label: 'Leave Requests' },
  { value: 'notifications', label: 'Notifications' },
];
const ACTION_OPTIONS  = [
  { value: 'delete',    label: 'Delete' },
  { value: 'anonymize', label: 'Anonymize' },
];

const EMPTY_FORM = { entity_type: 'audit_logs', retain_for_days: 90, action: 'delete', is_active: true };

export default function RetentionPoliciesIndex({ policies }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('core.retention_policies.policies.create');
  const canUpdate  = useHRMAC('core.retention_policies.policies.update');
  const canDelete  = useHRMAC('core.retention_policies.policies.delete');
  const canExecute = useHRMAC('core.retention_policies.policies.execute');

  const [showModal,   setShowModal]   = useState(false);
  const [editing,     setEditing]     = useState(null);
  const [submitting,  setSubmitting]  = useState(false);
  const [form,        setForm]        = useState(EMPTY_FORM);

  const policyList = policies?.data ?? [];

  const openCreate = () => {
    setForm(EMPTY_FORM);
    setEditing(null);
    setShowModal(true);
  };

  const openEdit = policy => {
    setForm({
      entity_type:    policy.entity_type,
      retain_for_days: policy.retain_for_days,
      action:         policy.action,
      is_active:      !!policy.is_active,
    });
    setEditing(policy);
    setShowModal(true);
  };

  const closeModal = () => { setShowModal(false); setEditing(null); };

  const handleSave = () => {
    setSubmitting(true);
    if (editing) {
      router.put(route('core.retention-policies.update', editing.id), form, {
        preserveState: true,
        onSuccess: () => { toast.success('Policy updated.'); closeModal(); },
        onError:   () => toast.error('Failed to update policy.'),
        onFinish:  () => setSubmitting(false),
      });
    } else {
      router.post(route('core.retention-policies.store'), form, {
        preserveState: true,
        onSuccess: () => { toast.success('Policy created.'); closeModal(); },
        onError:   () => toast.error('Failed to create policy.'),
        onFinish:  () => setSubmitting(false),
      });
    }
  };

  const handleDelete = id => {
    if (!confirm('Delete this retention policy?')) return;
    router.delete(route('core.retention-policies.destroy', id), {
      preserveState: true,
      onSuccess: () => toast.success('Policy deleted.'),
      onError:   () => toast.error('Failed to delete policy.'),
    });
  };

  const handleExecute = id => {
    if (!confirm('Execute this policy now? Matching records will be processed immediately.')) return;
    router.post(route('core.retention-policies.execute', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Policy executed.'),
      onError:   () => toast.error('Failed to execute policy.'),
    });
  };

  const columns = [
    {
      key: 'entity_type', label: 'Entity', width: '18%',
      render: row => <Text size="sm">{row.entity_type}</Text>,
    },
    {
      key: 'retain_for_days', label: 'Retain (days)', width: '14%',
      render: row => <Text size="sm">{row.retain_for_days}</Text>,
    },
    {
      key: 'action', label: 'Action', width: '12%',
      render: row => (
        <Badge intent={ACTION_INTENT[row.action] || 'neutral'}>{row.action}</Badge>
      ),
    },
    {
      key: 'is_active', label: 'Active', width: '10%',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Yes' : 'No'}</Badge>
      ),
    },
    {
      key: 'last_executed_at', label: 'Last Executed', width: '18%',
      render: row => (
        <Text size="sm" tone="secondary">
          {row.last_executed_at ? new Date(row.last_executed_at).toLocaleString() : '—'}
        </Text>
      ),
    },
    {
      key: 'actions', label: '', width: '28%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canExecute && row.is_active && (
            <Button intent="soft" size="sm" onClick={() => handleExecute(row.id)}>
              Execute Now
            </Button>
          )}
          {canUpdate && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row.id)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Retention Policies"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Retention Policies' },
      ]}
      description="Configure automatic data retention and purge rules."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            New Policy
          </Button>
        )
      }
      kpis={[
        <Stat key="total"  title="Policies"        value={policies?.total ?? 0}                                        icon="document" />,
        <Stat key="active" title="Active"           value={policyList.filter(p => p.is_active).length}                 icon="check"    iconTone="success" />,
        <Stat key="delete" title="Delete Actions"   value={policyList.filter(p => p.action === 'delete').length}       icon="trash"    iconTone="danger" />,
        <Stat key="anon"   title="Anonymize Actions" value={policyList.filter(p => p.action === 'anonymize').length}   icon="lock"     iconTone="amber" />,
      ]}
      table={
        <DataTable
          columns={columns}
          rows={policyList}
          empty="No retention policies configured."
        />
      }
      pagination={
        policies?.last_page > 1 && (
          <Pagination
            page={policies.current_page}
            total={policies.last_page}
            onChange={page => router.get(route('core.retention-policies.index'), { page }, {
              preserveState: true, preserveScroll: true, only: ['policies'],
            })}
          />
        )
      }
    >
      <Modal open={showModal} onClose={closeModal} title={editing ? 'Edit Policy' : 'New Retention Policy'} size="sm">
        <VStack gap={4}>
          <Field label="Entity Type" htmlFor="rp-entity">
            <Select
              id="rp-entity"
              value={form.entity_type}
              onChange={e => setForm(f => ({ ...f, entity_type: e.target.value }))}
              options={ENTITY_OPTIONS}
            />
          </Field>
          <Field label="Retain for (days)" htmlFor="rp-days" required>
            <Input
              id="rp-days"
              type="number"
              value={form.retain_for_days}
              onChange={e => setForm(f => ({ ...f, retain_for_days: parseInt(e.target.value, 10) || 0 }))}
              placeholder="90"
            />
          </Field>
          <Field label="Action" htmlFor="rp-action">
            <Select
              id="rp-action"
              value={form.action}
              onChange={e => setForm(f => ({ ...f, action: e.target.value }))}
              options={ACTION_OPTIONS}
            />
          </Field>
          {form.action === 'delete' && (
            <Alert intent="warning" title="Records matching this policy will be permanently deleted." />
          )}
          <Toggle
            label="Active"
            checked={form.is_active}
            onChange={v => setForm(f => ({ ...f, is_active: v }))}
          />
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={closeModal}>Cancel</Button>
            <Button intent="primary" loading={submitting} onClick={handleSave}>
              {editing ? 'Save Changes' : 'Create Policy'}
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

RetentionPoliciesIndex.layout = page => <App title="Retention Policies">{page}</App>;
