import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  Select,
  Button,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Alert,
  Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ENCRYPTION_OPTIONS = [
  { value: '',    label: 'None' },
  { value: 'tls', label: 'TLS' },
  { value: 'ssl', label: 'SSL' },
];

export default function Email({ settings, testResult }) {
  const toast   = useToast();
  const canEdit = useHRMAC('system-settings.email-settings.edit');
  const canTest = useHRMAC('system-settings.email-settings.test');

  const [testTo,      setTestTo]      = useState('');
  const [testSending, setTestSending] = useState(false);

  const form = useForm({
    host:       settings?.host       ?? '',
    port:       settings?.port       ?? 587,
    username:   settings?.username   ?? '',
    password:   '',
    encryption: settings?.encryption ?? 'tls',
    from_email: settings?.from_email ?? '',
    from_name:  settings?.from_name  ?? '',
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.put(route('platform.admin.settings.email.update'), {
      onSuccess: () => toast.success('Email settings saved.'),
      onError:   () => toast.error('Failed to save email settings.'),
    });
  }

  function sendTest() {
    if (!testTo) return;
    setTestSending(true);
    router.post(
      route('platform.admin.settings.email.test'),
      { to: testTo },
      {
        preserveState:  true,
        preserveScroll: true,
        onFinish: () => setTestSending(false),
        onSuccess: () => toast.success('Test email sent successfully.'),
        onError:   () => toast.error('Test email failed — check SMTP credentials.'),
      }
    );
  }

  return (
    <FormPageLayout
      title="Email Settings"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Settings' },
        { label: 'Email / SMTP' },
      ]}
      description="Configure SMTP credentials for outbound platform email."
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        {testResult && (
          <Alert
            intent={testResult.ok ? 'success' : 'danger'}
            title={testResult.ok ? 'Test email delivered.' : `Test failed: ${testResult.error}`}
          />
        )}

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>SMTP Server</Eyebrow>

              <HStack gap={4}>
                <Field label="Host" htmlFor="host" error={form.errors.host} required>
                  <Input
                    id="host"
                    value={form.data.host}
                    onChange={e => form.setData('host', e.target.value)}
                    placeholder="smtp.mailgun.org"
                    error={form.errors.host}
                  />
                </Field>

                <Field label="Port" htmlFor="port" error={form.errors.port} required>
                  <Input
                    id="port"
                    type="number"
                    value={String(form.data.port)}
                    onChange={e => form.setData('port', Number(e.target.value))}
                    placeholder="587"
                    error={form.errors.port}
                  />
                </Field>

                <Field label="Encryption" htmlFor="encryption" error={form.errors.encryption}>
                  <Select
                    id="encryption"
                    value={form.data.encryption}
                    onChange={e => form.setData('encryption', e.target.value)}
                    options={ENCRYPTION_OPTIONS}
                  />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Authentication</Eyebrow>

              <HStack gap={4}>
                <Field label="Username" htmlFor="username" error={form.errors.username}>
                  <Input
                    id="username"
                    value={form.data.username}
                    onChange={e => form.setData('username', e.target.value)}
                    placeholder="apikey"
                    error={form.errors.username}
                  />
                </Field>

                <Field
                  label="Password"
                  htmlFor="password"
                  error={form.errors.password}
                  hint={settings?.password_set ? 'A password is stored. Leave blank to keep current.' : undefined}
                >
                  <Input
                    id="password"
                    type="password"
                    value={form.data.password}
                    onChange={e => form.setData('password', e.target.value)}
                    placeholder={settings?.password_set ? '••••••••' : 'Enter password'}
                    error={form.errors.password}
                  />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Sender Identity</Eyebrow>

              <HStack gap={4}>
                <Field label="From Email" htmlFor="from_email" error={form.errors.from_email} required>
                  <Input
                    id="from_email"
                    type="email"
                    value={form.data.from_email}
                    onChange={e => form.setData('from_email', e.target.value)}
                    placeholder="noreply@example.com"
                    leftIcon="mail"
                    error={form.errors.from_email}
                  />
                </Field>

                <Field label="From Name" htmlFor="from_name" error={form.errors.from_name}>
                  <Input
                    id="from_name"
                    value={form.data.from_name}
                    onChange={e => form.setData('from_name', e.target.value)}
                    placeholder="AEOS365 Platform"
                    error={form.errors.from_name}
                  />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {canEdit && (
          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={form.processing} disabled={form.processing}>
              Save Email Settings
            </Button>
          </HStack>
        )}

        {canTest && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Send Test Email</Eyebrow>
                <Text tone="secondary">
                  Sends a test message via the currently saved SMTP configuration.
                  Save your settings before testing.
                </Text>
                <HStack gap={3} align="end">
                  <Field label="Recipient Email" htmlFor="test_to" hint="Where to deliver the test message.">
                    <Input
                      id="test_to"
                      type="email"
                      value={testTo}
                      onChange={e => setTestTo(e.target.value)}
                      placeholder="you@example.com"
                      leftIcon="mail"
                    />
                  </Field>
                  <Button
                    type="button"
                    intent="soft"
                    onClick={sendTest}
                    loading={testSending}
                    disabled={testSending || !testTo}
                  >
                    Send Test
                  </Button>
                </HStack>
              </VStack>
            </CardBody>
          </Card>
        )}

      </VStack>
    </FormPageLayout>
  );
}

Email.layout = page => <App title="Email Settings">{page}</App>;
