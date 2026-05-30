import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, HStack, Box, Field, Input, Select, Button, Badge, Eyebrow } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

export default function Settings({ mode, savedSettings, timezones = [] }) {
  const { errors } = usePage().props;

  const nameKey         = mode === 'saas' ? 'site_name'        : 'company_name';
  const nameLabel       = mode === 'saas' ? 'Platform Name'    : 'Company Name';
  const namePlaceholder = mode === 'saas' ? 'My SaaS Platform' : 'Acme Corporation';
  const saveRoute       = mode === 'saas' ? IR.savePlatform    : IR.saveSettings;

  const [form, setForm] = useState({
    [nameKey]:     savedSettings?.[nameKey]     ?? '',
    support_email: savedSettings?.support_email ?? '',
    app_url:       savedSettings?.app_url       ?? (typeof window !== 'undefined' ? window.location.origin : ''),
    timezone:      savedSettings?.timezone      ?? 'UTC',
    ...(mode !== 'saas' ? {
      mail_host:       savedSettings?.mail_host       ?? '',
      mail_port:       savedSettings?.mail_port       ?? 587,
      mail_username:   savedSettings?.mail_username   ?? '',
      mail_password:   savedSettings?.mail_password   ?? '',
      mail_from_name:  savedSettings?.mail_from_name  ?? '',
      mail_encryption: savedSettings?.mail_encryption ?? 'tls',
    } : {}),
  });

  const [saving, setSaving] = useState(false);
  const [saved, setSaved]   = useState(!!savedSettings);

  function set(key, val) { setForm(f => ({ ...f, [key]: val })); setSaved(false); }

  function save() {
    setSaving(true);
    router.post(saveRoute, form, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => setSaved(true),
      onFinish:  () => setSaving(false),
    });
  }

  const tzOptions = timezones.length > 0
    ? timezones.map(t => ({ value: t, label: t }))
    : [{ value: 'UTC', label: 'UTC' }];

  return (
    <VStack gap={5}>
      <div>
        <h1 className="il-title">{mode === 'saas' ? 'Platform Settings' : 'System Settings'}</h1>
        <p className="il-desc">Configure basic settings for your {mode === 'saas' ? 'SaaS platform' : 'enterprise installation'}.</p>
      </div>

      <HStack gap={3}>
        <Box grow className="aeos-flex-1">
          <Field label={nameLabel} htmlFor="name_key" error={errors[nameKey]} required>
            <Input
              id="name_key"
              value={form[nameKey]}
              onChange={e => set(nameKey, e.target.value)}
              leftIcon="home"
              placeholder={namePlaceholder}
              error={!!errors[nameKey]}
            />
          </Field>
        </Box>
        <Box grow className="aeos-flex-1">
          <Field label="Support Email" htmlFor="support_email" error={errors.support_email} required>
            <Input
              id="support_email"
              type="email"
              value={form.support_email}
              onChange={e => set('support_email', e.target.value)}
              leftIcon="mail"
              placeholder="support@company.com"
              error={!!errors.support_email}
            />
          </Field>
        </Box>
      </HStack>

      <HStack gap={3}>
        <Box grow className="aeos-flex-1">
          <Field label="Application URL" htmlFor="app_url" error={errors.app_url} required>
            <Input
              id="app_url"
              value={form.app_url}
              onChange={e => set('app_url', e.target.value)}
              leftIcon="link"
              placeholder="https://app.company.com"
              error={!!errors.app_url}
            />
          </Field>
        </Box>
        <Box className="aeos-w-200">
          <Field label="Timezone" htmlFor="timezone" required>
            <Select
              id="timezone"
              value={form.timezone}
              onChange={e => set('timezone', e.target.value)}
              options={tzOptions}
            />
          </Field>
        </Box>
      </HStack>

      {mode !== 'saas' && (
        <VStack gap={3}>
          <Eyebrow tone="primary">Email (SMTP)</Eyebrow>
          <HStack gap={3} align="flex-end">
            <Box grow className="aeos-flex-1">
              <Field label="SMTP Host" htmlFor="mail_host">
                <Input id="mail_host" value={form.mail_host} onChange={e => set('mail_host', e.target.value)} leftIcon="mail" placeholder="smtp.mailgun.org" />
              </Field>
            </Box>
            <Box className="aeos-w-100">
              <Field label="Port" htmlFor="mail_port">
                <Input id="mail_port" type="number" value={form.mail_port} onChange={e => set('mail_port', e.target.value)} />
              </Field>
            </Box>
          </HStack>
          <HStack gap={3}>
            <Box grow className="aeos-flex-1">
              <Field label="SMTP Username" htmlFor="mail_user">
                <Input id="mail_user" value={form.mail_username} onChange={e => set('mail_username', e.target.value)} leftIcon="user" />
              </Field>
            </Box>
            <Box grow className="aeos-flex-1">
              <Field label="SMTP Password" htmlFor="mail_pass">
                <Input id="mail_pass" type="password" value={form.mail_password} onChange={e => set('mail_password', e.target.value)} leftIcon="settings" />
              </Field>
            </Box>
          </HStack>
        </VStack>
      )}

      <HStack gap={2}>
        <Button intent="soft" loading={saving} onClick={save}>Save Settings</Button>
        {saved && <Badge intent="success">Saved</Badge>}
      </HStack>

      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(IR.database)}>Back</Button>
        <Button intent="primary" rightIcon="arrowRight" disabled={!saved} onClick={() => router.get(IR.admin)}>Continue</Button>
      </div>
    </VStack>
  );
}

Settings.layout = page => (
  <InstallLayout
    title="Settings"
    step={page.props.mode === 'saas' ? 3 : 4}
    steps={page.props.mode === 'saas' ? STEPS_SAAS : STEPS_STANDALONE}
    mode={page.props.mode}
  >
    {page}
  </InstallLayout>
);
