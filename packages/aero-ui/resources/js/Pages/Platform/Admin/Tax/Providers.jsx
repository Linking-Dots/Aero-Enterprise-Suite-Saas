import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Toggle,
  Modal,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const PROVIDER_META = {
  stripe_tax: {
    label:       'Stripe Tax',
    description: 'Automated tax calculation integrated with Stripe Billing.',
    fields: [
      { key: 'api_key',  label: 'API Key',  type: 'password', placeholder: 'sk_live_...' },
    ],
  },
  avalara: {
    label:       'Avalara',
    description: 'Enterprise-grade tax compliance platform.',
    fields: [
      { key: 'account_id', label: 'Account ID',  type: 'text',     placeholder: '123456789' },
      { key: 'license_key', label: 'License Key', type: 'password', placeholder: 'XXXXXXXX-XXXX-...' },
      { key: 'environment', label: 'Environment', type: 'text',     placeholder: 'production' },
    ],
  },
  taxjar: {
    label:       'TaxJar',
    description: 'Sales tax API for ecommerce and SaaS.',
    fields: [
      { key: 'api_token',  label: 'API Token', type: 'password', placeholder: 'tjt_...' },
      { key: 'sandbox',    label: 'Sandbox',   type: 'toggle' },
    ],
  },
};

export default function TaxProviders({ providers }) {
  const toast        = useToast();
  const canConfigure = useHRMAC('tax-engine.tax-providers.configure');

  const [editTarget,  setEditTarget]  = useState(null);
  const [credentials, setCredentials] = useState({});
  const [isActive,    setIsActive]    = useState(false);
  const [saving,      setSaving]      = useState(false);
  const [testTarget,  setTestTarget]  = useState(null);
  const [testResult,  setTestResult]  = useState(null);
  const [testing,     setTesting]     = useState(false);

  function openEdit(provider) {
    setEditTarget(provider);
    setCredentials(provider.config ?? {});
    setIsActive(provider.is_active ?? false);
  }

  function closeEdit() {
    setEditTarget(null);
    setCredentials({});
  }

  function handleSave() {
    if (!editTarget) return;
    setSaving(true);
    router.put(
      route('platform.admin.tax.providers.update', editTarget.code),
      { config: credentials, is_active: isActive },
      {
        preserveState: true,
        onSuccess: () => { toast.success('Provider configuration saved.'); closeEdit(); },
        onError:   () => toast.error('Failed to save provider configuration.'),
        onFinish:  () => setSaving(false),
      }
    );
  }

  function openTest(provider) {
    setTestTarget(provider);
    setTestResult(null);
  }

  function runTest() {
    if (!testTarget) return;
    setTesting(true);
    router.post(
      route('platform.admin.tax.providers.test', testTarget.code),
      {},
      {
        preserveState: true,
        onSuccess: (page) => {
          setTestResult({ success: true, message: page.props.flash?.success ?? 'Connection successful.' });
        },
        onError: () => {
          setTestResult({ success: false, message: 'Connection test failed. Check your credentials.' });
        },
        onFinish: () => setTesting(false),
      }
    );
  }

  const meta = editTarget ? PROVIDER_META[editTarget.code] : null;

  const columns = [
    {
      key: 'name',
      label: 'Provider',
      render: (row) => (
        <VStack gap={0}>
          <Text>{PROVIDER_META[row.code]?.label ?? row.name}</Text>
          <Text tone="secondary">{PROVIDER_META[row.code]?.description ?? ''}</Text>
        </VStack>
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
      key: 'configured',
      label: 'Configured',
      render: (row) => {
        const hasConfig = row.config && Object.keys(row.config).length > 0;
        return (
          <Badge intent={hasConfig ? 'info' : 'neutral'}>
            {hasConfig ? 'Credentials Set' : 'Not Configured'}
          </Badge>
        );
      },
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canConfigure && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Configure
            </Button>
          )}
          <Button intent="soft" size="sm" onClick={() => openTest(row)}>
            Test Connection
          </Button>
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Tax Providers"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tax Rates', href: route('platform.admin.tax.rates.index') },
        { label: 'Providers' },
      ]}
      description="Connect a third-party tax calculation engine to automate tax computation on invoices."
    >
      <DataTable
        columns={columns}
        rows={providers ?? []}
        empty="No tax providers available."
      />

      {/* Configure Modal */}
      <Modal
        open={!!editTarget}
        onClose={closeEdit}
        title={`Configure ${meta?.label ?? editTarget?.name}`}
        size="md"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={closeEdit}>Cancel</Button>
            <Button intent="primary" loading={saving} disabled={saving} onClick={handleSave}>
              Save Configuration
            </Button>
          </HStack>
        }
      >
        {meta && (
          <VStack gap={4}>
            <Alert
              intent="warning"
              title="API keys are stored encrypted. Never share credentials in plain text."
            />

            {meta.fields.map(field =>
              field.type === 'toggle' ? (
                <Toggle
                  key={field.key}
                  label={field.label}
                  checked={!!credentials[field.key]}
                  onChange={e => setCredentials({ ...credentials, [field.key]: e.target.checked })}
                />
              ) : (
                <Field key={field.key} label={field.label} htmlFor={`tp_${field.key}`}>
                  <Input
                    id={`tp_${field.key}`}
                    type={field.type}
                    value={credentials[field.key] ?? ''}
                    onChange={e => setCredentials({ ...credentials, [field.key]: e.target.value })}
                    placeholder={field.placeholder}
                  />
                </Field>
              )
            )}

            <Toggle
              label="Active"
              checked={isActive}
              onChange={e => setIsActive(e.target.checked)}
            />
          </VStack>
        )}
      </Modal>

      {/* Test Connection Modal */}
      <Modal
        open={!!testTarget}
        onClose={() => { setTestTarget(null); setTestResult(null); }}
        title={`Test Connection — ${PROVIDER_META[testTarget?.code]?.label ?? testTarget?.name}`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => { setTestTarget(null); setTestResult(null); }}>
              Close
            </Button>
            {!testResult && (
              <Button intent="primary" loading={testing} disabled={testing} onClick={runTest}>
                Run Test
              </Button>
            )}
          </HStack>
        }
      >
        <VStack gap={3}>
          {!testResult && (
            <Text tone="secondary">
              This will send a test request to the provider API to verify your credentials are valid.
            </Text>
          )}
          {testResult && (
            <Alert
              intent={testResult.success ? 'success' : 'danger'}
              title={testResult.message}
            />
          )}
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

TaxProviders.layout = page => <App title="Tax Providers">{page}</App>;
