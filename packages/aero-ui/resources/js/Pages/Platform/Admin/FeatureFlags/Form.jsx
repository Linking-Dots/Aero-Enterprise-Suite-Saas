import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  FormPageLayout,
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
  Card,
  CardBody,
  Alert,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const RULE_OPERATORS = [
  { value: 'eq',       label: 'equals' },
  { value: 'neq',      label: 'not equals' },
  { value: 'contains', label: 'contains' },
  { value: 'in',       label: 'in list' },
];

const RULE_ATTRIBUTES = [
  { value: 'tenant.plan_id',    label: 'Tenant Plan ID' },
  { value: 'tenant.status',     label: 'Tenant Status' },
  { value: 'tenant.country',    label: 'Tenant Country' },
  { value: 'user.email',        label: 'User Email' },
  { value: 'user.role',         label: 'User Role' },
];

function RuleRow({ rule, index, onChange, onRemove }) {
  return (
    <Card>
      <CardBody>
        <HStack gap={3}>
          <Select
            value={rule.attribute}
            onChange={e => onChange(index, 'attribute', e.target.value)}
            options={RULE_ATTRIBUTES}
          />
          <Select
            value={rule.operator}
            onChange={e => onChange(index, 'operator', e.target.value)}
            options={RULE_OPERATORS}
          />
          <Input
            value={rule.value}
            onChange={e => onChange(index, 'value', e.target.value)}
            placeholder="value"
          />
          <Button intent="danger" size="sm" onClick={() => onRemove(index)}>
            Remove
          </Button>
        </HStack>
      </CardBody>
    </Card>
  );
}

export default function FeatureFlagForm({ flag }) {
  const toast  = useToast();
  const isEdit = !!flag;

  const form = useForm({
    name:        flag?.name        ?? '',
    code:        flag?.code        ?? '',
    description: flag?.description ?? '',
    is_active:   flag?.is_active   ?? false,
    rollout_pct: flag?.rollout_pct ?? 100,
    rules:       flag?.rules       ?? [],
  });

  function addRule() {
    form.setData('rules', [
      ...form.data.rules,
      { attribute: 'tenant.plan_id', operator: 'eq', value: '' },
    ]);
  }

  function updateRule(index, field, value) {
    const updated = form.data.rules.map((r, i) =>
      i === index ? { ...r, [field]: value } : r
    );
    form.setData('rules', updated);
  }

  function removeRule(index) {
    form.setData('rules', form.data.rules.filter((_, i) => i !== index));
  }

  function handleSubmit(e) {
    e.preventDefault();
    if (isEdit) {
      form.put(route('platform.admin.feature-flags.update', flag.id), {
        onSuccess: () => toast.success('Flag updated.'),
        onError:   () => toast.error('Failed to update flag.'),
      });
    } else {
      form.post(route('platform.admin.feature-flags.store'), {
        onSuccess: () => toast.success('Flag created.'),
        onError:   () => toast.error('Failed to create flag.'),
      });
    }
  }

  function autoCode(name) {
    if (!isEdit) {
      form.setData({
        ...form.data,
        name,
        code: name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, ''),
      });
    } else {
      form.setData('name', name);
    }
  }

  return (
    <FormPageLayout
      title={isEdit ? `Edit Flag: ${flag.name}` : 'New Feature Flag'}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Feature Flags', href: route('platform.admin.feature-flags.index') },
        { label: isEdit ? flag.name : 'New' },
      ]}
      description={isEdit ? 'Update flag settings and targeting rules.' : 'Create a new feature flag with rollout controls.'}
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        {/* Basics */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Basic Info</Eyebrow>

              <Field label="Flag Name" htmlFor="ff_name" error={form.errors.name} required>
                <Input
                  id="ff_name"
                  value={form.data.name}
                  onChange={e => autoCode(e.target.value)}
                  placeholder="Dark Mode Beta"
                  error={form.errors.name}
                />
              </Field>

              <Field label="Flag Key" htmlFor="ff_code" error={form.errors.code} hint="Unique identifier used in code. Auto-generated from name." required>
                <Input
                  id="ff_code"
                  value={form.data.code}
                  onChange={e => form.setData('code', e.target.value)}
                  placeholder="dark_mode_beta"
                  error={form.errors.code}
                />
              </Field>

              <Field label="Description" htmlFor="ff_desc" error={form.errors.description}>
                <Input
                  id="ff_desc"
                  value={form.data.description}
                  onChange={e => form.setData('description', e.target.value)}
                  placeholder="Enables the dark mode UI for selected tenants."
                  error={form.errors.description}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* Rollout */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Rollout</Eyebrow>

              <Toggle
                label="Enabled by default"
                checked={form.data.is_active}
                onChange={e => form.setData('is_active', e.target.checked)}
              />

              <Field
                label="Rollout Percentage"
                htmlFor="ff_pct"
                error={form.errors.rollout_pct}
                hint="0 = no one, 100 = everyone who passes targeting rules."
              >
                <HStack gap={3}>
                  <Input
                    id="ff_pct"
                    type="number"
                    value={String(form.data.rollout_pct)}
                    onChange={e => form.setData('rollout_pct', Number(e.target.value))}
                    placeholder="100"
                    error={form.errors.rollout_pct}
                  />
                  <Text tone="secondary">%</Text>
                </HStack>
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* Targeting Rules */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={3}>
                <Eyebrow>Targeting Rules</Eyebrow>
                <Text tone="secondary">All rules must match (AND logic).</Text>
              </HStack>

              {form.data.rules.length === 0 && (
                <Text tone="secondary">No rules — flag applies to all tenants/users at the rollout %.</Text>
              )}

              {form.data.rules.map((rule, i) => (
                <RuleRow
                  key={i}
                  rule={rule}
                  index={i}
                  onChange={updateRule}
                  onRemove={removeRule}
                />
              ))}

              <Button intent="soft" size="sm" leftIcon="plus" onClick={addRule}>
                Add Rule
              </Button>

              {form.errors.rules && (
                <Alert intent="danger" title={form.errors.rules} />
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Actions */}
        <HStack gap={3}>
          <Button
            type="submit"
            intent="primary"
            loading={form.processing}
            disabled={form.processing}
          >
            {isEdit ? 'Update Flag' : 'Create Flag'}
          </Button>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('platform.admin.feature-flags.index'))}
          >
            Cancel
          </Button>
        </HStack>

      </VStack>
    </FormPageLayout>
  );
}

FeatureFlagForm.layout = page => (
  <App title={page.props.flag ? `Edit Flag: ${page.props.flag.name}` : 'New Feature Flag'}>{page}</App>
);
