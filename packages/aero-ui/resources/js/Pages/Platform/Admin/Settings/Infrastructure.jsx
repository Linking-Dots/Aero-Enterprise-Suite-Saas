import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  Select,
  Button,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Badge,
  Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const MODE_OPTIONS = [
  { value: 'dedicated', label: 'Dedicated (VPS / Cloud)' },
  { value: 'shared',    label: 'Shared (cPanel)' },
];

export default function Infrastructure({ hosting }) {
  const toast   = useToast();
  const canEdit = useHRMAC('system-settings.infrastructure-settings.edit');

  const form = useForm({
    mode:              hosting?.mode              ?? 'dedicated',
    cpanel_host:       hosting?.cpanel_host       ?? '',
    cpanel_port:       hosting?.cpanel_port       ?? 2083,
    cpanel_username:   hosting?.cpanel_username   ?? '',
    cpanel_api_token:  '',
    cpanel_db_user:    hosting?.cpanel_db_user    ?? '',
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.put(route('platform.admin.settings.infrastructure.update'), {
      onSuccess: () => toast.success('Infrastructure settings saved.'),
      onError:   () => toast.error('Failed to save infrastructure settings.'),
    });
  }

  const isShared = form.data.mode === 'shared';

  return (
    <FormPageLayout
      title="Infrastructure"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Settings' },
        { label: 'Infrastructure' },
      ]}
      description="Storage driver, queue driver, cache driver and hosting configuration."
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={3} align="center">
                <Eyebrow>Hosting Mode</Eyebrow>
                {hosting?.resolved_mode && (
                  <Badge intent="neutral">Resolved: {hosting.resolved_mode}</Badge>
                )}
              </HStack>

              <Field label="Mode" htmlFor="mode" error={form.errors.mode} required>
                <Select
                  id="mode"
                  value={form.data.mode}
                  onChange={e => form.setData('mode', e.target.value)}
                  options={MODE_OPTIONS}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {isShared && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>cPanel Configuration</Eyebrow>
                <Text tone="secondary">
                  Required for shared hosting. The API token is encrypted at rest and never
                  transmitted back to the browser.
                </Text>

                <HStack gap={4}>
                  <Field label="cPanel Host" htmlFor="cpanel_host" error={form.errors.cpanel_host}>
                    <Input
                      id="cpanel_host"
                      value={form.data.cpanel_host}
                      onChange={e => form.setData('cpanel_host', e.target.value)}
                      placeholder="cpanel.yourhost.com"
                      error={form.errors.cpanel_host}
                    />
                  </Field>

                  <Field label="cPanel Port" htmlFor="cpanel_port" error={form.errors.cpanel_port}>
                    <Input
                      id="cpanel_port"
                      type="number"
                      value={String(form.data.cpanel_port)}
                      onChange={e => form.setData('cpanel_port', Number(e.target.value))}
                      placeholder="2083"
                      error={form.errors.cpanel_port}
                    />
                  </Field>
                </HStack>

                <HStack gap={4}>
                  <Field label="cPanel Username" htmlFor="cpanel_username" error={form.errors.cpanel_username}>
                    <Input
                      id="cpanel_username"
                      value={form.data.cpanel_username}
                      onChange={e => form.setData('cpanel_username', e.target.value)}
                      placeholder="myuser"
                      error={form.errors.cpanel_username}
                    />
                  </Field>

                  <Field
                    label="cPanel API Token"
                    htmlFor="cpanel_api_token"
                    error={form.errors.cpanel_api_token}
                    hint={hosting?.cpanel_api_token_set ? 'Token is stored. Leave blank to keep current.' : undefined}
                  >
                    <Input
                      id="cpanel_api_token"
                      type="password"
                      name="cpanel_api_token"
                      autoComplete="new-password"
                      value={form.data.cpanel_api_token}
                      onChange={e => form.setData('cpanel_api_token', e.target.value)}
                      placeholder={hosting?.cpanel_api_token_set ? '••••••••' : 'Paste API token'}
                      error={form.errors.cpanel_api_token}
                    />
                  </Field>
                </HStack>

                <Field label="cPanel DB User" htmlFor="cpanel_db_user" error={form.errors.cpanel_db_user}>
                  <Input
                    id="cpanel_db_user"
                    value={form.data.cpanel_db_user}
                    onChange={e => form.setData('cpanel_db_user', e.target.value)}
                    placeholder="myuser_db"
                    error={form.errors.cpanel_db_user}
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>
        )}

        {canEdit && (
          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={form.processing} disabled={form.processing}>
              Save Infrastructure Settings
            </Button>
          </HStack>
        )}

      </VStack>
    </FormPageLayout>
  );
}

Infrastructure.layout = page => <App title="Infrastructure">{page}</App>;
