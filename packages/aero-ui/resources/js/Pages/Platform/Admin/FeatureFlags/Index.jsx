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
  Alert,
  Progress,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function RolloutBar({ pct }) {
  return (
    <HStack gap={2}>
      <Progress value={pct} max={100} />
      <Text tone="secondary">{pct}%</Text>
    </HStack>
  );
}

export default function FeatureFlagsIndex({ flags, tenants, filters }) {
  const toast      = useToast();
  const canToggle  = useHRMAC('feature-flags.flags.toggle');
  const canUpdate  = useHRMAC('feature-flags.flags.update');
  const canArchive = useHRMAC('feature-flags.flags.archive');
  const canCreate  = useHRMAC('feature-flags.flags.create');
  const canOverride = useHRMAC('feature-flags.tenant-flags.manage');

  const [archiveTarget,   setArchiveTarget]   = useState(null);
  const [overrideFlag,    setOverrideFlag]    = useState(null); // flag for which we manage overrides
  const [overrides,       setOverrides]       = useState([]);
  const [loadingOverrides, setLoadingOverrides] = useState(false);

  // Rollout edit state — inline per-row
  const [editingRollout,  setEditingRollout]  = useState(null); // { id, pct }
  const [savingRollout,   setSavingRollout]   = useState(false);

  // Tenant override form
  const overrideForm = useForm({
    tenant_id:  '',
    is_active:  true,
    expires_at: '',
  });

  function handleToggle(flag) {
    router.post(
      route('platform.admin.feature-flags.toggle', flag.id),
      {},
      {
        preserveState: true,
        onSuccess: () => toast.success(`Flag "${flag.name}" toggled.`),
        onError: () => toast.error('Toggle failed.'),
      }
    );
  }

  function saveRollout() {
    if (!editingRollout) return;
    setSavingRollout(true);
    router.put(
      route('platform.admin.feature-flags.update', editingRollout.id),
      { rollout_pct: editingRollout.pct },
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('Rollout % updated.');
          setEditingRollout(null);
        },
        onError: () => toast.error('Update failed.'),
        onFinish: () => setSavingRollout(false),
      }
    );
  }

  function confirmArchive() {
    if (!archiveTarget) return;
    router.post(
      route('platform.admin.feature-flags.archive', archiveTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Flag "${archiveTarget.name}" archived.`);
          setArchiveTarget(null);
        },
        onError: () => toast.error('Archive failed.'),
      }
    );
  }

  function openOverrides(flag) {
    setOverrideFlag(flag);
    setLoadingOverrides(true);
    overrideForm.reset();
    router.get(
      route('platform.admin.feature-flags.overrides', flag.id),
      {},
      {
        preserveState: true,
        only: ['overrides'],
        onSuccess: (page) => {
          setOverrides(page.props.overrides ?? []);
          setLoadingOverrides(false);
        },
        onError: () => setLoadingOverrides(false),
      }
    );
  }

  function addOverride(e) {
    e.preventDefault();
    if (!overrideFlag) return;
    overrideForm.post(
      route('platform.admin.feature-flags.overrides.store', overrideFlag.id),
      {
        preserveState: true,
        onSuccess: (page) => {
          setOverrides(page.props.overrides ?? []);
          overrideForm.reset();
          toast.success('Override set.');
        },
        onError: () => toast.error('Failed to set override.'),
      }
    );
  }

  function removeOverride(overrideId) {
    router.delete(
      route('platform.admin.feature-flags.overrides.destroy', overrideId),
      {
        preserveState: true,
        onSuccess: (page) => {
          setOverrides(page.props.overrides ?? []);
          toast.success('Override removed.');
        },
        onError: () => toast.error('Remove failed.'),
      }
    );
  }

  const tenantOptions = [
    { value: '', label: 'Select tenant…' },
    ...(tenants ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  const columns = [
    {
      key: 'name',
      label: 'Flag',
      render: (row) => (
        <VStack gap={0}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('platform.admin.feature-flags.edit', row.id))}
          >
            {row.name}
          </Button>
          <Mono>{row.code}</Mono>
        </VStack>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <HStack gap={2}>
          <Badge intent={row.is_archived ? 'neutral' : row.is_active ? 'success' : 'warning'}>
            {row.is_archived ? 'Archived' : row.is_active ? 'On' : 'Off'}
          </Badge>
          {canToggle && !row.is_archived && (
            <Toggle
              label=""
              checked={row.is_active}
              onChange={() => handleToggle(row)}
            />
          )}
        </HStack>
      ),
    },
    {
      key: 'rollout_pct',
      label: 'Rollout',
      render: (row) => {
        if (editingRollout?.id === row.id) {
          return (
            <HStack gap={2}>
              <Input
                type="number"
                value={String(editingRollout.pct)}
                onChange={e => setEditingRollout({ ...editingRollout, pct: Number(e.target.value) })}
                placeholder="0–100"
              />
              <Button intent="soft" size="sm" loading={savingRollout} onClick={saveRollout}>
                Save
              </Button>
              <Button intent="ghost" size="sm" onClick={() => setEditingRollout(null)}>
                Cancel
              </Button>
            </HStack>
          );
        }
        return (
          <HStack gap={2}>
            <RolloutBar pct={row.rollout_pct ?? 100} />
            {canUpdate && !row.is_archived && (
              <Button
                intent="ghost"
                size="sm"
                onClick={() => setEditingRollout({ id: row.id, pct: row.rollout_pct ?? 100 })}
              >
                Edit
              </Button>
            )}
          </HStack>
        );
      },
    },
    {
      key: 'overrides',
      label: 'Overrides',
      render: (row) => (
        <Button intent="ghost" size="sm" onClick={() => openOverrides(row)}>
          Manage
        </Button>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canArchive && !row.is_archived && (
            <Button intent="danger" size="sm" onClick={() => setArchiveTarget(row)}>
              Archive
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Feature Flags"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Feature Flags' },
      ]}
      description="Control feature rollouts and per-tenant overrides."
      actions={
        canCreate && (
          <Button
            intent="primary"
            leftIcon="plus"
            onClick={() => router.get(route('platform.admin.feature-flags.create'))}
          >
            New Flag
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={flags?.data ?? []}
          empty="No feature flags found."
        />

        {(flags?.last_page ?? 1) > 1 && (
          <Pagination
            page={flags.current_page}
            total={flags.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.feature-flags.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['flags'] }
              )
            }
          />
        )}
      </VStack>

      {/* Archive Confirm */}
      <Modal
        open={!!archiveTarget}
        onClose={() => setArchiveTarget(null)}
        title="Archive Feature Flag"
        description={`Archive flag "${archiveTarget?.name}" (${archiveTarget?.code})? It will no longer be evaluated and cannot be toggled.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmArchive}>
              Confirm Archive
            </Button>
            <Button intent="ghost" onClick={() => setArchiveTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Tenant Overrides Modal */}
      <Modal
        open={!!overrideFlag}
        onClose={() => { setOverrideFlag(null); setOverrides([]); }}
        title={`Tenant Overrides — ${overrideFlag?.name}`}
        size="lg"
        footer={
          <Button intent="ghost" onClick={() => { setOverrideFlag(null); setOverrides([]); }}>
            Close
          </Button>
        }
      >
        <VStack gap={4}>
          {loadingOverrides ? (
            <Text tone="secondary">Loading overrides…</Text>
          ) : (
            <>
              {overrides.length > 0 ? (
                <VStack gap={2}>
                  {overrides.map(ov => (
                    <HStack key={ov.id} gap={3}>
                      <Text>{ov.tenant_name ?? `Tenant #${ov.tenant_id}`}</Text>
                      <Badge intent={ov.is_active ? 'success' : 'danger'}>
                        {ov.is_active ? 'Enabled' : 'Disabled'}
                      </Badge>
                      {ov.expires_at && <Mono>{ov.expires_at}</Mono>}
                      {canOverride && (
                        <Button intent="danger" size="sm" onClick={() => removeOverride(ov.id)}>
                          Remove
                        </Button>
                      )}
                    </HStack>
                  ))}
                </VStack>
              ) : (
                <Text tone="secondary">No per-tenant overrides set.</Text>
              )}

              {canOverride && (
                <Card>
                  <CardBody>
                    <VStack gap={3}>
                      <Eyebrow>Add Override</Eyebrow>
                      <Field label="Tenant" htmlFor="ov_tenant" error={overrideForm.errors.tenant_id} required>
                        <Select
                          id="ov_tenant"
                          value={overrideForm.data.tenant_id}
                          onChange={e => overrideForm.setData('tenant_id', e.target.value)}
                          options={tenantOptions}
                        />
                      </Field>
                      <Toggle
                        label="Override value (enabled)"
                        checked={overrideForm.data.is_active}
                        onChange={e => overrideForm.setData('is_active', e.target.checked)}
                      />
                      <Field label="Expires At" htmlFor="ov_expires" error={overrideForm.errors.expires_at} hint="Leave blank for no expiry.">
                        <Input
                          id="ov_expires"
                          type="datetime-local"
                          value={overrideForm.data.expires_at}
                          onChange={e => overrideForm.setData('expires_at', e.target.value)}
                          error={overrideForm.errors.expires_at}
                        />
                      </Field>
                      <Button
                        intent="soft"
                        loading={overrideForm.processing}
                        disabled={overrideForm.processing}
                        onClick={addOverride}
                      >
                        Set Override
                      </Button>
                    </VStack>
                  </CardBody>
                </Card>
              )}
            </>
          )}
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

FeatureFlagsIndex.layout = page => <App title="Feature Flags">{page}</App>;
