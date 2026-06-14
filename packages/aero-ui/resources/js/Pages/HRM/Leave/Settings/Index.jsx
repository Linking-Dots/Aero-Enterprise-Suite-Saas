import { useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Field, Input, Select, Button, Alert, Toggle, Card, Text, Eyebrow,
} from '@aero/ui';

const ACCRUAL_OPTIONS = [
  { value: 'yearly',    label: 'Yearly' },
  { value: 'monthly',   label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
];

export default function LeaveSettingsIndex({ settings }) {
  const { props } = usePage();
  const flash = props.flash ?? {};

  const { data, setData, put, processing, errors } = useForm({
    accrual_cycle:             settings?.accrual_cycle              ?? 'yearly',
    allow_negative_balance:    settings?.allow_negative_balance     ?? false,
    auto_approve_under_days:   settings?.auto_approve_under_days    ?? false,
    auto_approve_threshold:    settings?.auto_approve_threshold      ?? '',
    encashment_enabled:        settings?.encashment_enabled          ?? false,
  });

  function submit(e) {
    e.preventDefault();
    put(route('hrm.leave.settings.update'));
  }

  return (
    <>
      <style>{`
        .lset-page    { padding: 1.5rem; max-width: 42rem; }
        .lset-title   { font-family: var(--aeos-font-display); font-size: 1.5rem; font-weight: 700; color: var(--aeos-text-primary); margin-bottom: 0.25rem; }
        .lset-section { padding: 1.5rem; background: var(--aeos-bg-surface); border: 1px solid var(--aeos-divider); border-radius: var(--aeos-r-lg); }
      `}</style>

      <div className="lset-page">
        <VStack gap={5}>
          <div>
            <div className="lset-title">Leave Settings</div>
            <Text tone="secondary">Global policy configuration for leave management.</Text>
          </div>

          {flash.success && (
            <Alert intent="success" title={flash.success} />
          )}

          <form onSubmit={submit}>
            <VStack gap={5}>
              <div className="lset-section">
                <VStack gap={4}>
                  <Eyebrow>Policy</Eyebrow>

                  <Field
                    label="Accrual Cycle"
                    error={errors.accrual_cycle}
                    hint="How often leave days are credited to employees."
                  >
                    <Select
                      options={ACCRUAL_OPTIONS}
                      value={data.accrual_cycle}
                      onChange={e => setData('accrual_cycle', e.target.value)}
                    />
                  </Field>

                  <Toggle
                    label="Allow negative balance"
                    checked={data.allow_negative_balance}
                    onChange={e => setData('allow_negative_balance', e.target.checked)}
                  />

                  <Toggle
                    label="Encashment enabled"
                    checked={data.encashment_enabled}
                    onChange={e => setData('encashment_enabled', e.target.checked)}
                  />
                </VStack>
              </div>

              <div className="lset-section">
                <VStack gap={4}>
                  <Eyebrow>Auto-Approval</Eyebrow>

                  <Toggle
                    label="Auto-approve short leaves"
                    checked={data.auto_approve_under_days}
                    onChange={e => setData('auto_approve_under_days', e.target.checked)}
                  />

                  {data.auto_approve_under_days && (
                    <Field
                      label="Auto-approve threshold (days)"
                      error={errors.auto_approve_threshold}
                      hint="Leaves with duration up to this many days are auto-approved."
                    >
                      <Input
                        type="number"
                        value={String(data.auto_approve_threshold)}
                        onChange={e => setData('auto_approve_threshold', e.target.value)}
                        placeholder="e.g. 2"
                      />
                    </Field>
                  )}
                </VStack>
              </div>

              <HStack gap={2}>
                <Button
                  type="submit"
                  intent="primary"
                  loading={processing}
                >
                  Save Settings
                </Button>
              </HStack>
            </VStack>
          </form>
        </VStack>
      </div>
    </>
  );
}

LeaveSettingsIndex.layout = page => <App title="Leave Settings">{page}</App>;
