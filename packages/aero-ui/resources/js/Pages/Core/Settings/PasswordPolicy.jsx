import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Button,
  Card, CardContent,
  HStack, VStack,
  Text,
  Input,
  Checkbox,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function PasswordPolicy({ policy }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.password_policy.edit');

  const form = useForm({
    min_length: policy?.min_length ?? 8,
    max_length: policy?.max_length ?? 128,
    require_uppercase: policy?.require_uppercase ?? true,
    require_lowercase: policy?.require_lowercase ?? true,
    require_numbers: policy?.require_numbers ?? true,
    require_symbols: policy?.require_symbols ?? true,
    password_expiry_days: policy?.password_expiry_days ?? 90,
    password_history_count: policy?.password_history_count ?? 5,
    prevent_common_passwords: policy?.prevent_common_passwords ?? true,
    prevent_username_in_password: policy?.prevent_username_in_password ?? true,
    max_consecutive_chars: policy?.max_consecutive_chars ?? 3,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('core.settings.password-policy.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Password policy updated successfully.'),
      onError: () => toast.error('Failed to update password policy.'),
    });
  };

  return (
    <FormPageLayout
      title="Password Policy"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Settings', href: route('core.settings.security.index') },
        { label: 'Password Policy' },
      ]}
      description="Configure password complexity rules, expiry, and restrictions."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={4} className="w-full max-w-2xl">
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Length Requirements</Text>
                <HStack gap={3} className="w-full">
                  <Input
                    label="Minimum Length"
                    type="number"
                    value={form.data.min_length}
                    onChange={e => form.setData('min_length', parseInt(e.target.value))}
                    error={form.errors.min_length}
                    min={6}
                    max={128}
                  />
                  <Input
                    label="Maximum Length"
                    type="number"
                    value={form.data.max_length}
                    onChange={e => form.setData('max_length', parseInt(e.target.value))}
                    error={form.errors.max_length}
                    min={8}
                    max={256}
                  />
                </HStack>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Character Requirements</Text>
                <VStack gap={2} align="start">
                  <Checkbox
                    label="Require Uppercase Letters (A-Z)"
                    checked={form.data.require_uppercase}
                    onChange={e => form.setData('require_uppercase', e.target.checked)}
                  />
                  <Checkbox
                    label="Require Lowercase Letters (a-z)"
                    checked={form.data.require_lowercase}
                    onChange={e => form.setData('require_lowercase', e.target.checked)}
                  />
                  <Checkbox
                    label="Require Numbers (0-9)"
                    checked={form.data.require_numbers}
                    onChange={e => form.setData('require_numbers', e.target.checked)}
                  />
                  <Checkbox
                    label="Require Symbols (!@#$%^&*)"
                    checked={form.data.require_symbols}
                    onChange={e => form.setData('require_symbols', e.target.checked)}
                  />
                </VStack>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Expiry & History</Text>
                <HStack gap={3} className="w-full">
                  <Input
                    label="Password Expiry (days)"
                    type="number"
                    value={form.data.password_expiry_days}
                    onChange={e => form.setData('password_expiry_days', parseInt(e.target.value))}
                    error={form.errors.password_expiry_days}
                    min={0}
                    max={365}
                  />
                  <Input
                    label="History Count"
                    type="number"
                    value={form.data.password_history_count}
                    onChange={e => form.setData('password_history_count', parseInt(e.target.value))}
                    error={form.errors.password_history_count}
                    min={0}
                    max={24}
                  />
                </HStack>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Restrictions</Text>
                <VStack gap={2} align="start">
                  <Checkbox
                    label="Prevent Common Passwords"
                    checked={form.data.prevent_common_passwords}
                    onChange={e => form.setData('prevent_common_passwords', e.target.checked)}
                  />
                  <Checkbox
                    label="Prevent Username in Password"
                    checked={form.data.prevent_username_in_password}
                    onChange={e => form.setData('prevent_username_in_password', e.target.checked)}
                  />
                </VStack>
                <Input
                  label="Max Consecutive Identical Characters"
                  type="number"
                  value={form.data.max_consecutive_chars}
                  onChange={e => form.setData('max_consecutive_chars', parseInt(e.target.value))}
                  error={form.errors.max_consecutive_chars}
                  min={0}
                  max={10}
                  className="w-1/2"
                />
              </VStack>
            </CardContent>
          </Card>

          {canEdit && (
            <HStack gap={2} justify="end" className="w-full">
              <Button
                type="button"
                variant="secondary"
                onClick={() => form.reset()}
                disabled={form.processing}
              >
                Reset
              </Button>
              <Button
                type="submit"
                variant="primary"
                disabled={form.processing}
              >
                {form.processing ? 'Saving...' : 'Save Changes'}
              </Button>
            </HStack>
          )}
        </VStack>
      </form>
    </FormPageLayout>
  );
}

PasswordPolicy.layout = page => (
  <App title="Password Policy">{page}</App>
);
