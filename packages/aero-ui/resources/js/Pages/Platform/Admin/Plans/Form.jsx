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
  Eyebrow,
  Card,
  CardBody,
  useToast,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function PlansForm({ plan, availableModules }) {
  const toast   = useToast();
  const isEdit  = !!plan;

  const form = useForm({
    name:                 plan?.name                 ?? '',
    slug:                 plan?.slug                 ?? '',
    status:               plan?.status               ?? 'draft',
    description:          plan?.description          ?? '',
    price_monthly:        plan?.price_monthly        ?? '',
    price_yearly:         plan?.price_yearly         ?? '',
    trial_days:           plan?.trial_days           ?? '',
    max_users:            plan?.max_users            ?? '',
    max_storage_gb:       plan?.max_storage_gb       ?? '',
    max_api_calls_per_mo: plan?.max_api_calls_per_mo ?? '',
    modules:              plan?.modules?.map(m => m.key ?? m) ?? [],
  });

  function handleSubmit(e) {
    e.preventDefault();
    if (isEdit) {
      form.put(route('platform.admin.plans.update', plan.id), {
        onSuccess: () => toast.success('Plan updated.'),
        onError:   () => toast.error('Failed to update plan.'),
      });
    } else {
      form.post(route('platform.admin.plans.store'), {
        onSuccess: () => toast.success('Plan created.'),
        onError:   () => toast.error('Failed to create plan.'),
      });
    }
  }

  function toggleModule(key) {
    const current = form.data.modules;
    form.setData(
      'modules',
      current.includes(key) ? current.filter(k => k !== key) : [...current, key]
    );
  }

  const statusOptions = [
    { value: 'draft',    label: 'Draft' },
    { value: 'active',   label: 'Active' },
    { value: 'archived', label: 'Archived' },
  ];

  return (
    <FormPageLayout
      title={isEdit ? `Edit Plan: ${plan.name}` : 'New Plan'}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.p1.dashboard') },
        { label: 'Plans', href: route('platform.admin.plans.index') },
        { label: isEdit ? plan.name : 'New' },
      ]}
      description={isEdit ? 'Update plan details, pricing, and permitted module limits. Module access also requires a ProductSubscription.' : 'Create a new subscription plan. Plans set quota limits and permitted modules; tenants purchase Products separately for module access.'}
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        {/* Basic Info */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Basic Info</Eyebrow>

              <Field label="Plan Name" htmlFor="name" error={form.errors.name} required>
                <Input
                  id="name"
                  value={form.data.name}
                  onChange={e => form.setData('name', e.target.value)}
                  placeholder="Pro Plan"
                  error={form.errors.name}
                />
              </Field>

              <Field label="Slug" htmlFor="slug" error={form.errors.slug} hint="URL-safe identifier. Auto-generated if blank.">
                <Input
                  id="slug"
                  value={form.data.slug}
                  onChange={e => form.setData('slug', e.target.value)}
                  placeholder="pro-plan"
                  error={form.errors.slug}
                />
              </Field>

              <Field label="Status" htmlFor="status" error={form.errors.status}>
                <Select
                  id="status"
                  value={form.data.status}
                  onChange={e => form.setData('status', e.target.value)}
                  options={statusOptions}
                />
              </Field>

              <Field label="Description" htmlFor="description" error={form.errors.description}>
                <Input
                  id="description"
                  value={form.data.description}
                  onChange={e => form.setData('description', e.target.value)}
                  placeholder="What's included in this plan…"
                  error={form.errors.description}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* Pricing */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Pricing</Eyebrow>

              <HStack gap={4}>
                <Field label="Monthly Price (USD)" htmlFor="price_monthly" error={form.errors.price_monthly}>
                  <Input
                    id="price_monthly"
                    type="number"
                    value={form.data.price_monthly}
                    onChange={e => form.setData('price_monthly', e.target.value)}
                    placeholder="49.00"
                    leftIcon="currencyDollar"
                    error={form.errors.price_monthly}
                  />
                </Field>

                <Field label="Yearly Price (USD)" htmlFor="price_yearly" error={form.errors.price_yearly}>
                  <Input
                    id="price_yearly"
                    type="number"
                    value={form.data.price_yearly}
                    onChange={e => form.setData('price_yearly', e.target.value)}
                    placeholder="490.00"
                    leftIcon="currencyDollar"
                    error={form.errors.price_yearly}
                  />
                </Field>
              </HStack>

              <Field
                label="Trial Days"
                htmlFor="trial_days"
                error={form.errors.trial_days}
                hint="Set 0 for no trial."
              >
                <Input
                  id="trial_days"
                  type="number"
                  value={form.data.trial_days}
                  onChange={e => form.setData('trial_days', e.target.value)}
                  placeholder="14"
                  error={form.errors.trial_days}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* Quota Limits */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Quota Limits</Eyebrow>

              <Field label="Max Users" htmlFor="max_users" error={form.errors.max_users} hint="Leave blank for unlimited.">
                <Input
                  id="max_users"
                  type="number"
                  value={form.data.max_users}
                  onChange={e => form.setData('max_users', e.target.value)}
                  placeholder="Unlimited"
                  error={form.errors.max_users}
                />
              </Field>

              <Field label="Max Storage (GB)" htmlFor="max_storage_gb" error={form.errors.max_storage_gb} hint="Leave blank for unlimited.">
                <Input
                  id="max_storage_gb"
                  type="number"
                  value={form.data.max_storage_gb}
                  onChange={e => form.setData('max_storage_gb', e.target.value)}
                  placeholder="Unlimited"
                  error={form.errors.max_storage_gb}
                />
              </Field>

              <Field label="Max API Calls / Month" htmlFor="max_api_calls_per_mo" error={form.errors.max_api_calls_per_mo} hint="Leave blank for unlimited.">
                <Input
                  id="max_api_calls_per_mo"
                  type="number"
                  value={form.data.max_api_calls_per_mo}
                  onChange={e => form.setData('max_api_calls_per_mo', e.target.value)}
                  placeholder="Unlimited"
                  error={form.errors.max_api_calls_per_mo}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* Permitted Module Limits */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Permitted Module Limits</Eyebrow>
              <Text tone="secondary">
                These modules are permitted (unlocked) under this plan tier. A tenant must also hold an active
                ProductSubscription for a module to be accessible — plan limits alone do not grant access.
              </Text>
              {(availableModules ?? []).length === 0 ? (
                <Text tone="secondary">No modules registered.</Text>
              ) : (
                <VStack gap={2}>
                  {availableModules.map(key => (
                    <Toggle
                      key={key}
                      label={key}
                      checked={form.data.modules.includes(key)}
                      onChange={() => toggleModule(key)}
                    />
                  ))}
                </VStack>
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
            {isEdit ? 'Update Plan' : 'Create Plan'}
          </Button>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('platform.admin.plans.index'))}
          >
            Cancel
          </Button>
        </HStack>

      </VStack>
    </FormPageLayout>
  );
}

PlansForm.layout = page => (
  <App title={page.props.plan ? `Edit Plan: ${page.props.plan.name}` : 'New Plan'}>{page}</App>
);
