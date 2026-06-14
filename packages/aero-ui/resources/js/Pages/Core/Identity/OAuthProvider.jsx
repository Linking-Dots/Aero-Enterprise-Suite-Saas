import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Card, CardContent,
  HStack, VStack,
  Text, Mono,
  Field,
  Input,
  Button,
  Badge,
  Alert,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SCOPE_OPTIONS = [
  { value: 'openid',  label: 'openid'  },
  { value: 'profile', label: 'profile' },
  { value: 'email',   label: 'email'   },
  { value: 'offline_access', label: 'offline_access' },
];

export default function OAuthProviderPage({ apps = [], flash = {} }) {
  const toast     = useToast();
  const canCreate = useHRMAC('auth.sso_identity.oauth.create');
  const canRevoke = useHRMAC('auth.sso_identity.oauth.revoke');

  const [modalOpen, setModalOpen]     = useState(false);
  const [copiedField, setCopiedField] = useState(null);

  const form = useForm({
    name:          '',
    redirect_uris: '',
    scopes:        [],
  });

  function openCreate() {
    form.reset();
    setModalOpen(true);
  }

  function closeModal() {
    setModalOpen(false);
    form.reset();
  }

  function handleSubmit(e) {
    e.preventDefault();
    form.post(route('core.identity.oauth.store'), {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => { toast.success('OAuth app created.'); closeModal(); },
      onError:   () => toast.error('Failed to create OAuth app.'),
    });
  }

  function handleRevoke(app) {
    if (!confirm(`Revoke OAuth app "${app.name}"? All tokens will be invalidated.`)) return;
    router.delete(route('core.identity.oauth.destroy', app.id), {
      preserveState: true,
      onSuccess: () => toast.success('OAuth app revoked.'),
      onError:   () => toast.error('Failed to revoke OAuth app.'),
    });
  }

  function toggleScope(scope) {
    const current = form.data.scopes ?? [];
    const next = current.includes(scope)
      ? current.filter(s => s !== scope)
      : [...current, scope];
    form.setData('scopes', next);
  }

  function copyToClipboard(value, fieldKey) {
    navigator.clipboard.writeText(value).then(() => {
      setCopiedField(fieldKey);
      setTimeout(() => setCopiedField(null), 2000);
    });
  }

  const columns = [
    {
      key: 'name', label: 'App Name', width: '28%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'client_id', label: 'Client ID', width: '36%',
      render: row => (
        <HStack gap={2} align="center">
          <Mono size="sm" tone="secondary">
            {row.client_id?.length > 24
              ? `${row.client_id.slice(0, 12)}…${row.client_id.slice(-8)}`
              : row.client_id}
          </Mono>
          <Button
            intent="ghost"
            size="sm"
            leftIcon="clipboard"
            onClick={() => copyToClipboard(row.client_id, `id-${row.id}`)}
          >
            {copiedField === `id-${row.id}` ? 'Copied' : 'Copy'}
          </Button>
        </HStack>
      ),
    },
    {
      key: 'is_enabled', label: 'Status', width: '14%',
      render: row => (
        <Badge intent={row.is_enabled ? 'success' : 'warning'}>
          {row.is_enabled ? 'Enabled' : 'Disabled'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '', width: '22%', align: 'right',
      render: row => (
        canRevoke && (
          <Button intent="danger" size="sm" onClick={() => handleRevoke(row)}>
            Revoke
          </Button>
        )
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="OAuth Providers"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'OAuth Providers' },
      ]}
      description="Manage OAuth 2.0 applications that can access this tenant via the API."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            Create OAuth App
          </Button>
        )
      }
      table={
        <VStack gap={4}>
          {flash.oauth_credentials && (
            <Alert
              intent="success"
              title="OAuth app created — save these credentials now"
            >
              <VStack gap={3}>
                <Text tone="secondary" size="sm">
                  The client secret is shown only once and cannot be retrieved again.
                </Text>

                <Card>
                  <CardContent>
                    <VStack gap={3}>
                      <HStack gap={3} align="center">
                        <Text size="sm" weight="semibold">Client ID</Text>
                        <Mono size="sm">{flash.oauth_credentials.client_id}</Mono>
                        <Button
                          intent="ghost"
                          size="sm"
                          leftIcon="clipboard"
                          onClick={() => copyToClipboard(flash.oauth_credentials.client_id, 'flash-id')}
                        >
                          {copiedField === 'flash-id' ? 'Copied' : 'Copy'}
                        </Button>
                      </HStack>

                      <HStack gap={3} align="center">
                        <Text size="sm" weight="semibold">Client Secret</Text>
                        <Mono size="sm">{flash.oauth_credentials.client_secret}</Mono>
                        <Button
                          intent="ghost"
                          size="sm"
                          leftIcon="clipboard"
                          onClick={() => copyToClipboard(flash.oauth_credentials.client_secret, 'flash-secret')}
                        >
                          {copiedField === 'flash-secret' ? 'Copied' : 'Copy'}
                        </Button>
                      </HStack>
                    </VStack>
                  </CardContent>
                </Card>
              </VStack>
            </Alert>
          )}

          <DataTable
            columns={columns}
            rows={apps}
            empty="No OAuth apps configured."
          />
        </VStack>
      }
    >
      <Modal
        open={modalOpen}
        onClose={closeModal}
        title="Create OAuth App"
        size="md"
      >
        <form onSubmit={handleSubmit}>
          <VStack gap={4}>
            <Field label="App name" htmlFor="oauth_name" error={form.errors.name} required>
              <Input
                id="oauth_name"
                value={form.data.name}
                onChange={e => form.setData('name', e.target.value)}
                error={form.errors.name}
                placeholder="e.g. My Integration"
                autoFocus
              />
            </Field>

            <Field
              label="Redirect URIs"
              htmlFor="redirect_uris"
              hint="Enter one URI per line."
              error={form.errors.redirect_uris}
              required
            >
              <Input
                id="redirect_uris"
                as="textarea"
                value={form.data.redirect_uris}
                onChange={e => form.setData('redirect_uris', e.target.value)}
                error={form.errors.redirect_uris}
                placeholder={`https://app.example.com/callback\nhttps://app.example.com/auth`}
                rows={4}
              />
            </Field>

            <Field label="Scopes" error={form.errors.scopes}>
              <VStack gap={2}>
                {SCOPE_OPTIONS.map(({ value, label }) => (
                  <label key={value} className="aeos-checkbox-row">
                    <input
                      type="checkbox"
                      checked={(form.data.scopes ?? []).includes(value)}
                      onChange={() => toggleScope(value)}
                    />
                    <Mono size="sm">{label}</Mono>
                  </label>
                ))}
              </VStack>
            </Field>

            <HStack gap={2} justify="end">
              <Button type="button" intent="ghost" onClick={closeModal}>Cancel</Button>
              <Button type="submit" intent="primary" loading={form.processing}>
                Create App
              </Button>
            </HStack>
          </VStack>
        </form>
      </Modal>
    </IndexPageLayout>
  );
}

OAuthProviderPage.layout = page => (
  <App title="OAuth Providers">{page}</App>
);
