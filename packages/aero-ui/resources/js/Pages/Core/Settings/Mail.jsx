/**
 * Email (SMTP) Settings — Tenant mail configuration page.
 *
 * Uses @aero/ui FormPageLayout with fields for SMTP host, port,
 * encryption, credentials, and from-address. Includes a test-email action.
 */
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
  FormPageLayout,
  Field, Input, Button, Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

function Section({ title, children }) {
  return (
    <Card>
      <CardHeader>
        <Text weight="semibold">{title}</Text>
      </CardHeader>
      <CardBody>
        <VStack gap={4}>
          {children}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function MailSettings({ emailSettings }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.mail_settings.update');
  const [showPassword, setShowPassword] = useState(false);
  const [testEmail, setTestEmail] = useState('');
  const [sendingTest, setSendingTest] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    host: emailSettings?.host ?? '',
    port: emailSettings?.port ?? '',
    encryption: emailSettings?.encryption ?? 'tls',
    username: emailSettings?.username ?? '',
    password: '',
    from_address: emailSettings?.from_address ?? '',
    from_name: emailSettings?.from_name ?? '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.settings.mail.update'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Mail settings updated successfully.');
      },
      onError: () => {
        toast.error('Failed to update mail settings. Please check the form.');
      },
    });
  };

  const handleSendTest = async (e) => {
    e.preventDefault();
    if (!testEmail) {
      toast.error('Please enter a recipient email address.');
      return;
    }

    setSendingTest(true);
    try {
      const response = await fetch(route('core.settings.mail.test'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ email: testEmail }),
      });

      const result = await response.json();

      if (response.ok && result.success) {
        toast.success(result.message || 'Test email sent successfully.');
      } else {
        toast.error(result.message || 'Failed to send test email.');
      }
    } catch {
      toast.error('An error occurred while sending the test email.');
    } finally {
      setSendingTest(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="Email (SMTP) Settings"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Email (SMTP) Settings' },
        ]}
        description="Configure your tenant's outgoing email server (SMTP) settings and sender identity."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button
                type="button"
                variant="secondary"
                onClick={() => reset()}
                disabled={processing}
              >
                Reset
              </Button>
              <Button
                type="submit"
                variant="primary"
                loading={processing}
              >
                Save Changes
              </Button>
            </HStack>
          )
        }
      >
        <VStack gap={6}>
          <Section title="SMTP Server">
            <Field label="SMTP Host" error={errors.host}>
              <Input
                value={data.host}
                onChange={e => setData('host', e.target.value)}
                placeholder="e.g. smtp.example.com"
              />
            </Field>

            <HStack gap={4}>
              <Field label="Port" error={errors.port} style={{ flex: 1 }}>
                <Input
                  type="number"
                  value={data.port}
                  onChange={e => setData('port', e.target.value)}
                  placeholder="587"
                  min={1}
                  max={65535}
                />
              </Field>

              <Field label="Encryption" error={errors.encryption} style={{ flex: 1 }}>
                <select
                  value={data.encryption}
                  onChange={e => setData('encryption', e.target.value)}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    borderRadius: 4,
                    border: '1px solid var(--border)',
                    background: 'var(--surface)',
                    color: 'var(--text)',
                  }}
                >
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                  <option value="none">None</option>
                </select>
              </Field>
            </HStack>
          </Section>

          <Section title="Authentication">
            <Field label="Username" error={errors.username}>
              <Input
                value={data.username}
                onChange={e => setData('username', e.target.value)}
                placeholder="SMTP username"
              />
            </Field>

            <Field label="Password" error={errors.password}>
              <HStack gap={2} align="center">
                <Input
                  type={showPassword ? 'text' : 'password'}
                  value={data.password}
                  onChange={e => setData('password', e.target.value)}
                  placeholder={emailSettings?.password_set ? 'Password is set (leave blank to keep)' : 'SMTP password'}
                  style={{ flex: 1 }}
                />
                <Button
                  type="button"
                  variant="secondary"
                  size="sm"
                  onClick={() => setShowPassword(v => !v)}
                >
                  {showPassword ? 'Hide' : 'Show'}
                </Button>
              </HStack>
              {emailSettings?.password_set && !data.password && (
                <Text size="sm" color="muted" style={{ marginTop: 4 }}>
                  A password is already saved. Enter a new one to replace it.
                </Text>
              )}
            </Field>
          </Section>

          <Section title="Sender Identity">
            <Field label="From Address" error={errors.from_address}>
              <Input
                type="email"
                value={data.from_address}
                onChange={e => setData('from_address', e.target.value)}
                placeholder="noreply@example.com"
              />
            </Field>

            <Field label="From Name" error={errors.from_name}>
              <Input
                value={data.from_name}
                onChange={e => setData('from_name', e.target.value)}
                placeholder="Your Company Name"
              />
            </Field>
          </Section>

          <Section title="Test Connection">
            <HStack gap={3} align="end">
              <Field label="Recipient Email" style={{ flex: 1 }}>
                <Input
                  type="email"
                  value={testEmail}
                  onChange={e => setTestEmail(e.target.value)}
                  placeholder="recipient@example.com"
                />
              </Field>
              <Button
                type="button"
                variant="secondary"
                onClick={handleSendTest}
                loading={sendingTest}
              >
                Send Test Email
              </Button>
            </HStack>
          </Section>
        </VStack>
      </FormPageLayout>
    </form>
  );
}

MailSettings.layout = page => (
  <App title="Email (SMTP) Settings">{page}</App>
);
