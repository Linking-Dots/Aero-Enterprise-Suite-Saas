/**
 * System Settings — catch-all configuration page for metadata,
 * advanced settings, integrations, and notification channels.
 *
 * Uses Inertia props from SystemSettingController::index().
 */
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Textarea, Toggle,
  Button, Badge,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function SystemSettings({ systemSettings }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.system_settings.edit');

  const org = systemSettings?.organization ?? {};

  const form = useForm({
    company_name: org.company_name ?? '',
    legal_name: org.legal_name ?? '',
    tagline: org.tagline ?? '',
    contact_person: org.contact_person ?? '',
    support_email: org.support_email ?? '',
    support_phone: org.support_phone ?? '',
    website_url: org.website_url ?? '',
    timezone: org.timezone ?? '',
    currency: org.currency ?? '',
    default_dark_mode: org.default_dark_mode ?? false,
    address_line1: org.address_line1 ?? '',
    address_line2: org.address_line2 ?? '',
    city: org.city ?? '',
    state: org.state ?? '',
    postal_code: org.postal_code ?? '',
    country: org.country ?? '',

    metadata: {
      seo_title: systemSettings?.metadata?.seo_title ?? '',
      seo_description: systemSettings?.metadata?.seo_description ?? '',
      seo_keywords: systemSettings?.metadata?.seo_keywords ?? [],
      default_locale: systemSettings?.metadata?.default_locale ?? 'en',
      show_help_center: systemSettings?.metadata?.show_help_center ?? true,
      enable_public_pages: systemSettings?.metadata?.enable_public_pages ?? false,
    },

    advanced: {
      maintenance_mode: systemSettings?.advanced?.maintenance_mode ?? false,
      session_timeout: systemSettings?.advanced?.session_timeout ?? 30,
    },

    integrations: {
      slack_webhook: systemSettings?.integrations?.slack_webhook ?? '',
      teams_webhook: systemSettings?.integrations?.teams_webhook ?? '',
      statuspage_url: systemSettings?.integrations?.statuspage_url ?? '',
    },

    notification_channels: {
      email: systemSettings?.notification_channels?.email ?? true,
      sms: systemSettings?.notification_channels?.sms ?? false,
      slack: systemSettings?.notification_channels?.slack ?? false,
    },
  });

  const handleSave = () => {
    form.put(route('core.settings.system.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('System settings updated.'),
      onError: () => toast.error('Failed to update system settings.'),
    });
  };

  return (
    <FormPageLayout
      title="System Settings"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'System Settings' },
      ]}
      description="Manage organization details, metadata, integrations, and system preferences."
      actions={
        canEdit && (
          <HStack gap={3}>
            <Button variant="secondary" onClick={() => form.reset()}>Reset</Button>
            <Button variant="primary" onClick={handleSave} loading={form.processing}>
              Save Changes
            </Button>
          </HStack>
        )
      }
    >
      <VStack gap={6}>

        {/* ── Organization Summary (read-only display) ── */}
        <Card>
          <CardHeader>
            <HStack gap={2} align="center">
              <Text weight="semibold">Organization</Text>
              <Badge intent="neutral" size="sm">Read-only</Badge>
            </HStack>
          </CardHeader>
          <CardBody>
            <VStack gap={3}>
              <Text size="sm"><strong>Company:</strong> {org.company_name || '—'}</Text>
              <Text size="sm"><strong>Legal Name:</strong> {org.legal_name || '—'}</Text>
              <Text size="sm"><strong>Contact:</strong> {org.contact_person || '—'}</Text>
              <Text size="sm"><strong>Email:</strong> {org.support_email || '—'}</Text>
              <Text size="sm"><strong>Phone:</strong> {org.support_phone || '—'}</Text>
              <Text size="sm"><strong>Address:</strong> {[org.address_line1, org.city, org.state, org.postal_code, org.country].filter(Boolean).join(', ') || '—'}</Text>
              <Text size="sm"><strong>Timezone:</strong> {org.timezone || '—'}</Text>
              <Text size="sm"><strong>Currency:</strong> {org.currency || '—'}</Text>
            </VStack>
          </CardBody>
        </Card>

        {/* ── Metadata ── */}
        <Card>
          <CardHeader><Text weight="semibold">Metadata & SEO</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="SEO Title">
                <Input
                  value={form.data.metadata.seo_title}
                  onChange={e => form.setData('metadata', { ...form.data.metadata, seo_title: e.target.value })}
                />
                {form.errors['metadata.seo_title'] && <Text intent="danger" size="sm">{form.errors['metadata.seo_title']}</Text>}
              </Field>
              <Field label="SEO Description">
                <Textarea
                  value={form.data.metadata.seo_description}
                  onChange={e => form.setData('metadata', { ...form.data.metadata, seo_description: e.target.value })}
                  rows={3}
                />
                {form.errors['metadata.seo_description'] && <Text intent="danger" size="sm">{form.errors['metadata.seo_description']}</Text>}
              </Field>
              <Field label="Default Locale">
                <Input
                  value={form.data.metadata.default_locale}
                  onChange={e => form.setData('metadata', { ...form.data.metadata, default_locale: e.target.value })}
                  placeholder="e.g. en"
                />
                {form.errors['metadata.default_locale'] && <Text intent="danger" size="sm">{form.errors['metadata.default_locale']}</Text>}
              </Field>
              <HStack gap={4} wrap>
                <Toggle
                  checked={!!form.data.metadata.show_help_center}
                  onChange={e => form.setData('metadata', { ...form.data.metadata, show_help_center: e.target.checked })}
                >
                  Show Help Center
                </Toggle>
                <Toggle
                  checked={!!form.data.metadata.enable_public_pages}
                  onChange={e => form.setData('metadata', { ...form.data.metadata, enable_public_pages: e.target.checked })}
                >
                  Enable Public Pages
                </Toggle>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* ── Advanced ── */}
        <Card>
          <CardHeader><Text weight="semibold">Advanced</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Toggle
                checked={!!form.data.advanced.maintenance_mode}
                onChange={e => form.setData('advanced', { ...form.data.advanced, maintenance_mode: e.target.checked })}
              >
                Maintenance Mode
              </Toggle>
              <Field label="Session Timeout (minutes)" style={{ maxWidth: 200 }}>
                <Input
                  type="number"
                  value={form.data.advanced.session_timeout}
                  onChange={e => form.setData('advanced', { ...form.data.advanced, session_timeout: Number(e.target.value) })}
                  min={5}
                  max={720}
                />
                {form.errors['advanced.session_timeout'] && <Text intent="danger" size="sm">{form.errors['advanced.session_timeout']}</Text>}
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* ── Integrations ── */}
        <Card>
          <CardHeader><Text weight="semibold">Integrations</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Slack Webhook URL">
                <Input
                  type="url"
                  value={form.data.integrations.slack_webhook}
                  onChange={e => form.setData('integrations', { ...form.data.integrations, slack_webhook: e.target.value })}
                  placeholder="https://hooks.slack.com/services/..."
                />
                {form.errors['integrations.slack_webhook'] && <Text intent="danger" size="sm">{form.errors['integrations.slack_webhook']}</Text>}
              </Field>
              <Field label="Teams Webhook URL">
                <Input
                  type="url"
                  value={form.data.integrations.teams_webhook}
                  onChange={e => form.setData('integrations', { ...form.data.integrations, teams_webhook: e.target.value })}
                  placeholder="https://outlook.office.com/webhook/..."
                />
                {form.errors['integrations.teams_webhook'] && <Text intent="danger" size="sm">{form.errors['integrations.teams_webhook']}</Text>}
              </Field>
              <Field label="Statuspage URL">
                <Input
                  type="url"
                  value={form.data.integrations.statuspage_url}
                  onChange={e => form.setData('integrations', { ...form.data.integrations, statuspage_url: e.target.value })}
                  placeholder="https://status.example.com"
                />
                {form.errors['integrations.statuspage_url'] && <Text intent="danger" size="sm">{form.errors['integrations.statuspage_url']}</Text>}
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {/* ── Notification Channels ── */}
        <Card>
          <CardHeader><Text weight="semibold">Notification Channels</Text></CardHeader>
          <CardBody>
            <HStack gap={4} wrap>
              <Toggle
                checked={!!form.data.notification_channels.email}
                onChange={e => form.setData('notification_channels', { ...form.data.notification_channels, email: e.target.checked })}
              >
                Email
              </Toggle>
              <Toggle
                checked={!!form.data.notification_channels.sms}
                onChange={e => form.setData('notification_channels', { ...form.data.notification_channels, sms: e.target.checked })}
              >
                SMS
              </Toggle>
              <Toggle
                checked={!!form.data.notification_channels.slack}
                onChange={e => form.setData('notification_channels', { ...form.data.notification_channels, slack: e.target.checked })}
              >
                Slack
              </Toggle>
            </HStack>
          </CardBody>
        </Card>

        {/* ── Test Actions ── */}
        <TestActions />

      </VStack>
    </FormPageLayout>
  );
}

