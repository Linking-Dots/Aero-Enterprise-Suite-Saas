import { useForm } from '@inertiajs/react';
import {
  Field,
  Input,
  Select,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsRail from './SettingsRail.jsx';
import SettingsSection from '@/components/settings/SettingsSection.jsx';

const TIMEZONE_OPTIONS = [
  { value: 'UTC',            label: 'UTC' },
  { value: 'America/New_York',  label: 'America/New_York' },
  { value: 'America/Chicago',   label: 'America/Chicago' },
  { value: 'America/Denver',    label: 'America/Denver' },
  { value: 'America/Los_Angeles',label: 'America/Los_Angeles' },
  { value: 'Europe/London',     label: 'Europe/London' },
  { value: 'Europe/Paris',      label: 'Europe/Paris' },
  { value: 'Asia/Dhaka',        label: 'Asia/Dhaka' },
  { value: 'Asia/Kolkata',      label: 'Asia/Kolkata' },
  { value: 'Asia/Tokyo',        label: 'Asia/Tokyo' },
  { value: 'Asia/Singapore',    label: 'Asia/Singapore' },
  { value: 'Australia/Sydney',  label: 'Australia/Sydney' },
];

const DATE_FORMAT_OPTIONS = [
  { value: 'Y-m-d',  label: 'YYYY-MM-DD (ISO)' },
  { value: 'd-m-Y',  label: 'DD-MM-YYYY' },
  { value: 'm/d/Y',  label: 'MM/DD/YYYY' },
  { value: 'd/m/Y',  label: 'DD/MM/YYYY' },
  { value: 'M j, Y', label: 'Mon DD, YYYY' },
];

export default function General({ settings }) {
  const toast  = useToast();
  const canEdit = useHRMAC('system-settings.general-settings.edit');

  const form = useForm({
    site_name:     settings?.site_name     ?? '',
    legal_name:    settings?.legal_name    ?? '',
    tagline:       settings?.tagline       ?? '',
    support_email: settings?.support_email ?? '',
    support_phone: settings?.support_phone ?? '',
    marketing_url: settings?.marketing_url ?? '',
    timezone:      settings?.timezone      ?? 'UTC',
    date_format:   settings?.date_format   ?? 'Y-m-d',
    currency:      settings?.currency      ?? 'USD',
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.put(route('platform.admin.settings.general.update'), {
      onSuccess: () => toast.success('General settings saved.'),
      onError:   () => toast.error('Failed to save settings.'),
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <SettingsSection
        title="General"
        description="Platform name, contact info, timezone and locale defaults."
        canEdit={canEdit}
        dirty={form.isDirty}
        processing={form.processing}
        onReset={() => form.reset()}
        onSave={handleSubmit}
      >

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Identity</Eyebrow>

              <Field label="Platform Name" htmlFor="site_name" error={form.errors.site_name} required>
                <Input
                  id="site_name"
                  value={form.data.site_name}
                  onChange={e => form.setData('site_name', e.target.value)}
                  placeholder="AEOS365"
                  error={form.errors.site_name}
                />
              </Field>

              <Field label="Legal Name" htmlFor="legal_name" error={form.errors.legal_name} hint="Appears in invoices and legal documents.">
                <Input
                  id="legal_name"
                  value={form.data.legal_name}
                  onChange={e => form.setData('legal_name', e.target.value)}
                  placeholder="Acme Technologies Ltd."
                  error={form.errors.legal_name}
                />
              </Field>

              <Field label="Tagline" htmlFor="tagline" error={form.errors.tagline}>
                <Input
                  id="tagline"
                  value={form.data.tagline}
                  onChange={e => form.setData('tagline', e.target.value)}
                  placeholder="Enterprise made simple."
                  error={form.errors.tagline}
                />
              </Field>

              <Field label="Marketing URL" htmlFor="marketing_url" error={form.errors.marketing_url}>
                <Input
                  id="marketing_url"
                  type="url"
                  value={form.data.marketing_url}
                  onChange={e => form.setData('marketing_url', e.target.value)}
                  placeholder="https://aeos365.com"
                  leftIcon="link"
                  error={form.errors.marketing_url}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Contact</Eyebrow>

              <HStack gap={4}>
                <Field label="Support Email" htmlFor="support_email" error={form.errors.support_email}>
                  <Input
                    id="support_email"
                    type="email"
                    value={form.data.support_email}
                    onChange={e => form.setData('support_email', e.target.value)}
                    placeholder="support@example.com"
                    leftIcon="mail"
                    error={form.errors.support_email}
                  />
                </Field>

                <Field label="Support Phone" htmlFor="support_phone" error={form.errors.support_phone}>
                  <Input
                    id="support_phone"
                    value={form.data.support_phone}
                    onChange={e => form.setData('support_phone', e.target.value)}
                    placeholder="+1-800-000-0000"
                    leftIcon="phone"
                    error={form.errors.support_phone}
                  />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Locale Defaults</Eyebrow>

              <HStack gap={4}>
                <Field label="Timezone" htmlFor="timezone" error={form.errors.timezone}>
                  <Select
                    id="timezone"
                    value={form.data.timezone}
                    onChange={e => form.setData('timezone', e.target.value)}
                    options={TIMEZONE_OPTIONS}
                  />
                </Field>

                <Field label="Date Format" htmlFor="date_format" error={form.errors.date_format}>
                  <Select
                    id="date_format"
                    value={form.data.date_format}
                    onChange={e => form.setData('date_format', e.target.value)}
                    options={DATE_FORMAT_OPTIONS}
                  />
                </Field>

                <Field label="Currency (ISO 4217)" htmlFor="currency" error={form.errors.currency} hint="e.g. USD, EUR, BDT">
                  <Input
                    id="currency"
                    value={form.data.currency}
                    onChange={e => form.setData('currency', e.target.value)}
                    placeholder="USD"
                    error={form.errors.currency}
                  />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

      </SettingsSection>
    </form>
  );
}

General.layout = page => (
  <App title="Platform Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="general">{page}</SettingsLayout>
  </App>
);
