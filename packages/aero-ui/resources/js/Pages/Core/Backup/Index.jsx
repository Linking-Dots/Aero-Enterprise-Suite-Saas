import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Modal,
  Alert,
  useToast,
  useHRMAC,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = { completed: 'success', failed: 'danger', running: 'warning', pending: 'neutral' };
const TYPE_INTENT   = { manual: 'neutral', scheduled: 'info' };

export default function BackupIndex({ backups }) {
  const toast       = useToast();
  const canCreate   = useHRMAC('core.backup.backups.create');
  const canDownload = useHRMAC('core.backup.backups.download');
  const canRestore  = useHRMAC('core.backup.backups.restore');
  const canDelete   = useHRMAC('core.backup.backups.delete');

  const [showCreateConfirm,  setShowCreateConfirm]  = useState(false);
  const [restoringBackup,    setRestoringBackup]    = useState(null);
  const [creating,           setCreating]           = useState(false);
  const [restoring,          setRestoring]          = useState(false);

  const backupList = backups?.data ?? [];

  const handleCreate = () => {
    setCreating(true);
    router.post(route('core.backup.store'), {}, {
      preserveState: true,
      onSuccess: () => { toast.success('Manual backup started.'); setShowCreateConfirm(false); },
      onError:   () => toast.error('Failed to start backup.'),
      onFinish:  () => setCreating(false),
    });
  };

  const handleDownload = id => {
    router.get(route('core.backup.download', id));
  };

  const handleRestore = () => {
    if (!restoringBackup) return;
    setRestoring(true);
    router.post(route('core.backup.restore', restoringBackup.id), {}, {
      preserveState: true,
      onSuccess: () => { toast.success('Restore initiated.'); setRestoringBackup(null); },
      onError:   () => toast.error('Failed to start restore.'),
      onFinish:  () => setRestoring(false),
    });
  };

  const handleDelete = id => {
    if (!confirm('Delete this backup?')) return;
    router.delete(route('core.backup.destroy', id), {
      preserveState: true,
      onSuccess: () => toast.success('Backup deleted.'),
      onError:   () => toast.error('Failed to delete backup.'),
    });
  };

  const columns = [
    {
      key: 'type', label: 'Type', width: '12%',
      render: row => (
        <Badge intent={TYPE_INTENT[row.type] || 'neutral'}>{row.type}</Badge>
      ),
    },
    {
      key: 'status', label: 'Status', width: '14%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] || 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'size', label: 'Size', width: '14%',
      render: row => <Text size="sm" tone="secondary">{row.size ?? '—'}</Text>,
    },
    {
      key: 'created_at', label: 'Created', width: '18%',
      render: row => <Text size="sm">{new Date(row.created_at).toLocaleString()}</Text>,
    },
    {
      key: 'actions', label: '', width: '42%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canDownload && row.status === 'completed' && (
            <Button intent="soft" size="sm" leftIcon="download" onClick={() => handleDownload(row.id)}>
              Download
            </Button>
          )}
          {canRestore && row.status === 'completed' && (
            <Button intent="ghost" size="sm" onClick={() => setRestoringBackup(row)}>
              Restore
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row.id)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Backups"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Backups' },
      ]}
      description="Create, download, and restore system backups."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="server" onClick={() => setShowCreateConfirm(true)}>
            Create Manual Backup
          </Button>
        )
      }
      kpis={[
        <Stat key="total"     title="Total Backups" value={backups?.total ?? 0}                                          icon="server" />,
        <Stat key="completed" title="Completed"      value={backupList.filter(b => b.status === 'completed').length}      icon="check" iconTone="success" />,
        <Stat key="failed"    title="Failed"         value={backupList.filter(b => b.status === 'failed').length}         icon="exclamation" iconTone="danger" />,
        <Stat key="manual"    title="Manual"         value={backupList.filter(b => b.type === 'manual').length}           icon="user" iconTone="neutral" />,
      ]}
      table={
        <DataTable
          columns={columns}
          rows={backupList}
          empty="No backups found."
        />
      }
      pagination={
        backups?.last_page > 1 && (
          <Pagination
            page={backups.current_page}
            total={backups.last_page}
            onChange={page => router.get(route('core.backup.index'), { page }, {
              preserveState: true, preserveScroll: true, only: ['backups'],
            })}
          />
        )
      }
    >
      {/* Create Backup Confirmation */}
      <Modal
        open={showCreateConfirm}
        onClose={() => setShowCreateConfirm(false)}
        title="Create Manual Backup"
        size="sm"
      >
        <VStack gap={4}>
          <Text>A full system backup will be created. This may take several minutes.</Text>
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setShowCreateConfirm(false)}>Cancel</Button>
            <Button intent="primary" loading={creating} onClick={handleCreate}>
              Start Backup
            </Button>
          </HStack>
        </VStack>
      </Modal>

      {/* Restore Confirmation */}
      <Modal
        open={restoringBackup !== null}
        onClose={() => setRestoringBackup(null)}
        title="Restore Backup"
        size="sm"
      >
        <VStack gap={4}>
          <Alert
            intent="danger"
            title="Warning: This will overwrite all current data with the backup snapshot. This action cannot be undone."
          />
          <Text>
            Restore backup from <strong>{restoringBackup?.created_at ? new Date(restoringBackup.created_at).toLocaleString() : '—'}</strong>?
          </Text>
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setRestoringBackup(null)}>Cancel</Button>
            <Button intent="danger" loading={restoring} onClick={handleRestore}>
              Restore Now
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

BackupIndex.layout = page => <App title="Backups">{page}</App>;