/* ── Test Actions (Email + SMS) ───────────────────────────────────── */
function TestActions() {
  const toast = useToast();
  const [testEmail, setTestEmail] = useState('');
  const [emailLoading, setEmailLoading] = useState(false);
  const [testPhone, setTestPhone] = useState('');
  const [smsLoading, setSmsLoading] = useState(false);

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const sendTestEmail = async () => {
    if (!testEmail) return;
    setEmailLoading(true);
    try {
      const res = await fetch(route('core.settings.system.test-email'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ email: testEmail }),
      });
      const data = await res.json();
      if (res.ok && data.success) {
        toast.success(data.message || 'Test email sent.');
      } else {
        toast.error(data.message || 'Failed to send test email.');
      }
    } catch {
      toast.error('Network error while sending test email.');
    } finally {
      setEmailLoading(false);
    }
  };

  const sendTestSms = async () => {
    if (!testPhone) return;
    setSmsLoading(true);
    try {
      const res = await fetch(route('core.settings.system.test-sms'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ phone: testPhone }),
      });
      const data = await res.json();
      if (res.ok && data.success) {
        toast.success(data.message || 'Test SMS sent.');
      } else {
        toast.error(data.message || 'Failed to send test SMS.');
      }
    } catch {
      toast.error('Network error while sending test SMS.');
    } finally {
      setSmsLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader><Text weight="semibold">Test Actions</Text></CardHeader>
      <CardBody>
        <VStack gap={4}>
          <HStack gap={3} align="end" wrap>
            <Field label="Test Email Address" style={{ flex: 1, minWidth: 200 }}>
              <Input
                type="email"
                value={testEmail}
                onChange={e => setTestEmail(e.target.value)}
                placeholder="admin@example.com"
              />
            </Field>
            <Button variant="secondary" onClick={sendTestEmail} loading={emailLoading}>
              Send Test Email
            </Button>
          </HStack>
          <HStack gap={3} align="end" wrap>
            <Field label="Test Phone Number" style={{ flex: 1, minWidth: 200 }}>
              <Input
                type="tel"
                value={testPhone}
                onChange={e => setTestPhone(e.target.value)}
                placeholder="+1 555 123 4567"
              />
            </Field>
            <Button variant="secondary" onClick={sendTestSms} loading={smsLoading}>
              Send Test SMS
            </Button>
          </HStack>
        </VStack>
      </CardBody>
    </Card>
  );
}

SystemSettings.layout = page => (
  <App title="System Settings">{page}</App>
);
