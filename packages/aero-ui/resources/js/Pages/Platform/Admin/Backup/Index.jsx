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

const FREQ_OPTIONS = [
  { value: 'daily',   label: 'Daily' },
  { value: 'weekly',  label: 'Weekly' },
  { value: 'monthly', label: 'Monthly' },
];

const STORAGE_OPTIONS = [
  { value: 's3',    label: 'Amazon S3' },
  { value: 'gcs',   label: 'Google Cloud Storage' },
  { value: 'local', label: 'Local Disk' },
  { value: 'sftp',  label: 'SFTP' },
];

function formatBytes(bytes) {
  if (!bytes) return '—';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  if (bytes < 1073741824) return `${(bytes / 1048576).toFixed(1)} MB`;
  return `${(bytes / 1073741824).toFixed(2)} GB`;
}

export default function BackupIndex({ schedules, recentBackups }) {
  const toast           = useToast();
  const canSchedule     = useHRMAC('backup-restore.backup-schedules.create');
  const canManual       = useHRMAC('backup-restore.manual-backups.create');
  const canDeleteSched  = useHRMAC('backup-restore.backup-schedules.delete');

  const [showSchedule,   setShowSchedule]   = useState(false);
  const [deleteTarget,   setDeleteTarget]   = useState(null);
  const [manualTenantId, setManualTenantId] = useState('');
  const [manualRunning,  setManualRunning]  = useState(false);

  const scheduleForm = useForm({
    tenant_id:            '',
    frequency:            'daily',
    time_of_day:          '02:00',
    retention_days:       30,
    storage_backend_code: 's3',
    is_active:            true,
  });

  function handleCreateSchedule(e) {
    e.preventDefault();
    scheduleForm.post(route('platform.admin.backup.schedules.store'), {
      onSuccess: () => {
        setShowSchedule(false);
        scheduleForm.reset();
        toast.success('Backup schedule created.');
      },
      onError: () => toast.error('Failed to create schedule.'),
    });
  }

  function handleDeleteSchedule() {
    if (!deleteTarget) return;
    router.post(
      route('platform.admin.backup.schedules.destroy', deleteTarget.id),
      { _method: 'DELETE' },
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('Schedule deleted.');
          setDeleteTarget(null);
        },
        onError: () => toast.error('Delete failed.'),
      }
    );
  }

  function triggerManualBackup() {
    setManualRunning(true);
    router.post(
      route('platform.admin.backup.manual'),
      { tenant_id: manualTenantId },
      {
        preserveState: true,
        onSuccess: () => toast.success('Manual backup triggered.'),
        onError:   () => toast.error('Backup trigger failed.'),
        onFinish:  () => setManualRunning(false),
      }
    );
  }

  function downloadBackup(backup) {
    window.location.href = route('platform.admin.backup.download', backup.id);
  }

  const scheduleColumns = [
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => <Text>{row.tenant_name ?? row.tenant_id ?? 'All Tenants'}</Text>,
    },
    {
      key: 'frequency',
      label: 'Frequency',
      render: (row) => <Badge intent="neutral">{row.frequency}</Badge>,
    },
    {
      key: 'time_of_day',
      label: 'Time',
      render: (row) => <Mono>{row.time_of_day}</Mono>,
    },
    {
      key: 'retention_days',
      label: 'Retention',
      render: (row) => <Text>{row.retention_days} days</Text>,
    },
    {
      key: 'storage_backend_code',
      label: 'Storage',
      render: (row) => <Badge intent="neutral">{row.storage_backend_code}</Badge>,
    },
    {
      key: 'is_active',
      label: 'Active',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Paused'}
        </Badge>
      ),
    },
    {
      key: 'last_run_at',
      label: 'Last Run',
      render: (row) => <Mono>{row.last_run_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canDeleteSched ? (
          <Button intent="danger" size="sm" onClick={() => setDeleteTarget(row)}>
            Delete
          </Button>
        ) : null,
    },
  ];

  const backupColumns = [
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
      label: 'Completed',
      render: (row) => <Mono>{row.completed_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        row.status === 'completed' ? (
          <Button intent="ghost" size="sm" leftIcon="download" onClick={() => downloadBackup(row)}>
            Download
          </Button>
        ) : null,
    },
  ];

  return (
    <IndexPageLayout
      title="Backup Dashboard"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Backup & Restore' },
        { label: 'Dashboard' },
      ]}
      description="Manage backup schedules and monitor recent backup jobs."
      actions={
        <HStack gap={2}>
          {canManual && (
            <Button
              intent="soft"
              leftIcon="play"
              loading={manualRunning}
              disabled={manualRunning}
              onClick={triggerManualBackup}
            >
              On-Demand Backup
            </Button>
          )}
          {canSchedule && (
            <Button intent="primary" leftIcon="plus" onClick={() => setShowSchedule(true)}>
              New Schedule
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={6}>
        {/* Manual Backup Controls */}
        {canManual && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>On-Demand Backup</Eyebrow>
                <HStack gap={3}>
                  <Field label="Tenant ID (optional)" htmlFor="manual_tenant">
                    <Input
                      id="manual_tenant"
                      value={manualTenantId}
                      onChange={e => setManualTenantId(e.target.value)}
                      placeholder="Leave blank for all tenants"
                    />
                  </Field>
                  <Button
                    intent="soft"
                    loading={manualRunning}
                    disabled={manualRunning}
                    onClick={triggerManualBackup}
                  >
                    Trigger Now
                  </Button>
                </HStack>
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Schedules */}
        <VStack gap={3}>
          <Eyebrow>Backup Schedules</Eyebrow>
          <DataTable
            columns={scheduleColumns}
            rows={schedules ?? []}
            empty="No backup schedules configured."
          />
        </VStack>

        {/* Recent Backups */}
        <VStack gap={3}>
          <Eyebrow>Recent Backups</Eyebrow>
          <DataTable
            columns={backupColumns}
            rows={recentBackups ?? []}
            empty="No recent backups."
          />
        </VStack>

        {/* Restore Link */}
        <HStack gap={2}>
          <Button
            intent="ghost"
            leftIcon="arrowRight"
            onClick={() => router.get(route('platform.admin.backup.restore'))}
          >
            Restore from Backup
          </Button>
        </HStack>
      </VStack>

      {/* Create Schedule Modal */}
      <Modal
        open={showSchedule}
        onClose={() => { setShowSchedule(false); scheduleForm.reset(); }}
        title="New Backup Schedule"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={scheduleForm.processing}
              disabled={scheduleForm.processing}
              onClick={handleCreateSchedule}
            >
              Create Schedule
            </Button>
            <Button intent="ghost" onClick={() => { setShowSchedule(false); scheduleForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Tenant ID (blank = platform-wide)" htmlFor="sched_tenant" error={scheduleForm.errors.tenant_id}>
            <Input
              id="sched_tenant"
              value={scheduleForm.data.tenant_id}
              onChange={e => scheduleForm.setData('tenant_id', e.target.value)}
              placeholder="Optional tenant UUID"
              error={scheduleForm.errors.tenant_id}
            />
          </Field>
          <HStack gap={4}>
            <Field label="Frequency" htmlFor="sched_freq" error={scheduleForm.errors.frequency}>
              <Select
                id="sched_freq"
                value={scheduleForm.data.frequency}
                onChange={e => scheduleForm.setData('frequency', e.target.value)}
                options={FREQ_OPTIONS}
              />
            </Field>
            <Field label="Time of Day (UTC)" htmlFor="sched_time" error={scheduleForm.errors.time_of_day}>
              <Input
                id="sched_time"
                type="time"
                value={scheduleForm.data.time_of_day}
                onChange={e => scheduleForm.setData('time_of_day', e.target.value)}
                error={scheduleForm.errors.time_of_day}
              />
            </Field>
          </HStack>
          <HStack gap={4}>
            <Field label="Retention (days)" htmlFor="sched_retention" error={scheduleForm.errors.retention_days}>
              <Input
                id="sched_retention"
                type="number"
                value={scheduleForm.data.retention_days}
                onChange={e => scheduleForm.setData('retention_days', Number(e.target.value))}
                error={scheduleForm.errors.retention_days}
              />
            </Field>
            <Field label="Storage Backend" htmlFor="sched_storage" error={scheduleForm.errors.storage_backend_code}>
              <Select
                id="sched_storage"
                value={scheduleForm.data.storage_backend_code}
                onChange={e => scheduleForm.setData('storage_backend_code', e.target.value)}
                options={STORAGE_OPTIONS}
              />
            </Field>
          </HStack>
          <Toggle
            label="Active immediately"
            checked={scheduleForm.data.is_active}
            onChange={v => scheduleForm.setData('is_active', v)}
          />
        </VStack>
      </Modal>

      {/* Delete Schedule Confirm Modal */}
      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Schedule"
        description={`Delete the ${deleteTarget?.frequency} schedule? Existing backups are not affected.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={handleDeleteSchedule}>
              Confirm Delete
            </Button>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

BackupIndex.layout = page => <App title="Backup Dashboard">{page}</App>;
