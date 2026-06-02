import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Box,
  HStack, VStack,
  Text, Mono,
  Field,
  Input,
  Modal,
  useToast,
  useHRMAC,
  Pagination,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SCOPE_OPTIONS = [
  { value: 'read',  label: 'Read'  },
  { value: 'write', label: 'Write' },
  { value: 'admin', label: 'Admin' },
];

function ScopeCheckboxes({ selected, onChange, disabled }) {
  const toggle = scope => {
    if (disabled) return;
    onChange(
      selected.includes(scope)
        ? selected.filter(s => s !== scope)
        : [...selected, scope]
    );
  };

  return (
    <HStack gap={3} wrap>
      {SCOPE_OPTIONS.map(opt => (
        <label key={opt.value} className="ak-scope-label">
          <input
            type="checkbox"
            checked={selected.includes(opt.value)}
            onChange={() => toggle(opt.value)}
            disabled={disabled}
          />
          <Text size="sm">{opt.label}</Text>
        </label>
      ))}
    </HStack>
  );
}

export default function ApiKeys({ keys, flash }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('core.api.keys.create');
  const canRevoke  = useHRMAC('core.api.keys.revoke');

  const [showModal,   setShowModal]   = useState(false);
  const [dismissed,   setDismissed]   = useState(false);
  const [copied,      setCopied]      = useState(false);

  const form = useForm({
    name:       '',
    scopes:     [],
    expires_at: '',
  });

  const resetModal = () => {
    form.reset();
    setShowModal(false);
  };

  const handleCreate = e => {
    e.preventDefault();
    form.post(route('core.api.keys.store'), {
      preserveState: true,
      onSuccess: () => { toast.success('API key created.'); resetModal(); },
      onError:   () => toast.error('Failed to create API key.'),
    });
  };

  const handleRevoke = key => {
    if (!confirm(`Revoke key "${key.name}"? This cannot be undone.`)) return;
    router.post(route('core.api.keys.revoke', key.id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('API key revoked.'),
      onError:   () => toast.error('Failed to revoke key.'),
    });
  };

  const copyKey = async text => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error('Copy failed — please select the key manually.');
    }
  };

  const columns = [
    {
      key: 'name', label: 'Name', width: '18%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'prefix', label: 'Prefix', width: '14%',
      render: row => <Mono size="sm">{row.prefix ?? '—'}</Mono>,
    },
    {
      key: 'scopes', label: 'Scopes', width: '18%',
      render: row => (
        <HStack gap={1} wrap>
          {(row.scopes ?? []).length > 0
            ? (row.scopes).map(s => <Badge key={s} intent="neutral" size="sm">{s}</Badge>)
            : <Text tone="secondary" size="sm">—</Text>}
        </HStack>
      ),
    },
    {
      key: 'expires_at', label: 'Expires', width: '12%',
      render: row => row.expires_at
        ? <Text size="sm">{new Date(row.expires_at).toLocaleDateString()}</Text>
        : <Text tone="secondary" size="sm">Never</Text>,
    },
    {
      key: 'last_used_at', label: 'Last Used', width: '14%',
      render: row => row.last_used_at
        ? <Text size="sm">{new Date(row.last_used_at).toLocaleDateString()}</Text>
        : <Text tone="secondary" size="sm">Never</Text>,
    },
    {
      key: 'status', label: 'Status', width: '10%',
      render: row => (
        <Badge intent={row.revoked_at ? 'danger' : 'success'}>
          {row.revoked_at ? 'Revoked' : 'Active'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '', width: '14%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canRevoke && !row.revoked_at && (
            <Button intent="danger" size="sm" onClick={() => handleRevoke(row)}>
              Revoke
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <style>{`
        .ak-scope-label {
          display: flex;
          align-items: center;
          gap: var(--aeos-space-1, 0.25rem);
          cursor: pointer;
          user-select: none;
        }
        .ak-key-banner {
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: var(--aeos-space-3, 0.75rem) var(--aeos-space-4, 1rem);
        }
        .ak-key-value {
          font-family: var(--aeos-font-mono);
          font-size: 0.8125rem;
          word-break: break-all;
          color: var(--aeos-text-primary);
        }
      `}</style>

      <IndexPageLayout
        title="API Keys"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'API Keys' },
        ]}
        description="Manage personal API keys for programmatic access."
        actions={
          canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={() => setShowModal(true)}>
              Create API Key
            </Button>
          )
        }
        table={
          <VStack gap={3}>
            {flash?.api_key && !dismissed && (
              <div className="ak-key-banner">
                <VStack gap={2}>
                  <HStack gap={2} align="center" justify="space-between">
                    <Text size="sm" tone="secondary">
                      Your new API key — copy it now. It will not be shown again.
                    </Text>
                    <Button intent="ghost" size="sm" onClick={() => setDismissed(true)}>
                      Dismiss
                    </Button>
                  </HStack>
                  <HStack gap={2} align="center">
                    <Box grow>
                      <Mono size="sm" className="ak-key-value">{flash.api_key}</Mono>
                    </Box>
                    <Button intent="soft" size="sm" leftIcon="clipboard" onClick={() => copyKey(flash.api_key)}>
                      {copied ? 'Copied!' : 'Copy'}
                    </Button>
                  </HStack>
                </VStack>
              </div>
            )}

            <DataTable
              columns={columns}
              rows={keys?.data ?? []}
              empty="No API keys found."
            />
          </VStack>
        }
        pagination={
          keys?.last_page > 1 && (
            <Pagination
              page={keys.current_page}
              total={keys.last_page}
              onChange={page => router.get(route('core.api.keys.index'), { page }, {
                preserveState: true, preserveScroll: true, only: ['keys'],
              })}
            />
          )
        }
      />

      {/* Create API Key Modal */}
      <Modal
        open={showModal}
        title="Create API Key"
        onClose={resetModal}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={resetModal} disabled={form.processing}>Cancel</Button>
            <Button
              intent="primary"
              loading={form.processing}
              onClick={handleCreate}
              disabled={!form.data.name.trim() || form.data.scopes.length === 0}
            >
              Create Key
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleCreate}>
          <VStack gap={4}>
            <Field label="Name" htmlFor="ak-name" error={form.errors.name} required>
              <Input
                id="ak-name"
                placeholder="e.g. CI/CD Pipeline"
                value={form.data.name}
                onChange={e => form.setData('name', e.target.value)}
                error={!!form.errors.name}
              />
            </Field>

            <Field label="Scopes" error={form.errors.scopes} required>
              <ScopeCheckboxes
                selected={form.data.scopes}
                onChange={scopes => form.setData('scopes', scopes)}
              />
            </Field>

            <Field label="Expires At" htmlFor="ak-expires" hint="Leave blank for no expiry." error={form.errors.expires_at}>
              <Input
                id="ak-expires"
                type="date"
                value={form.data.expires_at}
                onChange={e => form.setData('expires_at', e.target.value)}
                error={!!form.errors.expires_at}
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

ApiKeys.layout = page => (
  <App title="API Keys">{page}</App>
);
