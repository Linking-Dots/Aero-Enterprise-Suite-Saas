/**
 * Notification Channels (CA-3) — admin UI to enable / configure delivery
 * channels (Email, SMS, Push, In-App) and trigger test notifications.
 */
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
  FormPageLayout,
  Card,
  Field, Input, Select, Button, Badge, Toggle,
  HStack, VStack, Text,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SMS_PROVIDER_OPTIONS = [
  { value: 'twilio',          label: 'Twilio' },
  { value: 'vonage',          label: 'Vonage' },
  { value: 'africas_talking', label: "Africa's Talking" },
  { value: 'log',             label: 'Log (dev only)' },
];

function ChannelCard({ label, badge, badgeIntent, enabled, onToggle, canConfigure, children, onTest, testing, canTest, testLabel }) {
  return (
    <Card>
      <VStack gap={4}>
        <HStack gap={3} align="center">
          <Badge intent={badgeIntent ?? (enabled ? 'success' : 'neutral')}>{badge}</Badge>
          <Text size="md">{label}</Text>
          <div className="ch-spacer" />
          <Toggle
            label={enabled ? 'Enabled' : 'Disabled'}
            checked={enabled}
            onChange={v => canConfigure && onToggle(v)}
          />
        </HStack>

        {enabled && (
          <VStack gap={3}>
            {children}
            {onTest && canTest && (
              <HStack gap={2}>
                <Button
                  type="button"
                  intent="soft"
                  size="sm"
                  loading={testing}
                  onClick={onTest}
                >
                  {testLabel}
                </Button>
              </HStack>
            )}
          </VStack>
        )}
      </VStack>
    </Card>
  );
}

export default function NotificationChannels({ settings = {} }) {
  const toast = useToast();
  const canConfigure = useHRMAC('core.notifications.channels.configure');
  const canTest      = useHRMAC('core.notifications.channels.test');
  const [testing, setTesting] = useState(null);

  const { data, setData, post, processing, errors } = useForm({
    email_enabled:  settings.email_enabled  ?? true,
    sms_enabled:    settings.sms_enabled    ?? false,
    push_enabled:   settings.push_enabled   ?? false,
    inapp_enabled:  settings.inapp_enabled  ?? true,
    sms_provider:   settings.sms_provider   ?? 'twilio',
    sms_api_key:    '',
    sms_from:       settings.sms_from       ?? '',
    push_fcm_key:   '',
    push_vapid_pub: settings.push_vapid_pub ?? '',
  });

  const submit = (e) => {
    e.preventDefault();
    post(route('admin.notifications.channels.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Notification channels updated.'),
      onError:   () => toast.error('Failed to save channel settings.'),
    });
  };

  const sendTest = (channel) => {
    if (!canTest) return;
    setTesting(channel);
    router.post(
      route('admin.notifications.channels.test'),
      { channel },
      {
        preserveScroll: true,
        onSuccess: () => toast.success(`Test ${channel} notification queued.`),
        onError:   () => toast.error(`Failed to send test ${channel} notification.`),
        onFinish:  () => setTesting(null),
      },
    );
  };

  return (
    <>
      <style>{`
        .ch-spacer { flex: 1; }
      `}</style>

      <form onSubmit={submit}>
        <FormPageLayout
          title="Notification Channels"
          breadcrumb={[
            { label: 'Settings', href: route('core.settings.system.index') },
            { label: 'Notification Channels' },
          ]}
          description="Configure how notifications are delivered to users."
          actions={
            canConfigure && (
              <Button type="submit" intent="primary" loading={processing}>
                Save Channel Settings
              </Button>
            )
          }
        >
          <VStack gap={4}>

            {/* ── Email ──────────────────────────────────────── */}
            <ChannelCard
              label="Email (SMTP)"
              badge="Email"
              enabled={data.email_enabled}
              onToggle={v => setData('email_enabled', v)}
              canConfigure={canConfigure}
              onTest={() => sendTest('email')}
              testing={testing === 'email'}
              canTest={canTest}
              testLabel="Send Test Email"
            >
              <Alert intent="info" title="SMTP is configured globally.">
                To change the outbound mail server, visit{' '}
                <a className="aeos-link" href={route('core.settings.mail.index')}>
                  Settings &rsaquo; Email
                </a>.
              </Alert>
            </ChannelCard>

            {/* ── SMS ────────────────────────────────────────── */}
            <ChannelCard
              label="SMS"
              badge="SMS"
              enabled={data.sms_enabled}
              onToggle={v => setData('sms_enabled', v)}
              canConfigure={canConfigure}
              onTest={() => sendTest('sms')}
              testing={testing === 'sms'}
              canTest={canTest}
              testLabel="Send Test SMS"
            >
              <Field label="SMS Provider" htmlFor="sms_provider" error={errors.sms_provider}>
                <Select
                  options={SMS_PROVIDER_OPTIONS}
                  value={data.sms_provider}
                  onChange={e => setData('sms_provider', e.target.value)}
                />
              </Field>
              <Field label="API Key" htmlFor="sms_api_key" error={errors.sms_api_key}>
                <Input
                  id="sms_api_key"
                  type="password"
                  value={data.sms_api_key}
                  onChange={e => setData('sms_api_key', e.target.value)}
                  placeholder="Leave blank to keep existing"
                  error={Boolean(errors.sms_api_key)}
                />
              </Field>
              <Field label="From Number / Sender ID" htmlFor="sms_from" error={errors.sms_from}>
                <Input
                  id="sms_from"
                  value={data.sms_from}
                  onChange={e => setData('sms_from', e.target.value)}
                  placeholder="e.g. +15551234567"
                  error={Boolean(errors.sms_from)}
                />
              </Field>
            </ChannelCard>

            {/* ── Push ───────────────────────────────────────── */}
            <ChannelCard
              label="Push Notifications (FCM)"
              badge="Push"
              enabled={data.push_enabled}
              onToggle={v => setData('push_enabled', v)}
              canConfigure={canConfigure}
              onTest={() => sendTest('push')}
              testing={testing === 'push'}
              canTest={canTest}
              testLabel="Send Test Push"
            >
              <Field label="FCM Server Key" htmlFor="push_fcm_key" error={errors.push_fcm_key}>
                <Input
                  id="push_fcm_key"
                  type="password"
                  value={data.push_fcm_key}
                  onChange={e => setData('push_fcm_key', e.target.value)}
                  placeholder="Leave blank to keep existing"
                  error={Boolean(errors.push_fcm_key)}
                />
              </Field>
              <Field label="VAPID Public Key" htmlFor="push_vapid_pub" error={errors.push_vapid_pub}>
                <Input
                  id="push_vapid_pub"
                  value={data.push_vapid_pub}
                  onChange={e => setData('push_vapid_pub', e.target.value)}
                  error={Boolean(errors.push_vapid_pub)}
                />
              </Field>
            </ChannelCard>

            {/* ── In-App ─────────────────────────────────────── */}
            <ChannelCard
              label="In-App"
              badge="In-App"
              enabled={data.inapp_enabled}
              onToggle={v => setData('inapp_enabled', v)}
              canConfigure={canConfigure}
              onTest={() => sendTest('inapp')}
              testing={testing === 'inapp'}
              canTest={canTest}
              testLabel="Send Test In-App"
            >
              <Text tone="secondary">
                In-app notifications appear in the notification bell. No extra configuration required.
              </Text>
            </ChannelCard>

          </VStack>
        </FormPageLayout>
      </form>
    </>
  );
}

NotificationChannels.layout = page => (
  <App title="Notification Channels">{page}</App>
);
