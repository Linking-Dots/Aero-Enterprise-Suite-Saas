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
  Field,
  Input,
  Select,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ACTION_OPTIONS = [
  { value: 'warn',     label: 'Warn' },
  { value: 'throttle', label: 'Throttle' },
  { value: 'block',    label: 'Block' },
];

const ACTION_INTENT = { warn: 'warning', throttle: 'warning', block: 'danger' };

function SettingRow({ setting }) {
  const { errors } = usePage().props;
  const toast = useToast();
  const canEdit = useHRMAC('quota-management.quota-settings.edit');

  const [editing, setEditing] = useState(false);

  const form = useForm({
    resource:               setting.resource,
    default_limit:          setting.default_limit,
    warning_threshold_pct:  setting.warning_threshold_pct,
    hard_limit_pct:         setting.hard_limit_pct,
    action:                 setting.action,
  });

  function submit(e) {
    e.preventDefault();
    router.put(
      route('platform.admin.quotas.settings.update'),
      form.data,
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Settings updated for ${setting.resource}.`);
          setEditing(false);
        },
        onError: () => toast.error('Failed to update settings.'),
        onFinish: () => form.setData('resource', setting.resource),
      }
    );
  }

  if (!editing) {
    return (
      <Card>
        <CardBody>
          <HStack gap={4}>
            <VStack gap={1}>
              <Eyebrow>{setting.resource}</Eyebrow>
              <HStack gap={3} wrap>
                <Text tone="secondary">Default: <Mono>{setting.default_limit.toLocaleString()}</Mono></Text>
                <Text tone="secondary">Warn at: <Mono>{setting.warning_threshold_pct}%</Mono></Text>
                <Text tone="secondary">Hard limit: <Mono>{setting.hard_limit_pct}%</Mono></Text>
                <Badge intent={ACTION_INTENT[setting.action] ?? 'neutral'}>{setting.action}</Badge>
              </HStack>
            </VStack>
            {canEdit && (
              <Button intent="soft" size="sm" onClick={() => setEditing(true)}>
                Edit
              </Button>
            )}
          </HStack>
        </CardBody>
      </Card>
    );
  }

  return (
    <Card>
      <CardBody>
        <form onSubmit={submit}>
          <VStack gap={4}>
            <Eyebrow>{setting.resource}</Eyebrow>
            <HStack gap={3} wrap>
              <Field label="Default Limit" htmlFor={`limit-${setting.resource}`} error={errors.default_limit}>
                <Input
                  id={`limit-${setting.resource}`}
                  type="number"
                  value={String(form.data.default_limit)}
                  onChange={e => form.setData('default_limit', Number(e.target.value))}
                  error={!!errors.default_limit}
                />
              </Field>
              <Field label="Warn Threshold (%)" htmlFor={`warn-${setting.resource}`} error={errors.warning_threshold_pct}>
                <Input
                  id={`warn-${setting.resource}`}
                  type="number"
                  value={String(form.data.warning_threshold_pct)}
                  onChange={e => form.setData('warning_threshold_pct', Number(e.target.value))}
                  error={!!errors.warning_threshold_pct}
                />
              </Field>
              <Field label="Hard Limit (%)" htmlFor={`hard-${setting.resource}`} error={errors.hard_limit_pct}>
                <Input
                  id={`hard-${setting.resource}`}
                  type="number"
                  value={String(form.data.hard_limit_pct)}
                  onChange={e => form.setData('hard_limit_pct', Number(e.target.value))}
                  error={!!errors.hard_limit_pct}
                />
              </Field>
              <Field label="Action" htmlFor={`action-${setting.resource}`} error={errors.action}>
                <Select
                  value={form.data.action}
                  onChange={e => form.setData('action', e.target.value)}
                  options={ACTION_OPTIONS}
                />
              </Field>
            </HStack>
            <HStack gap={2}>
              <Button intent="primary" type="submit" loading={form.processing}>
                Save
              </Button>
              <Button intent="ghost" type="button" onClick={() => setEditing(false)}>
                Cancel
              </Button>
            </HStack>
          </VStack>
        </form>
      </CardBody>
    </Card>
  );
}

function NewResourceForm() {
  const { errors } = usePage().props;
  const toast = useToast();
  const [open, setOpen] = useState(false);

  const form = useForm({
    resource:               '',
    default_limit:          0,
    warning_threshold_pct:  80,
    hard_limit_pct:         100,
    action:                 'warn',
  });

  function submit(e) {
    e.preventDefault();
    router.put(
      route('platform.admin.quotas.settings.update'),
      form.data,
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Settings created for ${form.data.resource}.`);
          form.reset();
          setOpen(false);
        },
        onError: () => toast.error('Failed to create settings.'),
      }
    );
  }

  return (
    <>
      <Button intent="primary" onClick={() => setOpen(true)}>
        Add Resource
      </Button>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title="Add Enforcement Setting"
        description="Define quota enforcement rules for a new resource type."
        footer={
          <HStack gap={2}>
            <Button intent="primary" type="submit" form="new-resource-form" loading={form.processing}>
              Add Resource
            </Button>
            <Button intent="ghost" onClick={() => setOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form id="new-resource-form" onSubmit={submit}>
          <VStack gap={4}>
            <Field label="Resource Code" htmlFor="new-resource" error={errors.resource} required hint="e.g. storage_gb, api_calls, users">
              <Input
                id="new-resource"
                value={form.data.resource}
                onChange={e => form.setData('resource', e.target.value)}
                placeholder="resource_code"
                error={!!errors.resource}
              />
            </Field>
            <Field label="Default Limit" htmlFor="new-default-limit" error={errors.default_limit} required>
              <Input
                id="new-default-limit"
                type="number"
                value={String(form.data.default_limit)}
                onChange={e => form.setData('default_limit', Number(e.target.value))}
                error={!!errors.default_limit}
              />
            </Field>
            <Field label="Warn Threshold (%)" htmlFor="new-warn-pct" error={errors.warning_threshold_pct}>
              <Input
                id="new-warn-pct"
                type="number"
                value={String(form.data.warning_threshold_pct)}
                onChange={e => form.setData('warning_threshold_pct', Number(e.target.value))}
              />
            </Field>
            <Field label="Hard Limit (%)" htmlFor="new-hard-pct" error={errors.hard_limit_pct}>
              <Input
                id="new-hard-pct"
                type="number"
                value={String(form.data.hard_limit_pct)}
                onChange={e => form.setData('hard_limit_pct', Number(e.target.value))}
              />
            </Field>
            <Field label="Action" htmlFor="new-action" error={errors.action}>
              <Select
                value={form.data.action}
                onChange={e => form.setData('action', e.target.value)}
                options={ACTION_OPTIONS}
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

export default function QuotasSettings({ settings }) {
  const canEdit = useHRMAC('quota-management.quota-settings.edit');

  return (
    <IndexPageLayout
      title="Quota Enforcement Settings"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Quotas', href: route('platform.admin.quotas.index') },
        { label: 'Enforcement Settings' },
      ]}
      description="Configure default limits, warning thresholds, and enforcement actions per resource."
      actions={canEdit && <NewResourceForm />}
    >
      <VStack gap={4}>
        {(settings ?? []).length === 0 ? (
          <Card>
            <CardBody>
              <Text tone="secondary">No enforcement settings configured yet.</Text>
            </CardBody>
          </Card>
        ) : (
          (settings ?? []).map(s => <SettingRow key={s.id} setting={s} />)
        )}
      </VStack>
    </IndexPageLayout>
  );
}

QuotasSettings.layout = page => <App title="Quota Settings">{page}</App>;
