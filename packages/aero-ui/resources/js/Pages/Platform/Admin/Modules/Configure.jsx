import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  Toggle,
  Button,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Badge,
  Mono,
  Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function ModuleConfigure({ module }) {
  const toast      = useToast();
  const canConfigure = useHRMAC('module-management.module-list.configure');
  const canPrice   = useHRMAC('module-management.module-pricing.edit');
  const canToggle  = useHRMAC('module-management.module-list.toggle-active');

  const [jsonError, setJsonError] = useState(null);

  const form = useForm({
    price_monthly: module?.price_monthly ?? 0,
    price_annual:  module?.price_annual  ?? 0,
    is_active:     module?.is_active     ?? true,
    config:        JSON.stringify(module?.config ?? {}, null, 2),
  });

  function handleSubmit(e) {
    e.preventDefault();
    setJsonError(null);

    let parsedConfig;
    try {
      parsedConfig = JSON.parse(form.data.config);
    } catch {
      setJsonError('Invalid JSON — please fix the config before saving.');
      return;
    }

    // Save pricing if permitted
    if (canPrice) {
      router.put(
        route('platform.admin.modules.pricing', module.id),
        {
          price_monthly: parseFloat(form.data.price_monthly) || 0,
          price_annual:  parseFloat(form.data.price_annual)  || 0,
        },
        { preserveState: true, preserveScroll: true }
      );
    }

    // Save config if permitted
    if (canConfigure) {
      router.put(
        route('platform.admin.modules.configure', module.id),
        { config: parsedConfig },
        {
          preserveState: true,
          preserveScroll: true,
          onSuccess: () => toast.success('Module configuration saved.'),
          onError:   () => toast.error('Failed to save configuration.'),
        }
      );
    }
  }

  function handleToggle() {
    router.post(
      route('platform.admin.modules.toggle', module.id),
      {},
      {
        onSuccess: () => toast.success(`Module ${form.data.is_active ? 'disabled' : 'enabled'}.`),
        onError:   () => toast.error('Failed to toggle module.'),
      }
    );
  }

  return (
    <FormPageLayout
      title={`Configure: ${module?.name}`}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Modules', href: route('platform.admin.modules.index') },
        { label: module?.name ?? 'Configure' },
      ]}
      description="Update module pricing, active status, and advanced configuration."
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={3} align="center">
                <Eyebrow>Module</Eyebrow>
                <Badge intent={module?.is_active ? 'success' : 'neutral'}>
                  {module?.is_active ? 'active' : 'inactive'}
                </Badge>
              </HStack>

              <HStack gap={3}>
                <VStack gap={0}>
                  <Text tone="secondary">Code</Text>
                  <Mono>{module?.code}</Mono>
                </VStack>
              </HStack>

              {module?.description && (
                <Text tone="secondary">{module.description}</Text>
              )}

              {canToggle && (
                <HStack gap={3}>
                  <Toggle
                    label={module?.is_active ? 'Active on storefront' : 'Hidden from storefront'}
                    checked={form.data.is_active}
                    onChange={handleToggle}
                  />
                  <Text tone="secondary">
                    Disabling removes this module from the storefront. Existing tenant subscriptions are not affected.
                  </Text>
                </HStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {canPrice && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Pricing</Eyebrow>

                <HStack gap={4}>
                  <Field label="Monthly Price (USD)" htmlFor="price_monthly" error={form.errors.price_monthly} hint="Per-seat or flat fee per month.">
                    <Input
                      id="price_monthly"
                      type="number"
                      value={String(form.data.price_monthly)}
                      onChange={e => form.setData('price_monthly', e.target.value)}
                      placeholder="0.00"
                      leftIcon="dollar"
                      error={form.errors.price_monthly}
                    />
                  </Field>

                  <Field label="Annual Price (USD)" htmlFor="price_annual" error={form.errors.price_annual} hint="Per-seat or flat fee per year.">
                    <Input
                      id="price_annual"
                      type="number"
                      value={String(form.data.price_annual)}
                      onChange={e => form.setData('price_annual', e.target.value)}
                      placeholder="0.00"
                      leftIcon="dollar"
                      error={form.errors.price_annual}
                    />
                  </Field>
                </HStack>
              </VStack>
            </CardBody>
          </Card>
        )}

        {canConfigure && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Advanced Configuration</Eyebrow>
                <Text tone="secondary">
                  JSON object for module-specific settings. Changes take effect immediately on save.
                </Text>

                <Field
                  label="Config (JSON)"
                  htmlFor="config"
                  error={jsonError ?? form.errors.config}
                  hint="Must be valid JSON."
                >
                  <Input
                    id="config"
                    value={form.data.config}
                    onChange={e => {
                      setJsonError(null);
                      form.setData('config', e.target.value);
                    }}
                    error={jsonError ?? form.errors.config}
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>
        )}

        <HStack gap={3}>
          {(canPrice || canConfigure) && (
            <Button type="submit" intent="primary" loading={form.processing} disabled={form.processing}>
              Save Changes
            </Button>
          )}
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('platform.admin.modules.index'))}
          >
            Back to Modules
          </Button>
        </HStack>

      </VStack>
    </FormPageLayout>
  );
}

ModuleConfigure.layout = page => <App title="Configure Module">{page}</App>;
