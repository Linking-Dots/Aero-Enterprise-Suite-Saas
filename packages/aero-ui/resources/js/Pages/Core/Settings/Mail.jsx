/**
 * Mail / SMTP Settings — tenant outgoing email configuration.
 *
 * Props: { mail: { driver, host, port, username, from_name, from_email, encryption } }
 *
 * Ported onto the unified SettingsLayout shell (Task 4). Violations fixed:
 *   P0-1: removed all style={} props
 *   P0-2: replaced raw <select> with Select engine component
 *   P1-1: errors passed as strings (no ?.[0])
 *   P1-2: removed manual X-CSRF-TOKEN header — axios handles automatically
 *   P2-1: variant= → intent= on Button
 *   HRMAC: core.settings.mail.update → core.settings.mail_settings.update (Task 0 fix)
 */
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import {
  Field, Input, Select, Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

const DRIVER_OPTIONS = [
  { value: 'smtp',    label: 'SMTP' },
  { value: 'ses',     label: 'Amazon SES' },
  { value: 'mailgun', label: 'Mailgun' },
  { value: 'log',     label: 'Log (debug only)' },
];

const ENCRYPTION_OPTIONS = [
  { value: 'tls',  label: 'TLS' },
  { value: 'ssl',  label: 'SSL' },
  { value: 'none', label: 'None' },
];

export default function MailSettings({ mail = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.mail_settings.update');

  const [testEmail,   setTestEmail]   = useState('');
  const [testLoading, setTestLoading] = useState(false);
  const [testResult,  setTestResult]  = useState(null); // { ok, message }

  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    driver:      mail.driver      ?? 'smtp',
    host:        mail.host        ?? '',
    port:        mail.port        ?? '587',
    username:    mail.username    ?? '',
    password:    '',
    encryption:  mail.encryption  ?? 'tls',
    from_name:   mail.from_name   ?? '',
    from_email:  mail.from_email  ?? '',
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.mail.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Mail settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  async function handleTest() {
    if (!testEmail.trim()) {
      toast.error('Enter a recipient address first.');
      return;
    }
    setTestLoading(true);
    setTestResult(null);
    try {
      const { data: res } = await axios.post(route('core.settings.mail.test'), { email: testEmail });
      setTestResult({ ok: true, message: res.message ?? 'Test email sent.' });
      toast.success(res.message ?? 'Test email sent.');
    } catch (err) {
      const msg = err.response?.data?.message ?? 'Failed to send test email.';
      setTestResult({ ok: false, message: msg });
      toast.error(msg);
    } finally {
      setTestLoading(false);
    }
  }

  return (
    <form onSubmit={handleSave}>
      <SettingsSection
        title="Email / SMTP Settings"
        description="Configure the outgoing mail driver, SMTP server, and sender identity."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => reset()}
        onSave={handleSave}
      >
        {/* ── Driver ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Mail Driver</Text></CardHeader>
          <CardBody>
            <Field label="Driver" error={errors.driver}>
              <Select
                value={data.driver}
                onChange={e => setData('driver', e.target.value)}
                options={DRIVER_OPTIONS}
              />
            </Field>
          </CardBody>
        </Card>

        {/* ── SMTP Server ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">SMTP Server</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Host" error={errors.host}>
                <Input
                  value={data.host}
                  onChange={e => setData('host', e.target.value)}
                  placeholder="smtp.example.com"
                />
              </Field>
              <HStack gap={4} wrap>
                <Field label="Port" error={errors.port}>
                  <Input
                    type="number"
                    value={data.port}
                    onChange={e => setData('port', e.target.value)}
                    placeholder="587"
                  />
                </Field>
                <Field label="Encryption" error={errors.encryption}>
                  <Select
                    value={data.encryption}
                    onChange={e => setData('encryption', e.target.value)}
                    options={ENCRYPTION_OPTIONS}
                  />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* ── Authentication ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Authentication</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Username" error={errors.username}>
                <Input
                  value={data.username}
                  onChange={e => setData('username', e.target.value)}
                  placeholder="SMTP username"
                  leftIcon="user"
                />
              </Field>
              <Field label="Password" hint="Leave blank to keep existing password." error={errors.password}>
                <Input
                  type="password"
                  value={data.password}
                  onChange={e => setData('password', e.target.value)}
                  placeholder="Leave blank to keep existing"
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* ── Sender Identity ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Sender Identity</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="From Name" error={errors.from_name}>
                <Input
                  value={data.from_name}
                  onChange={e => setData('from_name', e.target.value)}
                  placeholder="Your Company Name"
                />
              </Field>
              <Field label="From Email" error={errors.from_email}>
                <Input
                  type="email"
                  value={data.from_email}
                  onChange={e => setData('from_email', e.target.value)}
                  placeholder="noreply@example.com"
                  leftIcon="mail"
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* ── Test Connection ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Test Connection</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              {testResult && (
                <Alert intent={testResult.ok ? 'success' : 'danger'} title={testResult.message} />
              )}
              <HStack gap={3} align="end" wrap>
                <Field label="Recipient Email">
                  <Input
                    type="email"
                    value={testEmail}
                    onChange={e => setTestEmail(e.target.value)}
                    placeholder="you@example.com"
                    leftIcon="mail"
                  />
                </Field>
                <Button
                  type="button"
                  intent="soft"
                  loading={testLoading}
                  onClick={handleTest}
                >
                  Send Test
                </Button>
              </HStack>
            </VStack>
          </CardBody>
        </Card>
      </SettingsSection>
    </form>
  );
}

MailSettings.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="mail">{page}</SettingsLayout>
  </App>
);
