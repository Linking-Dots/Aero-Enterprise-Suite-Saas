import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
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
  Modal,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  completed: 'success',
  running:   'warning',
  failed:    'danger',
};

function formatBytes(bytes) {
  if (!bytes) return '—';
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  if (bytes < 1073741824) return `${(bytes / 1048576).toFixed(1)} MB`;
  return `${(bytes / 1073741824).toFixed(2)} GB`;
}

export default function Restore({ restorePoints, tenants }) {
  const toast      = useToast();
  const canRestore = useHRMAC('backup-restore.restore.restore');
  const canPitr    = useHRMAC('backup-restore.restore.pitr');

  const { errors } = usePage().props;

  const [restoreTarget, setRestoreTarget] = useState(null);
  const [restoring,     setRestoring]     = useState(false);

  // PITR form
  const [pitrForm, setPitrForm] = useState({
    tenant_id:   '',
    target_time: '',
  });
  const [showPitr,    setShowPitr]    = useState(false);
  const [pitrRunning, setPitrRunning] = useState(false);

  const tenantOptions = [
    { value: '', label: 'Select tenant...' },
    ...(tenants ?? []).map(t => ({ value: String(t.id), label: t.name ?? t.domain })),
  ];

  function confirmRestore() {
    if (!restoreTarget) return;
    setRestoring(true);
    router.post(
      route('platform.admin.backup.restore.execute', restoreTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Restore from backup #${restoreTarget.id} initiated.`);
          setRestoreTarget(null);
        },
        onError: () => toast.error('Restore failed.'),
        onFinish: () => setRestoring(false),
      }
    );
  }

  function executePitr(e) {
    e.preventDefault();
    if (!pitrForm.tenant_id || !pitrForm.target_time) {
      toast.error('Select a tenant and target time.');
      return;
    }
    setPitrRunning(true);
    router.post(
      route('platform.admin.backup.pitr'),
      pitrForm,
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('Point-in-time restore initiated.');
          setShowPitr(false);
          setPitrForm({ tenant_id: '', target_time: '' });
        },
        onError: () => toast.error('PITR failed.'),
        onFinish: () => setPitrRunning(false),
      }
    );
  }

  const columns = [
    {
      key: 'id',
      label: 'ID',
      render: (row) => <Mono>#{row.id}</Mono>,
    },
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => <Text>{row.tenant_name ?? row.tenant_id ?? '—'}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'size_bytes',
      label: 'Size',
      render: (row) => <Mono>{formatBytes(row.size_bytes)}</Mono>,
    },
    {
      key: 'storage_backend',
      label: 'Storage',
      render: (row) => <Badge intent="neutral">{row.storage_backend}</Badge>,
    },
    {
      key: 'completed_at',
      label: 'Date',
      render: (row) => <Mono>{row.completed_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canRestore && row.status === 'completed' ? (
          <Button intent="soft" size="sm" onClick={() => setRestoreTarget(row)}>
            Restore
          </Button>
        ) : null,
    },
  ];

  return (
    <IndexPageLayout
      title="Restore"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Backup & Restore', href: route('platform.admin.backup.index') },
        { label: 'Restore' },
      ]}
      description="Restore tenant data from a backup point or use point-in-time recovery."
      actions={
        <HStack gap={2}>
          {canPitr && (
            <Button intent="soft" leftIcon="clock" onClick={() => setShowPitr(true)}>
              Point-in-Time Restore
            </Button>
          )}
          <Button
            intent="ghost"
            leftIcon="arrowLeft"
            onClick={() => router.get(route('platform.admin.backup.index'))}
          >
            Back to Dashboard
          </Button>
        </HStack>
      }
    >
      <VStack gap={6}>
        {/* Warning Banner */}
        <Card>
          <CardBody>
            <HStack gap={3}>
              <VStack gap={1}>
                <Eyebrow tone="danger">Caution</Eyebrow>
                <Text tone="secondary">
                  Restoring overwrites the tenant's current data with the selected backup state.
                  This action cannot be undone. Ensure the tenant is notified before proceeding.
                </Text>
              </VStack>
            </HStack>
          </CardBody>
        </Card>

        {/* Restore Points Table */}
        <VStack gap={3}>
          <Eyebrow>Available Restore Points</Eyebrow>
          <DataTable
            columns={columns}
            rows={restorePoints ?? []}
            empty="No completed backups available for restore."
          />
        </VStack>
      </VStack>

      {/* Restore Confirm Modal */}
      <Modal
        open={!!restoreTarget}
        onClose={() => setRestoreTarget(null)}
        title="Confirm Restore"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="danger"
              loading={restoring}
              disabled={restoring}
              onClick={confirmRestore}
            >
              Confirm Restore
            </Button>
            <Button intent="ghost" onClick={() => setRestoreTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            You are about to restore tenant data from:
          </Text>
          {restoreTarget && (
            <Card>
              <CardBody>
                <VStack gap={2}>
                  <HStack gap={4}>
                    <VStack gap={0}>
                      <Text tone="secondary">Backup ID</Text>
                      <Mono>#{restoreTarget.id}</Mono>
                    </VStack>
                    <VStack gap={0}>
                      <Text tone="secondary">Tenant</Text>
                      <Text>{restoreTarget.tenant_name ?? restoreTarget.tenant_id}</Text>
                    </VStack>
                    <VStack gap={0}>
                      <Text tone="secondary">Date</Text>
                      <Mono>{restoreTarget.completed_at}</Mono>
                    </VStack>
                    <VStack gap={0}>
                      <Text tone="secondary">Size</Text>
                      <Mono>{formatBytes(restoreTarget.size_bytes)}</Mono>
                    </VStack>
                  </HStack>
                </VStack>
              </CardBody>
            </Card>
          )}
          <Text tone="secondary">
            All current tenant data will be replaced. This cannot be undone.
          </Text>
        </VStack>
      </Modal>

      {/* PITR Modal */}
      <Modal
        open={showPitr}
        onClose={() => setShowPitr(false)}
        title="Point-in-Time Restore"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="danger"
              loading={pitrRunning}
              disabled={pitrRunning}
              onClick={executePitr}
            >
              Execute PITR
            </Button>
            <Button intent="ghost" onClick={() => setShowPitr(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Text tone="secondary">
            Restore a tenant to any point in time within the retention window. Data after the target
            time will be lost permanently.
          </Text>
          <Field label="Tenant" htmlFor="pitr_tenant" error={errors.tenant_id} required>
            <Select
              id="pitr_tenant"
              value={pitrForm.tenant_id}
              onChange={e => setPitrForm({ ...pitrForm, tenant_id: e.target.value })}
              options={tenantOptions}
            />
          </Field>
          <Field
            label="Target Date & Time (UTC)"
            htmlFor="pitr_time"
            error={errors.target_time}
            hint="Restore data as it existed at this exact moment."
            required
          >
            <Input
              id="pitr_time"
              type="datetime-local"
              value={pitrForm.target_time}
              onChange={e => setPitrForm({ ...pitrForm, target_time: e.target.value })}
              error={errors.target_time}
            />
          </Field>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

Restore.layout = page => <App title="Restore">{page}</App>;
