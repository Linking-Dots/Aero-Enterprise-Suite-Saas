import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card,
  CardBody,
  Button,
  HStack, VStack,
  Text,
  Field,
  Input,
  Alert,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const LIMIT_FIELDS = [
  {
    key: 'api_global_per_minute',
    label: 'Global API Requests / Minute',
    description: 'Maximum number of API requests allowed across all keys per minute.',
  },
  {
    key: 'api_per_key_per_minute',
    label: 'Per-Key API Requests / Minute',
    description: 'Maximum number of API requests a single API key can make per minute.',
  },
  {
    key: 'api_burst_allowance',
    label: 'Burst Allowance',
    description: 'Additional requests permitted in short bursts above the per-minute limit.',
  },
  {
    key: 'webhook_per_hour',
    label: 'Webhook Deliveries / Hour',
    description: 'Maximum number of webhook events dispatched per hour.',
  },
  {
    key: 'auth_attempts_per_minute',
    label: 'Auth Attempts / Minute',
    description: 'Maximum login or token authentication attempts allowed per minute.',
  },
];

export default function RateLimits({ limits }) {
  const { errors } = usePage().props;
  const canEdit = useHRMAC('core.api.rate_limits.edit');

  const [form,    setForm]    = useState({
    api_global_per_minute:    limits?.api_global_per_minute    ?? '',
    api_per_key_per_minute:   limits?.api_per_key_per_minute   ?? '',
    api_burst_allowance:      limits?.api_burst_allowance      ?? '',
    webhook_per_hour:         limits?.webhook_per_hour         ?? '',
    auth_attempts_per_minute: limits?.auth_attempts_per_minute ?? '',
  });
  const [saving,  setSaving]  = useState(false);
  const [saved,   setSaved]   = useState(false);

  const setValue = (key, value) => {
    setSaved(false);
    setForm(prev => ({ ...prev, [key]: value }));
  };

  const handleSave = () => {
    setSaving(true);
    setSaved(false);
    router.post(route('core.api.rate-limits.update'), form, {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => setSaved(true),
      onFinish:  () => setSaving(false),
    });
  };

  return (
    <>
      <IndexPageLayout
        title="Rate Limits"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Rate Limits' },
        ]}
        description="Configure API and authentication rate limits to protect platform stability."
        actions={
          canEdit && (
            <Button
              intent="primary"
              loading={saving}
              onClick={handleSave}
            >
              Save Changes
            </Button>
          )
        }
      >
        <VStack gap={4}>
          {saved && (
            <Alert intent="success" title="Rate limits saved successfully." />
          )}

          {errors && Object.keys(errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below before saving." />
          )}

          <Card>
            <CardBody>
              <VStack gap={5}>
                {LIMIT_FIELDS.map(field => (
                  <VStack key={field.key} gap={1}>
                    <Field
                      label={field.label}
                      htmlFor={`rl-${field.key}`}
                      error={errors?.[field.key]}
                    >
                      <Input
                        id={`rl-${field.key}`}
                        type="number"
                        placeholder="0"
                        value={form[field.key]}
                        onChange={e => setValue(field.key, e.target.value)}
                        error={!!errors?.[field.key]}
                        disabled={!canEdit}
                      />
                    </Field>
                    <Text tone="secondary" size="sm">{field.description}</Text>
                  </VStack>
                ))}
              </VStack>
            </CardBody>
          </Card>
        </VStack>
      </IndexPageLayout>
    </>
  );
}

RateLimits.layout = page => (
  <App title="Rate Limits">{page}</App>
);
