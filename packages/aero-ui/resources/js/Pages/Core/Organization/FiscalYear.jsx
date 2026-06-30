/**
 * Fiscal Year — Organization shell section.
 *
 * Props: { org: { fiscal_year_start, fiscal_year_end, timezone, date_format } }
 *
 * fiscal_year_start/end use MM-DD format. Backend validates via /^\d{2}-\d{2}$/.
 * Timezone uses IANA names. Save → POST core.organization.fiscal-year.update
 */
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Select, Card, CardHeader, CardBody, HStack, VStack, Text,
  useToast, useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import OrganizationLayout from './OrganizationLayout.jsx';
import OrganizationRail from './OrganizationRail.jsx';
import OrganizationSection from './OrganizationSection.jsx';

const DATE_FORMAT_OPTIONS = [
  { value: 'DD/MM/YYYY',  label: 'DD/MM/YYYY'  },
  { value: 'MM/DD/YYYY',  label: 'MM/DD/YYYY'  },
  { value: 'YYYY-MM-DD',  label: 'YYYY-MM-DD'  },
  { value: 'D MMM YYYY',  label: 'D MMM YYYY'  },
];

export default function FiscalYear({ org }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.organization.fiscal_year.manage');

  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    fiscal_year_start: org?.fiscal_year_start ?? '01-01',
    fiscal_year_end:   org?.fiscal_year_end   ?? '12-31',
    timezone:          org?.timezone          ?? 'UTC',
    date_format:       org?.date_format        ?? 'DD/MM/YYYY',
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.organization.fiscal-year.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Fiscal year updated.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <OrganizationSection
        title="Fiscal Year"
        description="Your fiscal calendar, timezone, and date format."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => reset()}
        onSave={handleSave}
      >
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Fiscal Calendar</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={4}>
                <Field label="Fiscal Year Start (MM-DD)" error={errors.fiscal_year_start} required>
                  <Input value={data.fiscal_year_start} onChange={e => setData('fiscal_year_start', e.target.value)} placeholder="01-01" />
                </Field>

                <Field label="Fiscal Year End (MM-DD)" error={errors.fiscal_year_end} required>
                  <Input value={data.fiscal_year_end} onChange={e => setData('fiscal_year_end', e.target.value)} placeholder="12-31" />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardHeader><Text size="sm" tone="secondary">Regional</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Timezone" error={errors.timezone} required hint="IANA timezone name (e.g. America/New_York, Europe/London)">
                <Input value={data.timezone} onChange={e => setData('timezone', e.target.value)} placeholder="UTC" />
              </Field>

              <Field label="Date Format" error={errors.date_format} required>
                <Select value={data.date_format} onChange={e => setData('date_format', e.target.value)} options={DATE_FORMAT_OPTIONS} />
              </Field>
            </VStack>
          </CardBody>
        </Card>
      </OrganizationSection>
    </form>
  );
}

FiscalYear.layout = page => (
  <App title="Organization" railTitle="Organization" rail={<OrganizationRail />}>
    <OrganizationLayout active="fiscal">{page}</OrganizationLayout>
  </App>
);
