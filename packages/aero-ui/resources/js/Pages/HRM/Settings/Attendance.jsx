import { useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Field, Input, Button, Alert, Toggle,
} from '@aero/ui';

export default function AttendanceSettings({ settings }) {
  const { props } = usePage();
  const flash = props.flash ?? {};

  const { data, setData, put, processing, errors } = useForm({
    late_grace_minutes:         String(settings?.late_grace_minutes         ?? '5'),
    early_departure_grace:      String(settings?.early_departure_grace      ?? '5'),
    overtime_threshold_minutes: String(settings?.overtime_threshold_minutes ?? '30'),
    overtime_rate_multiplier:   String(settings?.overtime_rate_multiplier   ?? '1.5'),
    auto_clockout_hours:        String(settings?.auto_clockout_hours        ?? '12'),
    require_location:           settings?.require_location ?? false,
    require_selfie:             settings?.require_selfie   ?? false,
  });

  function submit(e) {
    e.preventDefault();
    put(route('hrm.settings.attendance.update'));
  }

  return (
    <div className="p-6 max-w-2xl">
        <VStack gap={5}>
          <div>
            <div className="text-2xl font-bold mb-1">Attendance Settings</div>
            <Text tone="secondary">Configure grace periods, overtime thresholds, and clock-in requirements.</Text>
          </div>

          {flash.success && <Alert intent="success" title={flash.success} />}
          {flash.error   && <Alert intent="danger"  title={flash.error}   />}

          <form onSubmit={submit}>
            <VStack gap={5}>

              {/* Grace Periods */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Grace Periods</Eyebrow>

                  <HStack gap={4}>
                    <Box grow>
                      <Field
                        label="Late Arrival Grace (minutes)"
                        htmlFor="late_grace_minutes"
                        error={errors.late_grace_minutes}
                        hint="Minutes past start time before marking as late."
                      >
                        <Input
                          id="late_grace_minutes"
                          type="number"
                          value={data.late_grace_minutes}
                          onChange={e => setData('late_grace_minutes', e.target.value)}
                          placeholder="5"
                        />
                      </Field>
                    </Box>
                    <Box grow>
                      <Field
                        label="Early Departure Grace (minutes)"
                        htmlFor="early_departure_grace"
                        error={errors.early_departure_grace}
                        hint="Minutes before end time allowed without penalty."
                      >
                        <Input
                          id="early_departure_grace"
                          type="number"
                          value={data.early_departure_grace}
                          onChange={e => setData('early_departure_grace', e.target.value)}
                          placeholder="5"
                        />
                      </Field>
                    </Box>
                  </HStack>
                </VStack>
              </div>

              {/* Overtime */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Overtime</Eyebrow>

                  <HStack gap={4}>
                    <Box grow>
                      <Field
                        label="Overtime Threshold (minutes)"
                        htmlFor="overtime_threshold_minutes"
                        error={errors.overtime_threshold_minutes}
                        hint="Minutes worked beyond shift before overtime applies."
                      >
                        <Input
                          id="overtime_threshold_minutes"
                          type="number"
                          value={data.overtime_threshold_minutes}
                          onChange={e => setData('overtime_threshold_minutes', e.target.value)}
                          placeholder="30"
                        />
                      </Field>
                    </Box>
                    <Box grow>
                      <Field
                        label="Overtime Rate Multiplier"
                        htmlFor="overtime_rate_multiplier"
                        error={errors.overtime_rate_multiplier}
                        hint="e.g. 1.5 = 1.5× regular rate"
                      >
                        <Input
                          id="overtime_rate_multiplier"
                          type="number"
                          value={data.overtime_rate_multiplier}
                          onChange={e => setData('overtime_rate_multiplier', e.target.value)}
                          placeholder="1.5"
                        />
                      </Field>
                    </Box>
                  </HStack>
                </VStack>
              </div>

              {/* Auto Clock-Out */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Auto Clock-Out</Eyebrow>

                  <Field
                    label="Auto Clock-Out After (hours)"
                    htmlFor="auto_clockout_hours"
                    error={errors.auto_clockout_hours}
                    hint="System automatically clocks out employees after this many hours."
                  >
                    <Input
                      id="auto_clockout_hours"
                      type="number"
                      value={data.auto_clockout_hours}
                      onChange={e => setData('auto_clockout_hours', e.target.value)}
                      placeholder="12"
                    />
                  </Field>
                </VStack>
              </div>

              {/* Clock-In Requirements */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Clock-In Requirements</Eyebrow>

                  <Toggle
                    label="Require location on clock-in"
                    checked={data.require_location}
                    onChange={e => setData('require_location', e.target.checked)}
                  />

                  <Toggle
                    label="Require selfie photo on clock-in"
                    checked={data.require_selfie}
                    onChange={e => setData('require_selfie', e.target.checked)}
                  />
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

AttendanceSettings.layout = page => <App title="Attendance Settings">{page}</App>;
