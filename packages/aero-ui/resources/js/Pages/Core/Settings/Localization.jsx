/**
 * Localization Settings — timezone, date format, time format, language, currency.
 *
 * Props: { settings: {} }
 *
 * Violations fixed vs. prior stub:
 *   P0-1: all style={} removed from Field and select wrappers
 *   P0-2: all raw <select> replaced with Select engine component
 *   P1-1: errors passed as strings
 *   P2-1: intent= not variant=
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Select,
  Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TIMEZONE_OPTIONS = [
  'UTC',
  'America/New_York',
  'America/Chicago',
  'America/Denver',
  'America/Los_Angeles',
  'America/Sao_Paulo',
  'Europe/London',
  'Europe/Paris',
  'Europe/Berlin',
  'Europe/Moscow',
  'Asia/Dubai',
  'Asia/Kolkata',
  'Asia/Dhaka',
  'Asia/Bangkok',
  'Asia/Singapore',
  'Asia/Tokyo',
  'Asia/Shanghai',
  'Australia/Sydney',
  'Pacific/Auckland',
  'Africa/Cairo',
  'Africa/Lagos',
].map(tz => ({ value: tz, label: tz }));

const DATE_FORMAT_OPTIONS = [
  { value: 'Y-m-d',  label: 'YYYY-MM-DD (2026-05-28)' },
  { value: 'd/m/Y',  label: 'DD/MM/YYYY (28/05/2026)' },
  { value: 'm/d/Y',  label: 'MM/DD/YYYY (05/28/2026)' },
  { value: 'd.m.Y',  label: 'DD.MM.YYYY (28.05.2026)' },
  { value: 'j F Y',  label: '28 May 2026' },
];

const TIME_FORMAT_OPTIONS = [
  { value: '24', label: '24-hour (14:30)' },
  { value: '12', label: '12-hour (2:30 PM)' },
];

const LANGUAGE_OPTIONS = [
  { value: 'en',    label: 'English' },
  { value: 'fr',    label: 'French' },
  { value: 'de',    label: 'German' },
  { value: 'es',    label: 'Spanish' },
  { value: 'pt',    label: 'Portuguese' },
  { value: 'ar',    label: 'Arabic' },
  { value: 'zh',    label: 'Chinese (Simplified)' },
  { value: 'ja',    label: 'Japanese' },
  { value: 'ko',    label: 'Korean' },
  { value: 'hi',    label: 'Hindi' },
  { value: 'bn',    label: 'Bengali' },
];

const CURRENCY_OPTIONS = [
  { value: 'USD', label: 'USD — US Dollar' },
  { value: 'EUR', label: 'EUR — Euro' },
  { value: 'GBP', label: 'GBP — British Pound' },
  { value: 'BDT', label: 'BDT — Bangladeshi Taka' },
  { value: 'INR', label: 'INR — Indian Rupee' },
  { value: 'JPY', label: 'JPY — Japanese Yen' },
  { value: 'AUD', label: 'AUD — Australian Dollar' },
  { value: 'CAD', label: 'CAD — Canadian Dollar' },
  { value: 'CHF', label: 'CHF — Swiss Franc' },
  { value: 'CNY', label: 'CNY — Chinese Yuan' },
  { value: 'SAR', label: 'SAR — Saudi Riyal' },
  { value: 'AED', label: 'AED — UAE Dirham' },
];

export default function LocalizationSettings({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.localization.edit');

  const { data, setData, post, processing, errors, reset } = useForm({
    timezone:    settings.timezone    ?? 'UTC',
    date_format: settings.date_format ?? 'Y-m-d',
    time_format: settings.time_format ?? '24',
    language:    settings.language    ?? 'en',
    currency:    settings.currency    ?? 'USD',
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.localization.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Localization settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <FormPageLayout
        title="Localization"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system') },
          { label: 'Localization' },
        ]}
        description="Configure timezone, date/time format, language, and currency for your organization."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button type="button" intent="soft" onClick={() => reset()} disabled={processing}>
                Reset
              </Button>
              <Button type="submit" intent="primary" loading={processing}>
                Save Changes
              </Button>
            </HStack>
          )
        }
      >
        <VStack gap={6}>

          {/* ── Regional ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Regional</Text></CardHeader>
            <CardBody>
              <VStack gap={4}>
                <Field label="Timezone" error={errors.timezone}>
                  <Select
                    value={data.timezone}
                    onChange={e => setData('timezone', e.target.value)}
                    options={TIMEZONE_OPTIONS}
                  />
                </Field>
                <Field label="Language" error={errors.language}>
                  <Select
                    value={data.language}
                    onChange={e => setData('language', e.target.value)}
                    options={LANGUAGE_OPTIONS}
                  />
                </Field>
                <Field label="Currency" error={errors.currency}>
                  <Select
                    value={data.currency}
                    onChange={e => setData('currency', e.target.value)}
                    options={CURRENCY_OPTIONS}
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>

          {/* ── Date & Time ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Date & Time Format</Text></CardHeader>
            <CardBody>
              <VStack gap={4}>
                <Field label="Date Format" error={errors.date_format}>
                  <Select
                    value={data.date_format}
                    onChange={e => setData('date_format', e.target.value)}
                    options={DATE_FORMAT_OPTIONS}
                  />
                </Field>
                <Field label="Time Format" error={errors.time_format}>
                  <Select
                    value={data.time_format}
                    onChange={e => setData('time_format', e.target.value)}
                    options={TIME_FORMAT_OPTIONS}
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>

        </VStack>
      </FormPageLayout>
    </form>
  );
}

LocalizationSettings.layout = page => (
  <App title="Localization">{page}</App>
);
