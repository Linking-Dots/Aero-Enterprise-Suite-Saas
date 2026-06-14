/**
 * Password Policy — complexity rules, expiry, and reuse history.
 *
 * Props: { settings: {} }
 *
 * Violations fixed vs. prior stub:
 *   P0-1: no style={}
 *   P0-2: Checkbox → Toggle engine component; raw <button> → Button with intent=
 *   P0-3: className="w-full max-w-2xl" not inline style
 *   P1-1: errors passed as strings
 *   P2-1: intent= not variant=
 *   Removed import of non-existent Checkbox — replaced with Toggle
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Toggle,
  Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function PasswordPolicy({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.password-policy.update');

  const { data, setData, post, processing, errors, reset } = useForm({
    min_length:         settings.min_length         ?? 8,
    require_uppercase:  settings.require_uppercase  ?? true,
    require_lowercase:  settings.require_lowercase  ?? true,
    require_numbers:    settings.require_numbers    ?? true,
    require_symbols:    settings.require_symbols    ?? false,
    max_age_days:       settings.max_age_days       ?? 0,
    history_count:      settings.history_count      ?? 5,
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.password-policy.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Password policy saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <FormPageLayout
        title="Password Policy"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system') },
          { label: 'Password Policy' },
        ]}
        description="Define minimum complexity, expiry, and reuse rules for all user passwords."
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

          {/* ── Length ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Length</Text></CardHeader>
            <CardBody>
              <Field
                label="Minimum Length"
                hint="Users cannot set passwords shorter than this value."
                error={errors.min_length}
              >
                <Input
                  type="number"
                  value={data.min_length}
                  onChange={e => setData('min_length', Number(e.target.value))}
                  min={6}
                  max={128}
                />
              </Field>
            </CardBody>
          </Card>

          {/* ── Character Requirements ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Character Requirements</Text></CardHeader>
            <CardBody>
              <VStack gap={3}>
                <Toggle
                  label="Require uppercase letters (A–Z)"
                  checked={!!data.require_uppercase}
                  onChange={e => setData('require_uppercase', e.target.checked)}
                />
                <Toggle
                  label="Require lowercase letters (a–z)"
                  checked={!!data.require_lowercase}
                  onChange={e => setData('require_lowercase', e.target.checked)}
                />
                <Toggle
                  label="Require numbers (0–9)"
                  checked={!!data.require_numbers}
                  onChange={e => setData('require_numbers', e.target.checked)}
                />
                <Toggle
                  label="Require symbols (!@#$%^&*)"
                  checked={!!data.require_symbols}
                  onChange={e => setData('require_symbols', e.target.checked)}
                />
              </VStack>
            </CardBody>
          </Card>

          {/* ── Expiry & History ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Expiry & History</Text></CardHeader>
            <CardBody>
              <VStack gap={4}>
                <Field
                  label="Max Password Age (days)"
                  hint="Set to 0 to disable expiry — passwords never expire."
                  error={errors.max_age_days}
                >
                  <Input
                    type="number"
                    value={data.max_age_days}
                    onChange={e => setData('max_age_days', Number(e.target.value))}
                    min={0}
                    max={730}
                  />
                </Field>
                <Field
                  label="Password History Count"
                  hint="Prevent reuse of the last N passwords. Set to 0 to disable."
                  error={errors.history_count}
                >
                  <Input
                    type="number"
                    value={data.history_count}
                    onChange={e => setData('history_count', Number(e.target.value))}
                    min={0}
                    max={24}
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

PasswordPolicy.layout = page => (
  <App title="Password Policy">{page}</App>
);
