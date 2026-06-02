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
  Alert,
  Modal,
  useToast,
  useHRMAC,
  Pagination,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SCOPE_OPTIONS = [
  { value: 'read',   label: 'Read'   },
  { value: 'write',  label: 'Write'  },
  { value: 'admin',  label: 'Admin'  },
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
        <label key={opt.value} className="pat-scope-label">
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

export default function Pat({ tokens, flash }) {
  const toast     = useToast();
  const canCreate = useHRMAC('core.api.pat.create');
  const canRevoke = useHRMAC('core.api.pat.revoke');

  const [showModal,  setShowModal]  = useState(false);
  const [dismissed,  setDismissed]  = useState(false);
  const [copied,     setCopied]     = useState(false);

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
    form.post(route('core.api.pat.store'), {
      preserveState: true,
      onSuccess: () => { toast.success('Personal access token created.'); resetModal(); },
      onError:   () => toast.error('Failed to create token.'),
    });
  };

  const handleRevoke = token => {
    if (!confirm(`Revoke token "${token.name}"? This cannot be undone.`)) return;
    router.post(route('core.api.pat.revoke', token.id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Token revoked.'),
      onError:   () => toast.error('Failed to revoke token.'),
    });
  };

  const copyToken = async text => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error('Copy failed — please select the token manually.');
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
      key: 'scopes', label: 'Scopes', width: '20%',
      render: row => (
        <HStack gap={1} wrap>
          {(row.scopes ?? []).length > 0
            ? row.scopes.map(s => <Badge key={s} intent="neutral" size="sm">{s}</Badge>)
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
      key: 'actions', label: '', width: '12%', align: 'right',
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
        .pat-scope-label {
          display: flex;
          align-items: center;
          gap: var(--aeos-space-1, 0.25rem);
          cursor: pointer;
          user-select: none;
        }
        .pat-token-banner {
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: var(--aeos-space-3, 0.75rem) var(--aeos-space-4, 1rem);
        }
      `}</style>

      <IndexPageLayout
        title="Personal Access Tokens"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Personal Access Tokens' },
        ]}
        description="Create and manage personal access tokens for API authentication."
        actions={
          canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={() => setShowModal(true)}>
              Create Token
            </Button>
          )
        }
        table={
          <VStack gap={3}>
            {flash?.pat_token && !dismissed && (
              <div className="pat-token-banner">
                <VStack gap={2}>
                  <HStack gap={2} align="center" justify="space-between">
                    <Text size="sm" tone="secondary">
                      Your new personal access token — copy it now. It will not be shown again.
                    </Text>
                    <Button intent="ghost" size="sm" onClick={() => setDismissed(true)}>
                      Dismiss
                    </Button>
                  </HStack>
                  <HStack gap={2} align="center">
                    <Box grow>
                      <Mono size="sm">{flash.pat_token}</Mono>
                    </Box>
                    <Button intent="soft" size="sm" leftIcon="clipboard" onClick={() => copyToken(flash.pat_token)}>
                      {copied ? 'Copied!' : 'Copy'}
                    </Button>
                  </HStack>
                </VStack>
              </div>
            )}

            <DataTable
              columns={columns}
              rows={tokens?.data ?? []}
              empty="No personal access tokens found."
            />
          </VStack>
        }
        pagination={
          tokens?.last_page > 1 && (
            <Pagination
              page={tokens.current_page}
              total={tokens.last_page}
              onChange={page => router.get(route('core.api.pat.index'), { page }, {
                preserveState: true, preserveScroll: true, only: ['tokens'],
              })}
            />
          )
        }
      />

      {/* Create Token Modal */}
      <Modal
        open={showModal}
        title="Create Personal Access Token"
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
              Create Token
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleCreate}>
          <VStack gap={4}>
            <Field label="Name" htmlFor="pat-name" error={form.errors.name} required>
              <Input
                id="pat-name"
                placeholder="e.g. Local Development"
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

            <Field
              label="Expires At"
              htmlFor="pat-expires"
              hint="Leave blank for no expiry."
              error={form.errors.expires_at}
            >
              <Input
                id="pat-expires"
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

Pat.layout = page => (
  <App title="Personal Access Tokens">{page}</App>
);
