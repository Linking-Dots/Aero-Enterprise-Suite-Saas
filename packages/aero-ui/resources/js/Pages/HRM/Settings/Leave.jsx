import { useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Field, Input, Select, Button, Alert, Toggle, Badge,
} from '@aero/ui';

const ACCRUAL_OPTIONS = [
  { value: 'monthly',   label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'yearly',    label: 'Yearly' },
];

const APPROVAL_LEVEL_OPTIONS = [
  { value: '1', label: '1 — Direct Manager' },
  { value: '2', label: '2 — Manager + HR' },
  { value: '3', label: '3 — Manager + HR + Director' },
];

const ALL_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
const DAY_LABELS = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri', sat: 'Sat', sun: 'Sun' };

export default function LeaveSettings({ settings }) {
  const { props } = usePage();
  const flash = props.flash ?? {};

  const { data, setData, put, processing, errors } = useForm({
    working_days:          settings?.working_days          ?? ['mon', 'tue', 'wed', 'thu', 'fri'],
    accrual_enabled:       settings?.accrual_enabled       ?? false,
    accrual_frequency:     settings?.accrual_frequency     ?? 'monthly',
    carry_forward_enabled: settings?.carry_forward_enabled ?? false,
    carry_forward_max_days: String(settings?.carry_forward_max_days ?? '5'),
    encashment_enabled:    settings?.encashment_enabled    ?? false,
    encashment_max_days:   String(settings?.encashment_max_days ?? '10'),
    leave_approval_levels: String(settings?.leave_approval_levels ?? '1'),
    min_notice_days:       String(settings?.min_notice_days ?? '1'),
  });

  function toggleDay(day) {
    const current = data.working_days ?? [];
    if (current.includes(day)) {
      setData('working_days', current.filter(d => d !== day));
    } else {
      setData('working_days', [...current, day]);
    }
  }

  function submit(e) {
    e.preventDefault();
    put(route('hrm.settings.leave.update'));
  }

  return (
    <div className="p-6 max-w-2xl">
        <VStack gap={5}>
          <div>
            <div className="text-2xl font-bold mb-1">Leave Settings</div>
            <Text tone="secondary">Configure working days, accrual, carry-forward, and encashment policies.</Text>
          </div>

          {flash.success && <Alert intent="success" title={flash.success} />}
          {flash.error   && <Alert intent="danger"  title={flash.error}   />}

          <form onSubmit={submit}>
            <VStack gap={5}>

              {/* Working Days */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Working Days</Eyebrow>
                  <Text tone="secondary">Select which days of the week are considered working days.</Text>
                  {errors.working_days && <Text tone="secondary">{errors.working_days}</Text>}
                  <HStack gap={2} wrap>
                    {ALL_DAYS.map(day => {
                      const isActive = (data.working_days ?? []).includes(day);
                      return (
                        <span
                          key={day}
                          className="cursor-pointer select-none"
                          onClick={() => toggleDay(day)}
                          role="button"
                          tabIndex={0}
                          onKeyDown={e => e.key === 'Enter' && toggleDay(day)}
                        >
                          <Badge intent={isActive ? 'success' : 'neutral'}>
                            {DAY_LABELS[day]}
                          </Badge>
                        </span>
                      );
                    })}
                  </HStack>
                </VStack>
              </div>

              {/* Accrual */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Accrual</Eyebrow>

                  <Toggle
                    label="Enable leave accrual"
                    checked={data.accrual_enabled}
                    onChange={e => setData('accrual_enabled', e.target.checked)}
                  />

                  {data.accrual_enabled && (
                    <Field
                      label="Accrual Frequency"
                      htmlFor="accrual_frequency"
                      error={errors.accrual_frequency}
                      hint="How often leave days are credited to employees."
                    >
                      <Select
                        id="accrual_frequency"
                        options={ACCRUAL_OPTIONS}
                        value={data.accrual_frequency}
                        onChange={e => setData('accrual_frequency', e.target.value)}
                      />
                    </Field>
                  )}
                </VStack>
              </div>

              {/* Carry Forward */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Carry Forward</Eyebrow>

                  <Toggle
                    label="Enable carry forward of unused leave"
                    checked={data.carry_forward_enabled}
                    onChange={e => setData('carry_forward_enabled', e.target.checked)}
                  />

                  {data.carry_forward_enabled && (
                    <Field
                      label="Maximum Days to Carry Forward"
                      htmlFor="carry_forward_max_days"
                      error={errors.carry_forward_max_days}
                    >
                      <Input
                        id="carry_forward_max_days"
                        type="number"
                        value={data.carry_forward_max_days}
                        onChange={e => setData('carry_forward_max_days', e.target.value)}
                        placeholder="5"
                      />
                    </Field>
                  )}
                </VStack>
              </div>

              {/* Encashment */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Encashment</Eyebrow>

                  <Toggle
                    label="Enable leave encashment"
                    checked={data.encashment_enabled}
                    onChange={e => setData('encashment_enabled', e.target.checked)}
                  />

                  {data.encashment_enabled && (
                    <Field
                      label="Maximum Days for Encashment"
                      htmlFor="encashment_max_days"
                      error={errors.encashment_max_days}
                    >
                      <Input
                        id="encashment_max_days"
                        type="number"
                        value={data.encashment_max_days}
                        onChange={e => setData('encashment_max_days', e.target.value)}
                        placeholder="10"
                      />
                    </Field>
                  )}
                </VStack>
              </div>

              {/* Approval & Notice */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Approval &amp; Notice</Eyebrow>

                  <Field
                    label="Leave Approval Levels"
                    htmlFor="leave_approval_levels"
                    error={errors.leave_approval_levels}
                  >
                    <Select
                      id="leave_approval_levels"
                      options={APPROVAL_LEVEL_OPTIONS}
                      value={data.leave_approval_levels}
                      onChange={e => setData('leave_approval_levels', e.target.value)}
                    />
                  </Field>

                  <Field
                    label="Minimum Notice Days"
                    htmlFor="min_notice_days"
                    error={errors.min_notice_days}
                    hint="Minimum days in advance a leave request must be submitted."
                  >
                    <Input
                      id="min_notice_days"
                      type="number"
                      value={data.min_notice_days}
                      onChange={e => setData('min_notice_days', e.target.value)}
                      placeholder="1"
                    />
                  </Field>
                </VStack>
              </div>

              <HStack gap={2}>
                <Button type="submit" intent="primary" loading={processing}>
                  Save Settings
                </Button>
              </HStack>
            </VStack>
          </form>
        </VStack>
      </div>
  );
}

LeaveSettings.layout = page => <App title="Leave Settings">{page}</App>;
