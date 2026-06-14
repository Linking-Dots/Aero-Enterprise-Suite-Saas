import { useState } from 'react';
import { router } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import {
  FormPageLayout,
  Card, CardContent,
  VStack, HStack,
  Text,
  Eyebrow,
  Field,
  Input,
  Select,
  Toggle,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SMS_PROVIDER_OPTIONS = [
  { value: 'log',    label: 'Log (development only)' },
  { value: 'twilio', label: 'Twilio'                 },
  { value: 'nexmo',  label: 'Nexmo'                  },
  { value: 'vonage', label: 'Vonage'                 },
];

const TEST_CHANNEL_OPTIONS = [
  { value: 'email', label: 'Email' },
  { value: 'phone', label: 'Phone / SMS' },
];

export default function VerificationPage({ config = {} }) {
  const toast   = useToast();
  const { errors } = usePage().props;
  const canEdit = useHRMAC('auth.sso_identity.verification.edit');

  const [form, setForm] = useState({
    email_verification_required:  config.email_verification_required  ?? true,
    email_expiry_hours:           config.email_expiry_hours           ?? 24,
    phone_verification_enabled:   config.phone_verification_enabled   ?? false,
    phone_code_expiry_minutes:    config.phone_code_expiry_minutes    ?? 10,
    sms_provider:                 config.sms_provider                 ?? 'log',
  });

  const [saving, setSaving]           = useState(false);
  const [testChannel, setTestChannel] = useState('email');
  const [testTo, setTestTo]           = useState('');
  const [testSending, setTestSending] = useState(false);

  const setField = (field, value) => setForm(prev => ({ ...prev, [field]: value }));

  function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    router.post(route('core.identity.verification.update'), form, {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => toast.success('Verification settings saved.'),
      onError:   () => toast.error('Failed to save verification settings.'),
      onFinish:  () => setSaving(false),
    });
  }

  function handleSendTest(e) {
    e.preventDefault();
    if (!testTo.trim()) {
      toast.error('Enter a recipient address or number.');
      return;
    }
    setTestSending(true);
    router.post(
      route('core.identity.verification.test'),
      { channel: testChannel, to: testTo },
      {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => toast.success(`Test ${testChannel} sent to ${testTo}.`),
        onError:   () => toast.error('Failed to send test message.'),
        onFinish:  () => setTestSending(false),
      }
    );
  }

  return (
    <FormPageLayout
      title="Verification Settings"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'Verification' },
      ]}
      description="Configure email and phone verification requirements for user accounts."
    >
      <form onSubmit={handleSave}>
        <VStack gap={4}>
          {/* Email Verification */}
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Eyebrow>Email Verification</Eyebrow>

                <Toggle
                  label="Require email verification on signup"
                  checked={form.email_verification_required}
                  onChange={e => setField('email_verification_required', e.target.checked)}
                  disabled={!canEdit}
                />

                <Field
                  label="Verification link expiry (hours)"
                  htmlFor="email_expiry_hours"
                  hint="How long the email verification link remains valid."
                  error={errors.email_expiry_hours}
                  required
                >
                  <Input
                    id="email_expiry_hours"
                    type="number"
                    value={form.email_expiry_hours}
                    onChange={e => setField('email_expiry_hours', parseInt(e.target.value, 10))}
                    error={errors.email_expiry_hours}
                    min={1}
                    max={720}
                    disabled={!canEdit}
                  />
                </Field>
              </VStack>
            </CardContent>
          </Card>

          {/* Phone Verification */}
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Eyebrow>Phone Verification</Eyebrow>

                <Toggle
                  label="Enable phone / SMS verification"
                  checked={form.phone_verification_enabled}
                  onChange={e => setField('phone_verification_enabled', e.target.checked)}
                  disabled={!canEdit}
                />

                {form.phone_verification_enabled && (
                  <>
                    <Field
                      label="SMS code expiry (minutes)"
                      htmlFor="phone_code_expiry_minutes"
                      hint="How long the one-time SMS code remains valid."
                      error={errors.phone_code_expiry_minutes}
                      required
                    >
                      <Input
                        id="phone_code_expiry_minutes"
                        type="number"
                        value={form.phone_code_expiry_minutes}
                        onChange={e => setField('phone_code_expiry_minutes', parseInt(e.target.value, 10))}
                        error={errors.phone_code_expiry_minutes}
                        min={1}
                        max={60}
                        disabled={!canEdit}
                      />
                    </Field>

                    <Field
                      label="SMS provider"
                      htmlFor="sms_provider"
                      error={errors.sms_provider}
                      required
                    >
                      <Select
                        id="sms_provider"
                        value={form.sms_provider}
                        onChange={e => setField('sms_provider', e.target.value)}
                        options={SMS_PROVIDER_OPTIONS}
                        disabled={!canEdit}
                      />
                    </Field>
                  </>
                )}
              </VStack>
            </CardContent>
          </Card>

          {canEdit && (
            <HStack gap={2} justify="end">
              <Button type="submit" intent="primary" loading={saving}>
                Save Changes
              </Button>
            </HStack>
          )}
        </VStack>
      </form>

      {/* Test Section */}
      <Card>
        <CardContent>
          <VStack gap={4}>
            <Eyebrow>Send Test Message</Eyebrow>
            <Text tone="secondary" size="sm">
              Send a test verification message to confirm your configuration is working.
            </Text>

            <form onSubmit={handleSendTest}>
              <VStack gap={3}>
                <Field label="Channel" htmlFor="test_channel">
                  <Select
                    id="test_channel"
                    value={testChannel}
                    onChange={e => setTestChannel(e.target.value)}
                    options={TEST_CHANNEL_OPTIONS}
                  />
                </Field>

                <Field
                  label={testChannel === 'email' ? 'Email address' : 'Phone number'}
                  htmlFor="test_to"
                  required
                >
                  <Input
                    id="test_to"
                    type={testChannel === 'email' ? 'email' : 'tel'}
                    value={testTo}
                    onChange={e => setTestTo(e.target.value)}
                    placeholder={testChannel === 'email' ? 'user@example.com' : '+1 555 000 0000'}
                    leftIcon={testChannel === 'email' ? 'envelope' : 'phone'}
                  />
                </Field>

                <HStack gap={2} justify="end">
                  <Button type="submit" intent="soft" loading={testSending}>
                    Send Test
                  </Button>
                </HStack>
              </VStack>
            </form>
          </VStack>
        </CardContent>
      </Card>
    </FormPageLayout>
  );
}

VerificationPage.layout = page => (
  <App title="Verification Settings">{page}</App>
);
