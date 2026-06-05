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
  Card,
  CardBody,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ACTION_OPTIONS = [
  { value: 'retry',        label: 'Retry Payment' },
  { value: 'email',        label: 'Send Email' },
  { value: 'suspend',      label: 'Suspend Subscription' },
  { value: 'mark_unpaid',  label: 'Mark as Unpaid' },
];

const ACTION_INTENT = {
  retry:       'info',
  email:       'neutral',
  suspend:     'warning',
  mark_unpaid: 'danger',
};

export default function DunningRules({ rules, recoveryEmails }) {
  const toast      = useToast();
  const canManage  = useHRMAC('dunning.dunning-rules.manage');
  const canEmails  = useHRMAC('dunning.recovery-emails.manage');

  const [drawerOpen,   setDrawerOpen]   = useState(false);
  const [editTarget,   setEditTarget]   = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting,     setDeleting]     = useState(false);
  const [emailTarget,  setEmailTarget]  = useState(null);

  const form = useForm({
    name:              '',
    day_offset:        '',
    action:            'retry',
    email_template_id: '',
    is_active:         true,
    order_index:       0,
  });

  const emailForm = useForm({
    subject:     '',
    body:        '',
  });

  function openCreate() {
    setEditTarget(null);
    form.reset();
    form.setData('order_index', rules?.length ?? 0);
    setDrawerOpen(true);
  }

  function openEdit(rule) {
    setEditTarget(rule);
    form.setData({
      name:              rule.name,
      day_offset:        String(rule.day_offset),
      action:            rule.action,
      email_template_id: rule.email_template_id ? String(rule.email_template_id) : '',
      is_active:         rule.is_active,
      order_index:       rule.order_index,
    });
    setDrawerOpen(true);
  }

  function openEditEmail(template) {
    setEmailTarget(template);
    emailForm.setData({
      subject: template.subject ?? '',
      body:    template.body ?? '',
    });
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(route('platform.admin.dunning.rules.update', editTarget.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Rule updated.'); setDrawerOpen(false); },
        onError: () => toast.error('Failed to update rule.'),
      });
    } else {
      form.post(route('platform.admin.dunning.rules.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Rule created.'); setDrawerOpen(false); form.reset(); },
        onError: () => toast.error('Failed to create rule.'),
      });
    }
  }

  function confirmDelete() {
    if (!deleteTarget) return;
    setDeleting(true);
    router.delete(
      route('platform.admin.dunning.rules.destroy', deleteTarget.id),
      {
        preserveState: true,
        onSuccess: () => { toast.success('Rule deleted.'); setDeleteTarget(null); },
        onError: () => toast.error('Failed to delete rule.'),
        onFinish: () => setDeleting(false),
      }
    );
  }

  function handleEmailSave(e) {
    e.preventDefault();
    if (!emailTarget) return;
    emailForm.put(route('platform.admin.dunning.recovery-emails.update', emailTarget.id), {
      preserveState: true,
      onSuccess: () => { toast.success('Email template updated.'); setEmailTarget(null); },
      onError: () => toast.error('Failed to update template.'),
    });
  }

  function moveUp(rule) {
    if (rule.order_index === 0) return;
    router.put(
      route('platform.admin.dunning.rules.update', rule.id),
      { ...rule, order_index: rule.order_index - 1 },
      {
        preserveState: true,
        onSuccess: () => toast.success('Rule reordered.'),
        onError: () => toast.error('Failed to reorder rule.'),
      }
    );
  }

  function moveDown(rule, total) {
    if (rule.order_index >= total - 1) return;
    router.put(
      route('platform.admin.dunning.rules.update', rule.id),
      { ...rule, order_index: rule.order_index + 1 },
      {
        preserveState: true,
        onSuccess: () => toast.success('Rule reordered.'),
        onError: () => toast.error('Failed to reorder rule.'),
      }
    );
  }

  const sortedRules = [...(rules ?? [])].sort((a, b) => a.order_index - b.order_index);

  const ruleColumns = [
    {
      key: 'order',
      label: 'Order',
      render: (row) => <Mono>{row.order_index + 1}</Mono>,
    },
    {
      key: 'name',
      label: 'Name',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'day_offset',
      label: 'Trigger',
      render: (row) => <Mono>Day +{row.day_offset}</Mono>,
    },
    {
      key: 'action',
      label: 'Action',
      render: (row) => (
        <Badge intent={ACTION_INTENT[row.action] ?? 'neutral'}>
          {ACTION_OPTIONS.find(o => o.value === row.action)?.label ?? row.action}
        </Badge>
      ),
    },
    {
      key: 'is_active',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        canManage && (
          <HStack gap={1}>
            <Button
              intent="ghost"
              size="sm"
              onClick={() => moveUp(row)}
              disabled={row.order_index === 0}
              leftIcon="arrowUp"
            >
              Up
            </Button>
            <Button
              intent="ghost"
              size="sm"
              onClick={() => moveDown(row, sortedRules.length)}
              disabled={row.order_index >= sortedRules.length - 1}
              leftIcon="arrowDown"
            >
              Down
            </Button>
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
            <Button intent="danger" size="sm" onClick={() => setDeleteTarget(row)}>
              Delete
            </Button>
          </HStack>
        )
      ),
    },
  ];

  const emailColumns = [
    {
      key: 'name',
      label: 'Template',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'subject',
      label: 'Subject',
      render: (row) => <Text tone="secondary">{row.subject}</Text>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        canEmails && (
          <Button intent="ghost" size="sm" onClick={() => openEditEmail(row)}>
            Edit
          </Button>
        )
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Dunning Rules"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Dunning', href: route('platform.admin.dunning.index') },
        { label: 'Rules' },
      ]}
      description="Configure the retry schedule and notification templates for failed payment recovery."
      actions={
        canManage && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            Add Rule
          </Button>
        )
      }
    >
      <VStack gap={6}>
        {/* Rules list */}
        <VStack gap={3}>
          <Eyebrow>Retry Schedule</Eyebrow>
          <Alert
            intent="info"
            title="Rules are executed in order by day offset. Day 0 means immediately on failure."
          />
          <DataTable
            columns={ruleColumns}
            rows={sortedRules}
            empty="No dunning rules configured. Add rules to define the retry schedule."
          />
        </VStack>

        {/* Recovery email templates */}
        {(recoveryEmails ?? []).length > 0 && (
          <VStack gap={3}>
            <Eyebrow>Recovery Email Templates</Eyebrow>
            <DataTable
              columns={emailColumns}
              rows={recoveryEmails ?? []}
              empty="No email templates found."
            />
          </VStack>
        )}
      </VStack>

      {/* Create / Edit Rule Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Dunning Rule' : 'New Dunning Rule'}
      >
        <VStack gap={4}>
          <Field label="Rule Name" htmlFor="dr_name" error={form.errors.name} required>
            <Input
              id="dr_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="Day 3 Retry"
              error={form.errors.name}
            />
          </Field>

          <Field label="Day Offset" htmlFor="dr_day" error={form.errors.day_offset} required hint="Days after payment failure to trigger this rule. Use 0 for immediate.">
            <Input
              id="dr_day"
              type="number"
              value={form.data.day_offset}
              onChange={e => form.setData('day_offset', e.target.value)}
              placeholder="3"
              error={form.errors.day_offset}
            />
          </Field>

          <Field label="Action" htmlFor="dr_action" error={form.errors.action} required>
            <Select
              id="dr_action"
              value={form.data.action}
              onChange={e => form.setData('action', e.target.value)}
              options={ACTION_OPTIONS}
            />
          </Field>

          {form.data.action === 'email' && (
            <Field label="Email Template ID" htmlFor="dr_template" error={form.errors.email_template_id} hint="Leave blank to use the default recovery email.">
              <Input
                id="dr_template"
                type="number"
                value={form.data.email_template_id}
                onChange={e => form.setData('email_template_id', e.target.value)}
                placeholder="Optional"
                error={form.errors.email_template_id}
              />
            </Field>
          )}

          <Field label="Order Index" htmlFor="dr_order" error={form.errors.order_index} hint="Execution order among rules with the same day offset.">
            <Input
              id="dr_order"
              type="number"
              value={form.data.order_index}
              onChange={e => form.setData('order_index', Number(e.target.value))}
              placeholder="0"
              error={form.errors.order_index}
            />
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
              {editTarget ? 'Update Rule' : 'Create Rule'}
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Delete Rule Confirm Modal */}
      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Dunning Rule"
        description={`Delete rule "${deleteTarget?.name}" (Day +${deleteTarget?.day_offset})? This cannot be undone.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={deleting} disabled={deleting} onClick={confirmDelete}>
              Delete
            </Button>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Edit Recovery Email Modal */}
      <Modal
        open={!!emailTarget}
        onClose={() => { setEmailTarget(null); emailForm.reset(); }}
        title={`Edit Template — ${emailTarget?.name}`}
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={emailForm.processing}
              disabled={emailForm.processing}
              onClick={handleEmailSave}
            >
              Save Template
            </Button>
            <Button intent="ghost" onClick={() => { setEmailTarget(null); emailForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Subject" htmlFor="em_subject" error={emailForm.errors.subject} required>
            <Input
              id="em_subject"
              value={emailForm.data.subject}
              onChange={e => emailForm.setData('subject', e.target.value)}
              placeholder="Your payment failed — please update your billing info"
              error={emailForm.errors.subject}
            />
          </Field>

          <Field label="Body" htmlFor="em_body" error={emailForm.errors.body} required hint="Supports plain text. Use {tenant_name}, {amount}, {invoice_id} as placeholders.">
            <Input
              id="em_body"
              value={emailForm.data.body}
              onChange={e => emailForm.setData('body', e.target.value)}
              placeholder="Hi {tenant_name}, your payment of {amount} failed..."
              error={emailForm.errors.body}
            />
          </Field>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

DunningRules.layout = page => <App title="Dunning Rules">{page}</App>;
