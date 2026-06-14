import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Toggle,
  Modal,
  Pagination,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SCOPE_OPTIONS = [
  { value: 'read:all',        label: 'Read All' },
  { value: 'write:all',       label: 'Write All' },
  { value: 'read:tenants',    label: 'Read Tenants' },
  { value: 'write:tenants',   label: 'Write Tenants' },
  { value: 'read:billing',    label: 'Read Billing' },
  { value: 'write:billing',   label: 'Write Billing' },
  { value: 'read:logs',       label: 'Read Logs' },
];

function ScopeBadges({ scopes }) {
  if (!scopes || scopes.length === 0) return <Text tone="secondary">No scopes</Text>;
  return (
    <HStack gap={1} wrap>
      {scopes.map(s => (
        <Badge key={s} intent="neutral">{s}</Badge>
      ))}
    </HStack>
  );
}

export default function ApiKeys({ keys, filters }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('platform-integrations.api-keys.create');
  const canRevoke  = useHRMAC('platform-integrations.api-keys.revoke');

  const [revokeTarget, setRevokeTarget]     = useState(null);
  const [showCreate,   setShowCreate]       = useState(false);
  const [newKeyModal,  setNewKeyModal]      = useState(null); // { name, key }
  const [copied,       setCopied]           = useState(false);

  const form = useForm({
    name:       '',
    scopes:     [],
    expires_at: '',
  });

  const [scopeInput, setScopeInput] = useState('read:all');

  function addScope() {
    if (!form.data.scopes.includes(scopeInput)) {
      form.setData('scopes', [...form.data.scopes, scopeInput]);
    }
  }

  function removeScope(s) {
    form.setData('scopes', form.data.scopes.filter(x => x !== s));
  }

  function handleCreate(e) {
    e.preventDefault();
    form.post(route('platform.admin.integrations.api-keys.store'), {
      onSuccess: (page) => {
        const plain = page.props.flash?.plain_key;
        setShowCreate(false);
        form.reset();
        if (plain) {
          setNewKeyModal({ name: form.data.name, key: plain });
        } else {
          toast.success('API key created.');
        }
      },
      onError: () => toast.error('Failed to create API key.'),
    });
  }

  function confirmRevoke() {
    if (!revokeTarget) return;
    router.post(
      route('platform.admin.integrations.api-keys.revoke', revokeTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Key "${revokeTarget.name}" revoked.`);
          setRevokeTarget(null);
        },
        onError: () => toast.error('Revoke failed.'),
      }
    );
  }

  function copyKey() {
    if (newKeyModal?.key) {
      navigator.clipboard.writeText(newKeyModal.key).then(() => {
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      });
    }
  }

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'key_prefix',
      label: 'Key Prefix',
      render: (row) => <Mono>{row.key_prefix}...</Mono>,
    },
    {
      key: 'scopes',
      label: 'Scopes',
      render: (row) => <ScopeBadges scopes={row.scopes} />,
    },
    {
      key: 'last_used_at',
      label: 'Last Used',
      render: (row) => <Mono>{row.last_used_at ?? '—'}</Mono>,
    },
    {
      key: 'expires_at',
      label: 'Expires',
      render: (row) => <Mono>{row.expires_at ?? 'Never'}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.revoked_at ? 'danger' : 'success'}>
          {row.revoked_at ? 'Revoked' : 'Active'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canRevoke && !row.revoked_at ? (
          <Button intent="danger" size="sm" onClick={() => setRevokeTarget(row)}>
            Revoke
          </Button>
        ) : null,
    },
  ];

  return (
    <IndexPageLayout
      title="API Keys"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Integrations' },
        { label: 'API Keys' },
      ]}
      description="Manage platform-level API keys for external integrations."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
            New API Key
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={keys?.data ?? []}
          empty="No API keys found."
        />

        {(keys?.last_page ?? 1) > 1 && (
          <Pagination
            page={keys.current_page}
            total={keys.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.integrations.api-keys.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['keys'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create Modal */}
      <Modal
        open={showCreate}
        onClose={() => { setShowCreate(false); form.reset(); }}
        title="Create API Key"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              type="submit"
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleCreate}
            >
              Create Key
            </Button>
            <Button intent="ghost" onClick={() => { setShowCreate(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Key Name" htmlFor="key_name" error={form.errors.name} required>
            <Input
              id="key_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="My Integration Key"
              error={form.errors.name}
            />
          </Field>

          <Field label="Expiry Date" htmlFor="expires_at" error={form.errors.expires_at} hint="Leave blank for no expiry.">
            <Input
              id="expires_at"
              type="date"
              value={form.data.expires_at}
              onChange={e => form.setData('expires_at', e.target.value)}
              error={form.errors.expires_at}
            />
          </Field>

          <Field label="Scopes" htmlFor="scope_input" error={form.errors.scopes}>
            <HStack gap={2}>
              <Select
                id="scope_input"
                value={scopeInput}
                onChange={e => setScopeInput(e.target.value)}
                options={SCOPE_OPTIONS}
              />
              <Button intent="soft" size="sm" onClick={addScope}>
                Add
              </Button>
            </HStack>
            {form.data.scopes.length > 0 && (
              <HStack gap={1} wrap>
                {form.data.scopes.map(s => (
                  <HStack key={s} gap={1}>
                    <Badge intent="neutral">{s}</Badge>
                    <Button intent="ghost" size="sm" onClick={() => removeScope(s)}>
                      x
                    </Button>
                  </HStack>
                ))}
              </HStack>
            )}
          </Field>
        </VStack>
      </Modal>

      {/* Revoke Confirm Modal */}
      <Modal
        open={!!revokeTarget}
        onClose={() => setRevokeTarget(null)}
        title="Revoke API Key"
        description={`Revoke "${revokeTarget?.name}"? This action cannot be undone. Any integrations using this key will immediately lose access.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmRevoke}>
              Confirm Revoke
            </Button>
            <Button intent="ghost" onClick={() => setRevokeTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* New Key Display Modal (shown once) */}
      <Modal
        open={!!newKeyModal}
        onClose={() => setNewKeyModal(null)}
        title="API Key Created"
        size="md"
        footer={
          <Button intent="primary" onClick={() => setNewKeyModal(null)}>
            I have copied the key
          </Button>
        }
      >
        <VStack gap={4}>
          <Text tone="secondary">
            Your new API key has been created. Copy it now — it will not be shown again.
          </Text>
          <Card>
            <CardBody>
              <VStack gap={2}>
                <Text tone="secondary">Key for: {newKeyModal?.name}</Text>
                <Mono>{newKeyModal?.key}</Mono>
              </VStack>
            </CardBody>
          </Card>
          <Button intent="soft" leftIcon="clipboard" onClick={copyKey}>
            {copied ? 'Copied!' : 'Copy to Clipboard'}
          </Button>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

ApiKeys.layout = page => <App title="API Keys">{page}</App>;
