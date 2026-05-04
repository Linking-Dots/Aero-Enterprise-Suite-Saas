/**
 * Localization Settings — Tenant localization configuration page.
 *
 * Uses @aero/ui FormPageLayout with fields for timezone, currency,
 * locale, date format, time format, and first day of week.
 */
import { useForm } from '@inertiajs/react';
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

const DATE_FORMAT_OPTIONS = [
  { value: 'Y-m-d', label: 'YYYY-MM-DD (2026-05-03)' },
  { value: 'd/m/Y', label: 'DD/MM/YYYY (03/05/2026)' },
  { value: 'm/d/Y', label: 'MM/DD/YYYY (05/03/2026)' },
  { value: 'd.m.Y', label: 'DD.MM.YYYY (03.05.2026)' },
  { value: 'j F Y', label: '3 May 2026' },
];

const TIME_FORMAT_OPTIONS = [
  { value: '24', label: '24-hour (14:30)' },
  { value: '12', label: '12-hour (2:30 PM)' },
];

const WEEKDAY_OPTIONS = [
  { value: 0, label: 'Sunday' },
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
];

export default function LocalizationSettings({ localization, timezones }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.localization.edit');

  const { data, setData, put, processing, errors, reset } = useForm({
    timezone: localization?.timezone ?? 'UTC',
    currency: localization?.currency ?? 'USD',
    locale: localization?.locale ?? 'en',
    date_format: localization?.date_format ?? 'Y-m-d',
    time_format: localization?.time_format ?? '24',
    first_day_of_week: localization?.first_day_of_week ?? 1,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    put(route('core.settings.localization.update'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Localization settings updated successfully.');
      },
      onError: () => {
        toast.error('Failed to update localization settings. Please check the form.');
      },
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="Localization"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Localization' },
        ]}
        description="Configure your tenant's timezone, currency, locale, date format, and regional preferences."
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
          <Section title="Regional Settings">
            <Field label="Timezone" error={errors.timezone}>
              <select
                value={data.timezone}
                onChange={e => setData('timezone', e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  borderRadius: 4,
                  border: '1px solid var(--border)',
                  background: 'var(--surface)',
                  color: 'var(--text)',
                  maxHeight: 200,
                }}
              >
                {timezones.map(tz => (
                  <option key={tz} value={tz}>{tz}</option>
                ))}
              </select>
            </Field>

            <Field label="Currency" error={errors.currency}>
              <Input
                value={data.currency}
                onChange={e => setData('currency', e.target.value)}
                placeholder="e.g. USD, EUR, GBP"
                maxLength={3}
                style={{ textTransform: 'uppercase' }}
              />
            </Field>

            <Field label="Locale" error={errors.locale}>
              <Input
                value={data.locale}
                onChange={e => setData('locale', e.target.value)}
                placeholder="e.g. en_US, fr_FR, de_DE"
              />
            </Field>
          </Section>

          <Section title="Date & Time Format">
            <Field label="Date Format" error={errors.date_format}>
              <select
                value={data.date_format}
                onChange={e => setData('date_format', e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  borderRadius: 4,
                  border: '1px solid var(--border)',
                  background: 'var(--surface)',
                  color: 'var(--text)',
                }}
              >
                {DATE_FORMAT_OPTIONS.map(opt => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </Field>

            <Field label="Time Format" error={errors.time_format}>
              <select
                value={data.time_format}
                onChange={e => setData('time_format', e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  borderRadius: 4,
                  border: '1px solid var(--border)',
                  background: 'var(--surface)',
                  color: 'var(--text)',
                }}
              >
                {TIME_FORMAT_OPTIONS.map(opt => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </Field>

            <Field label="First Day of Week" error={errors.first_day_of_week}>
              <select
                value={data.first_day_of_week}
                onChange={e => setData('first_day_of_week', Number(e.target.value))}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  borderRadius: 4,
                  border: '1px solid var(--border)',
                  background: 'var(--surface)',
                  color: 'var(--text)',
                }}
              >
                {WEEKDAY_OPTIONS.map(opt => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </Field>
          </Section>
        </VStack>
      </FormPageLayout>
    </form>
  );
}

LocalizationSettings.layout = page => (
  <App title="Localization">{page}</App>
);
