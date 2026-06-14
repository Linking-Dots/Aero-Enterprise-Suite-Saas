import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  Field,
  Input,
  Toggle,
  Button,
  HStack,
  VStack,
  Text,
  Eyebrow,
  Card,
  CardBody,
  Alert,
  Badge,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function GatewayCard({ gatewayKey, label, gateway }) {
  const toast = useToast();

  const form = useForm({
    enabled:    gateway?.enabled    ?? false,
    is_default: gateway?.is_default ?? false,
    public_key: gateway?.public_key ?? '',
    secret_key: '',
    webhook_secret: '',
    extra: gateway?.extra ? JSON.stringify(gateway.extra, null, 2) : '',
  });

  const [testResult, setTestResult]   = useState(null); // null | 'success' | 'failure'
  const [testMessage, setTestMessage] = useState('');
  const [testing,     setTesting]     = useState(false);

  function handleSave(e) {
    e.preventDefault();
    form.post(
      route('platform.admin.billing.gateways.update', gatewayKey),
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`${label} settings saved.`);
          // Clear secret fields after save
          form.setData('secret_key', '');
          form.setData('webhook_secret', '');
          setTestResult(null);
        },
        onError: () => toast.error(`Failed to save ${label} settings.`),
      }
    );
  }

  function handleTest() {
    setTesting(true);
    setTestResult(null);
    router.post(
      route('platform.admin.billing.gateways.test', gatewayKey),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          const msg = page.props.flash?.message ?? 'Connection successful.';
          setTestResult('success');
          setTestMessage(msg);
        },
        onError: (errors) => {
          setTestResult('failure');
          setTestMessage(Object.values(errors)[0] ?? 'Connection test failed.');
        },
        onFinish: () => setTesting(false),
      }
    );
  }

  return (
    <Card>
      <CardBody>
        <form onSubmit={handleSave}>
          <VStack gap={4}>
            <HStack gap={2}>
              <Eyebrow>{label}</Eyebrow>
              {gateway?.is_default && (
                <Badge intent="primary">Default</Badge>
              )}
            </HStack>

            <Field label="Enable Gateway" htmlFor={`${gatewayKey}_enabled`}>
              <Toggle
                label={`Enable ${label} payment processing`}
                checked={form.data.enabled}
                onChange={e => form.setData('enabled', e.target.checked)}
              />
            </Field>

            <Field label="Set as Default Gateway" htmlFor={`${gatewayKey}_default`}>
              <Toggle
                label="Use this gateway for new subscriptions by default"
                checked={form.data.is_default}
                onChange={e => form.setData('is_default', e.target.checked)}
              />
            </Field>

            {form.data.enabled && (
              <>
                <Field
                  label="Public Key"
                  htmlFor={`${gatewayKey}_public_key`}
                  error={form.errors.public_key}
                >
                  <Input
                    id={`${gatewayKey}_public_key`}
                    value={form.data.public_key}
                    onChange={e => form.setData('public_key', e.target.value)}
                    placeholder={gatewayKey === 'stripe' ? 'pk_live_...' : 'store_id'}
                    error={form.errors.public_key}
                  />
                </Field>

                <Field
                  label="Secret Key"
                  htmlFor={`${gatewayKey}_secret_key`}
                  error={form.errors.secret_key}
                  hint="Leave blank to keep the existing secret."
                >
                  <Input
                    id={`${gatewayKey}_secret_key`}
                    type="password"
                    value={form.data.secret_key}
                    onChange={e => form.setData('secret_key', e.target.value)}
                    placeholder="Leave blank to keep current"
                    error={form.errors.secret_key}
                  />
                </Field>

                <Field
                  label="Webhook Secret"
                  htmlFor={`${gatewayKey}_webhook_secret`}
                  error={form.errors.webhook_secret}
                  hint="Leave blank to keep the existing webhook secret."
                >
                  <Input
                    id={`${gatewayKey}_webhook_secret`}
                    type="password"
                    value={form.data.webhook_secret}
                    onChange={e => form.setData('webhook_secret', e.target.value)}
                    placeholder="Leave blank to keep current"
                    error={form.errors.webhook_secret}
                  />
                </Field>
              </>
            )}

            {testResult === 'success' && (
              <Alert intent="success" title={testMessage || 'Connection successful.'} />
            )}
            {testResult === 'failure' && (
              <Alert intent="danger" title={testMessage || 'Connection test failed.'} />
            )}

            <HStack gap={2}>
              <Button
                type="submit"
                intent="primary"
                loading={form.processing}
                disabled={form.processing}
              >
                Save {label}
              </Button>
              <Button
                type="button"
                intent="soft"
                loading={testing}
                disabled={testing || !form.data.enabled}
                onClick={handleTest}
              >
                Test Connection
              </Button>
            </HStack>
          </VStack>
        </form>
      </CardBody>
    </Card>
  );
}

export default function Gateways({ gateways }) {
  return (
    <IndexPageLayout
      title="Payment Gateways"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Billing', href: route('platform.admin.billing.subscriptions.index') },
        { label: 'Gateways' },
      ]}
      description="Configure payment gateways for subscription billing."
    >
      <HStack gap={6} wrap>
        <GatewayCard
          gatewayKey="stripe"
          label="Stripe"
          gateway={gateways?.stripe}
        />
        <GatewayCard
          gatewayKey="sslcommerz"
          label="SSLCommerz"
          gateway={gateways?.sslcommerz}
        />
      </HStack>
    </IndexPageLayout>
  );
}

Gateways.layout = page => <App title="Payment Gateways">{page}</App>;
