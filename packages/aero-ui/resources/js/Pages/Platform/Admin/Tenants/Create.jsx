import { router, useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  Select,
  Toggle,
  Button,
  HStack,
  VStack,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function TenantsCreate({ plans, products }) {
  const toast = useToast();

  const form = useForm({
    name:         '',
    email:        '',
    plan_id:      '',
    product_id:   '',
    timezone:     'UTC',
    byoc_enabled: false,
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.post(route('platform.admin.tenants.store'), {
      onSuccess: () => {
        toast.success('Tenant created and provisioning started.');
      },
      onError: () => {
        const first = Object.values(form.errors)[0];
        toast.error(first ?? 'Failed to create tenant.');
      },
    });
  }

  const planOptions = [
    { value: '', label: 'Select a plan...' },
    ...(plans ?? []).map(p => ({
      value: String(p.id),
      label: `${p.name}${p.price_monthly != null ? ` — $${p.price_monthly}/mo` : ''}`,
    })),
  ];

  const productOptions = [
    { value: '', label: 'No initial product (add later)' },
    ...(products ?? []).map(p => ({
      value: String(p.id),
      label: `${p.name}${p.price_monthly != null ? ` — $${p.price_monthly}/mo` : ''}`,
    })),
  ];

  return (
    <FormPageLayout
      title="Create Tenant"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenants', href: route('platform.admin.tenants.index') },
        { label: 'Create' },
      ]}
      description="Provision a new tenant account."
      onSubmit={handleSubmit}
    >
      <VStack gap={4} className="pa1-form-narrow">
        <Field
          label="Tenant Name"
          htmlFor="name"
          error={form.errors.name}
          required
        >
          <Input
            id="name"
            value={form.data.name}
            onChange={e => form.setData('name', e.target.value)}
            placeholder="Acme Corp"
            error={form.errors.name}
          />
        </Field>

        <Field
          label="Admin Email"
          htmlFor="email"
          error={form.errors.email}
          required
        >
          <Input
            id="email"
            type="email"
            value={form.data.email}
            onChange={e => form.setData('email', e.target.value)}
            placeholder="admin@acme.com"
            error={form.errors.email}
          />
        </Field>

        <Field
          label="Plan"
          htmlFor="plan_id"
          error={form.errors.plan_id}
          hint="Sets quota limits and tier for this tenant."
          required
        >
          <Select
            id="plan_id"
            value={form.data.plan_id}
            onChange={e => form.setData('plan_id', e.target.value)}
            options={planOptions}
          />
        </Field>

        <Field
          label="Initial Product"
          htmlFor="product_id"
          error={form.errors.product_id}
          hint="The product (HRM, Finance, CRM, etc.) this tenant subscribes to. A ProductSubscription will be created on provisioning."
        >
          <Select
            id="product_id"
            value={form.data.product_id}
            onChange={e => form.setData('product_id', e.target.value)}
            options={productOptions}
          />
        </Field>

        <Field
          label="Timezone"
          htmlFor="timezone"
          error={form.errors.timezone}
          hint="IANA timezone identifier, e.g. America/New_York"
        >
          <Input
            id="timezone"
            value={form.data.timezone}
            onChange={e => form.setData('timezone', e.target.value)}
            placeholder="UTC"
            error={form.errors.timezone}
          />
        </Field>

        <Field label="BYOC Database" htmlFor="byoc_enabled">
          <Toggle
            label="Enable bring-your-own-cloud database"
            checked={form.data.byoc_enabled}
            onChange={e => form.setData('byoc_enabled', e.target.checked)}
          />
        </Field>

        <HStack gap={3}>
          <Button
            type="submit"
            intent="primary"
            loading={form.processing}
            disabled={form.processing}
          >
            Create Tenant
          </Button>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('platform.admin.tenants.index'))}
          >
            Cancel
          </Button>
        </HStack>
      </VStack>
    </FormPageLayout>
  );
}

TenantsCreate.layout = page => <App title="Create Tenant">{page}</App>;
