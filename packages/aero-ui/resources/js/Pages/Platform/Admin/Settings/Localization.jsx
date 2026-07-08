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
  Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsRail from './SettingsRail.jsx';
import SettingsSection from '@/components/settings/SettingsSection.jsx';

const LOCALE_OPTIONS = [
  { value: 'en',    label: 'English (en)' },
  { value: 'en-US', label: 'English – US (en-US)' },
  { value: 'en-GB', label: 'English – GB (en-GB)' },
  { value: 'fr',    label: 'French (fr)' },
  { value: 'de',    label: 'German (de)' },
  { value: 'es',    label: 'Spanish (es)' },
  { value: 'pt',    label: 'Portuguese (pt)' },
  { value: 'ar',    label: 'Arabic (ar)' },
  { value: 'bn',    label: 'Bengali (bn)' },
  { value: 'zh',    label: 'Chinese (zh)' },
  { value: 'ja',    label: 'Japanese (ja)' },
  { value: 'ko',    label: 'Korean (ko)' },
];

const TIMEZONE_OPTIONS = [
  { value: 'UTC',               label: 'UTC' },
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

const FIRST_DAY_OPTIONS = [
  { value: '0', label: 'Sunday' },
  { value: '1', label: 'Monday' },
  { value: '6', label: 'Saturday' },
];

export default function Localization({ localization }) {
  const toast   = useToast();
  const canEdit = useHRMAC('system-settings.localization-settings.edit');

  const form = useForm({
    default_locale:    localization?.default_locale    ?? 'en',
    available_locales: (localization?.available_locales ?? ['en']).join(', '),
    timezone:          localization?.timezone          ?? 'UTC',
    date_format:       localization?.date_format       ?? 'Y-m-d',
    first_day_of_week: String(localization?.first_day_of_week ?? 1),
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.transform(d => ({
      ...d,
      available_locales: d.available_locales
        .split(',')
        .map(s => s.trim())
        .filter(Boolean),
      first_day_of_week: Number(d.first_day_of_week),
    })).put(route('platform.admin.settings.localization.update'), {
      onSuccess: () => toast.success('Localization saved.'),
      onError:   () => toast.error('Failed to save localization settings.'),
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <SettingsSection
        title="Localization"
        description="Default language, date format, timezone and calendar settings."
        canEdit={canEdit}
        dirty={form.isDirty}
        processing={form.processing}
        onReset={() => form.reset()}
        onSave={handleSubmit}
      >

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Language</Eyebrow>

              <Field label="Default Locale" htmlFor="default_locale" error={form.errors.default_locale} required>
                <Select
                  id="default_locale"
                  value={form.data.default_locale}
                  onChange={e => form.setData('default_locale', e.target.value)}
                  options={LOCALE_OPTIONS}
                />
              </Field>

              <Field
                label="Available Locales"
                htmlFor="available_locales"
                error={form.errors.available_locales}
                hint="Comma-separated locale codes shown to users, e.g. en, fr, de"
              >
                <Input
                  id="available_locales"
                  value={form.data.available_locales}
                  onChange={e => form.setData('available_locales', e.target.value)}
                  placeholder="en, fr, de"
                  error={form.errors.available_locales}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Date and Time</Eyebrow>

              <HStack gap={4}>
                <Field label="Timezone" htmlFor="timezone" error={form.errors.timezone} required>
                  <Select
                    id="timezone"
                    value={form.data.timezone}
                    onChange={e => form.setData('timezone', e.target.value)}
                    options={TIMEZONE_OPTIONS}
                  />
                </Field>

                <Field label="Date Format" htmlFor="date_format" error={form.errors.date_format} required>
                  <Select
                    id="date_format"
                    value={form.data.date_format}
                    onChange={e => form.setData('date_format', e.target.value)}
                    options={DATE_FORMAT_OPTIONS}
                  />
                </Field>

                <Field label="First Day of Week" htmlFor="first_day_of_week" error={form.errors.first_day_of_week} required>
                  <Select
                    id="first_day_of_week"
                    value={form.data.first_day_of_week}
                    onChange={e => form.setData('first_day_of_week', e.target.value)}
                    options={FIRST_DAY_OPTIONS}
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

Localization.layout = page => (
  <App title="Platform Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="localization">{page}</SettingsLayout>
  </App>
);
