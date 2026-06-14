import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Toggle,
  Modal,
  Card,
  CardBody,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const CONNECTOR_META = {
  stripe: {
    label: 'Stripe',
    description: 'Payment processing — subscriptions, invoices, refunds.',
    fields: [
      { key: 'publishable_key', label: 'Publishable Key', placeholder: 'pk_live_...' },
      { key: 'secret_key',      label: 'Secret Key',      placeholder: 'sk_live_...', secret: true },
      { key: 'webhook_secret',  label: 'Webhook Secret',  placeholder: 'whsec_...',   secret: true },
    ],
  },
  slack: {
    label: 'Slack',
    description: 'Send platform notifications to Slack channels.',
    fields: [
      { key: 'bot_token',     label: 'Bot Token',     placeholder: 'xoxb-...', secret: true },
      { key: 'channel',       label: 'Default Channel', placeholder: '#platform-alerts' },
    ],
  },
  sendgrid: {
    label: 'SendGrid',
    description: 'Transactional email delivery for tenant communications.',
    fields: [
      { key: 'api_key',        label: 'API Key',       placeholder: 'SG.xxx', secret: true },
      { key: 'from_email',     label: 'From Email',    placeholder: 'noreply@example.com' },
      { key: 'from_name',      label: 'From Name',     placeholder: 'AEOS Platform' },
    ],
  },
  aws_s3: {
    label: 'AWS S3',
    description: 'Object storage for tenant file uploads and exports.',
    fields: [
      { key: 'access_key_id',     label: 'Access Key ID',     placeholder: 'AKIA...' },
      { key: 'secret_access_key', label: 'Secret Access Key', placeholder: '...', secret: true },
      { key: 'region',            label: 'Region',            placeholder: 'us-east-1' },
      { key: 'bucket',            label: 'Bucket Name',       placeholder: 'aeos-storage' },
    ],
  },
  google_oauth: {
    label: 'Google OAuth',
    description: 'Enable Google social login for tenant users.',
    fields: [
      { key: 'client_id',     label: 'Client ID',     placeholder: '...' },
      { key: 'client_secret', label: 'Client Secret', placeholder: '...', secret: true },
    ],
  },
  github_oauth: {
    label: 'GitHub OAuth',
    description: 'Enable GitHub social login for tenant users.',
    fields: [
      { key: 'client_id',     label: 'Client ID',     placeholder: '...' },
      { key: 'client_secret', label: 'Client Secret', placeholder: '...', secret: true },
    ],
  },
};

function ConnectorCard({ connector, onConfigure }) {
  const meta = CONNECTOR_META[connector.code] ?? {
    label: connector.code,
    description: '',
    fields: [],
  };

  return (
    <Card>
      <CardBody>
        <VStack gap={3}>
          <HStack gap={3}>
            <VStack gap={1}>
              <Text>{meta.label}</Text>
              <Text tone="secondary">{meta.description}</Text>
            </VStack>
            <Badge intent={connector.is_enabled ? 'success' : 'neutral'}>
              {connector.is_enabled ? 'Enabled' : 'Disabled'}
            </Badge>
          </HStack>
          {connector.last_verified_at && (
            <Text tone="secondary">
              Last verified: <Mono>{connector.last_verified_at}</Mono>
            </Text>
          )}
          <HStack gap={2}>
            <Button intent="soft" size="sm" onClick={() => onConfigure(connector)}>
              Configure
            </Button>
          </HStack>
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function Connectors({ connectors }) {
  const toast        = useToast();
  const canConfigure = useHRMAC('platform-integrations.connectors.configure');

  const [configTarget, setConfigTarget] = useState(null);
  const [showSecret,   setShowSecret]   = useState({});

  const form = useForm({
    is_enabled:  false,
    credentials: {},
  });

  function openConfigure(connector) {
    setConfigTarget(connector);
    setShowSecret({});
    const meta = CONNECTOR_META[connector.code] ?? { fields: [] };
    const creds = {};
    meta.fields.forEach(f => {
      creds[f.key] = connector.credentials?.[f.key] ?? '';
    });
    form.setData({
      is_enabled:  connector.is_enabled ?? false,
      credentials: creds,
    });
  }

  function handleSave(e) {
    e.preventDefault();
    form.post(
      route('platform.admin.integrations.connectors.configure', configTarget.code),
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`${CONNECTOR_META[configTarget.code]?.label ?? configTarget.code} saved.`);
          setConfigTarget(null);
        },
        onError: () => toast.error('Failed to save connector.'),
      }
    );
  }

  function setCredential(key, value) {
    form.setData('credentials', { ...form.data.credentials, [key]: value });
  }

  const meta = configTarget ? (CONNECTOR_META[configTarget.code] ?? { fields: [] }) : { fields: [] };

  return (
    <IndexPageLayout
      title="Connectors"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Integrations' },
        { label: 'Connectors' },
      ]}
      description="Configure third-party service integrations for the platform."
    >
      <VStack gap={4}>
        {(connectors ?? []).length === 0 && (
          <Text tone="secondary">No connectors available.</Text>
        )}
        <VStack gap={3}>
          {(connectors ?? []).map(c => (
            <ConnectorCard key={c.code} connector={c} onConfigure={openConfigure} />
          ))}
        </VStack>
      </VStack>

      {/* Configure Modal */}
      <Modal
        open={!!configTarget}
        onClose={() => setConfigTarget(null)}
        title={`Configure ${CONNECTOR_META[configTarget?.code]?.label ?? configTarget?.code}`}
        size="md"
        footer={
          <HStack gap={2}>
            {canConfigure && (
              <Button
                intent="primary"
                loading={form.processing}
                disabled={form.processing}
                onClick={handleSave}
              >
                Save
              </Button>
            )}
            <Button intent="ghost" onClick={() => setConfigTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Toggle
            label="Enable this connector"
            checked={form.data.is_enabled}
            onChange={e => form.setData('is_enabled', e.target.checked)}
          />

          <Eyebrow>Credentials</Eyebrow>

          {meta.fields.map(f => (
            <Field key={f.key} label={f.label} htmlFor={`cred_${f.key}`} error={form.errors[`credentials.${f.key}`]}>
              <Input
                id={`cred_${f.key}`}
                type={f.secret && !showSecret[f.key] ? 'password' : 'text'}
                value={form.data.credentials[f.key] ?? ''}
                onChange={e => setCredential(f.key, e.target.value)}
                placeholder={f.placeholder}
                rightIcon={f.secret ? (showSecret[f.key] ? 'eyeSlash' : 'eye') : undefined}
                error={form.errors[`credentials.${f.key}`]}
              />
            </Field>
          ))}

          {form.errors.credentials && (
            <Alert intent="danger" title={form.errors.credentials} />
          )}
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

Connectors.layout = page => <App title="Connectors">{page}</App>;
