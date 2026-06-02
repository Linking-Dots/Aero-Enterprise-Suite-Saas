import { useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../hooks/useHRMAC.js';
import {
  VStack, HStack, Box, Text, Eyebrow, Field, Input, Select, Button, Alert, Card,
} from '@aero/ui';

const DAYS_PER_WEEK_OPTIONS = [
  { value: '4', label: '4 days' },
  { value: '5', label: '5 days' },
  { value: '6', label: '6 days' },
  { value: '7', label: '7 days' },
];

const FISCAL_MONTH_OPTIONS = [
  { value: '1',  label: 'January' },
  { value: '2',  label: 'February' },
  { value: '3',  label: 'March' },
  { value: '4',  label: 'April' },
  { value: '5',  label: 'May' },
  { value: '6',  label: 'June' },
  { value: '7',  label: 'July' },
  { value: '8',  label: 'August' },
  { value: '9',  label: 'September' },
  { value: '10', label: 'October' },
  { value: '11', label: 'November' },
  { value: '12', label: 'December' },
];

export default function GeneralSettings({ settings }) {
  const { props } = usePage();
  const flash = props.flash ?? {};
  const canUpdate = useHRMAC('hrm.settings.general-settings.update');

  const { data, setData, put, processing, errors } = useForm({
    work_start_time:    settings?.work_start_time    ?? '09:00',
    work_end_time:      settings?.work_end_time      ?? '17:00',
    work_days_per_week: String(settings?.work_days_per_week ?? '5'),
    fiscal_year_start:  String(settings?.fiscal_year_start  ?? '1'),
    probation_months:   String(settings?.probation_months   ?? '3'),
    notice_period_days: String(settings?.notice_period_days ?? '30'),
    employee_id_prefix: settings?.employee_id_prefix ?? 'EMP',
    employee_id_digits: String(settings?.employee_id_digits ?? '4'),
    currency:           settings?.currency           ?? 'USD',
    timezone:           settings?.timezone           ?? 'UTC',
  });

  function submit(e) {
    e.preventDefault();
    put(route('hrm.settings.general.update'));
  }

  return (
    <div className="p-6 max-w-2xl">
        <VStack gap={5}>
          <div>
            <div className="text-2xl font-bold text-foreground mb-1">HRM General Settings</div>
            <Text tone="secondary">Core configuration for working hours, employee IDs, and HR policies.</Text>
          </div>

          {flash.success && <Alert intent="success" title={flash.success} />}
          {flash.error   && <Alert intent="danger"  title={flash.error}   />}

          <form onSubmit={submit}>
            <VStack gap={5}>

              {/* Working Hours */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Working Hours</Eyebrow>

                  <HStack gap={4}>
                    <Box grow>
                      <Field label="Work Start Time" htmlFor="work_start_time" error={errors.work_start_time}>
                        <Input
                          id="work_start_time"
                          type="time"
                          value={data.work_start_time}
                          onChange={e => setData('work_start_time', e.target.value)}
                        />
                      </Field>
                    </Box>
                    <Box grow>
                      <Field label="Work End Time" htmlFor="work_end_time" error={errors.work_end_time}>
                        <Input
                          id="work_end_time"
                          type="time"
                          value={data.work_end_time}
                          onChange={e => setData('work_end_time', e.target.value)}
                        />
                      </Field>
                    </Box>
                  </HStack>

                  <Field label="Work Days per Week" htmlFor="work_days_per_week" error={errors.work_days_per_week}>
                    <Select
                      id="work_days_per_week"
                      options={DAYS_PER_WEEK_OPTIONS}
                      value={data.work_days_per_week}
                      onChange={e => setData('work_days_per_week', e.target.value)}
                    />
                  </Field>
                </VStack>
              </div>

              {/* Employee IDs */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Employee IDs</Eyebrow>

                  <HStack gap={4}>
                    <Box grow>
                      <Field
                        label="Employee ID Prefix"
                        htmlFor="employee_id_prefix"
                        error={errors.employee_id_prefix}
                        hint="Prefix prepended to numeric ID (e.g. EMP)"
                      >
                        <Input
                          id="employee_id_prefix"
                          value={data.employee_id_prefix}
                          onChange={e => setData('employee_id_prefix', e.target.value)}
                          placeholder="EMP"
                        />
                      </Field>
                    </Box>
                    <Box grow>
                      <Field
                        label="ID Digit Length"
                        htmlFor="employee_id_digits"
                        error={errors.employee_id_digits}
                        hint="Number of zero-padded digits (e.g. 4 → 0001)"
                      >
                        <Input
                          id="employee_id_digits"
                          type="number"
                          value={data.employee_id_digits}
                          onChange={e => setData('employee_id_digits', e.target.value)}
                          placeholder="4"
                        />
                      </Field>
                    </Box>
                  </HStack>
                </VStack>
              </div>

              {/* Fiscal / HR Policies */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Fiscal &amp; HR Policies</Eyebrow>

                  <Field label="Fiscal Year Start Month" htmlFor="fiscal_year_start" error={errors.fiscal_year_start}>
                    <Select
                      id="fiscal_year_start"
                      options={FISCAL_MONTH_OPTIONS}
                      value={data.fiscal_year_start}
                      onChange={e => setData('fiscal_year_start', e.target.value)}
                    />
                  </Field>

                  <HStack gap={4}>
                    <Box grow>
                      <Field
                        label="Probation Period (months)"
                        htmlFor="probation_months"
                        error={errors.probation_months}
                      >
                        <Input
                          id="probation_months"
                          type="number"
                          value={data.probation_months}
                          onChange={e => setData('probation_months', e.target.value)}
                          placeholder="3"
                        />
                      </Field>
                    </Box>
                    <Box grow>
                      <Field
                        label="Notice Period (days)"
                        htmlFor="notice_period_days"
                        error={errors.notice_period_days}
                      >
                        <Input
                          id="notice_period_days"
                          type="number"
                          value={data.notice_period_days}
                          onChange={e => setData('notice_period_days', e.target.value)}
                          placeholder="30"
                        />
                      </Field>
                    </Box>
                  </HStack>

                  <HStack gap={4}>
                    <Box grow>
                      <Field label="Currency" htmlFor="currency" error={errors.currency}>
                        <Input
                          id="currency"
                          value={data.currency}
                          onChange={e => setData('currency', e.target.value)}
                          placeholder="USD"
                        />
                      </Field>
                    </Box>
                    <Box grow>
                      <Field label="Timezone" htmlFor="timezone" error={errors.timezone}>
                        <Input
                          id="timezone"
                          value={data.timezone}
                          onChange={e => setData('timezone', e.target.value)}
                          placeholder="UTC"
                        />
                      </Field>
                    </Box>
                  </HStack>
                </VStack>
              </div>

              <HStack gap={2}>
                <Button
                  type="submit"
                  intent="primary"
                  loading={processing}
                  disabled={!canUpdate}
                >
                  Save Settings
                </Button>
              </HStack>
            </VStack>
          </form>
        </VStack>
      </div>
  );
}

GeneralSettings.layout = page => <App title="HRM General Settings">{page}</App>;
