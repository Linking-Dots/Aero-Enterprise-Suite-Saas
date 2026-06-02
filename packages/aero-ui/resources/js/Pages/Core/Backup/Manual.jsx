import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card,
  CardBody,
  Button,
  Badge,
  Alert,
  HStack, VStack,
  Text,
  Eyebrow,
  Mono,
  Modal,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  completed: 'success',
  failed:    'danger',
  running:   'warning',
  pending:   'neutral',
};

export default function BackupManual({ last_backup }) {
  const toast   = useToast();
  const [showConfirm, setShowConfirm] = useState(false);
  const [creating,    setCreating]    = useState(false);

  const handleCreate = () => {
    setCreating(true);
    router.post(route('core.backup.store'), {}, {
      preserveState: true,
      onSuccess: () => {
        toast.success('Manual backup started successfully.');
        setShowConfirm(false);
      },
      onError:  () => toast.error('Failed to start backup. Please try again.'),
      onFinish: () => setCreating(false),
    });
  };

  return (
    <IndexPageLayout
      title="Manual Backup"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Backups', href: route('core.backup.index') },
        { label: 'Manual Backup' },
      ]}
      description="Create an on-demand full system backup."
      actions={
        <Button
          intent="primary"
          size="lg"
          leftIcon="server"
          onClick={() => setShowConfirm(true)}
        >
          Create Manual Backup
        </Button>
      }
      table={
        <VStack gap={5}>

          {/* Warning notice */}
          <Alert
            intent="warning"
            title="Backup may take several minutes depending on database and file storage size. Do not close this window while a backup is in progress."
          />

          {/* Last backup */}
          <VStack gap={3}>
            <Eyebrow>Last Backup</Eyebrow>
            <Card>
              <CardBody>
                {last_backup ? (
                  <VStack gap={3}>
                    <HStack gap={4} wrap>
                      <VStack gap={1}>
                        <Text size="sm" tone="secondary">Status</Text>
                        <Badge intent={STATUS_INTENT[last_backup.status] ?? 'neutral'}>
                          {last_backup.status ?? '—'}
                        </Badge>
                      </VStack>

                      <VStack gap={1}>
                        <Text size="sm" tone="secondary">Created</Text>
                        <Mono size="sm">
                          {last_backup.created_at
                            ? new Date(last_backup.created_at).toLocaleString()
                            : '—'}
                        </Mono>
                      </VStack>

                      {last_backup.size && (
                        <VStack gap={1}>
                          <Text size="sm" tone="secondary">Size</Text>
                          <Mono size="sm">{last_backup.size}</Mono>
                        </VStack>
                      )}
                    </HStack>
                  </VStack>
                ) : (
                  <Text tone="secondary">No backups have been created yet.</Text>
                )}
              </CardBody>
            </Card>
          </VStack>

          {/* Estimated size note */}
          {last_backup?.size && (
            <VStack gap={2}>
              <Eyebrow>Estimated Size</Eyebrow>
              <Card>
                <CardBody>
                  <HStack gap={2}>
                    <Text size="sm" tone="secondary">Based on the last backup, the next backup will be approximately</Text>
                    <Mono size="sm">{last_backup.size}</Mono>
                  </HStack>
                </CardBody>
              </Card>
            </VStack>
          )}

        </VStack>
      }
    >
      {/* Confirmation Modal */}
      <Modal
        open={showConfirm}
        onClose={() => setShowConfirm(false)}
        title="Create Manual Backup"
        size="sm"
      >
        <VStack gap={4}>
          <Alert
            intent="warning"
            title="This process may take several minutes. The application will remain available during the backup."
          />
          <Text>
            A full system backup will be created, including database and uploaded files.
            Are you sure you want to proceed?
          </Text>
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setShowConfirm(false)} disabled={creating}>
              Cancel
            </Button>
            <Button intent="primary" loading={creating} leftIcon="server" onClick={handleCreate}>
              Start Backup
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

BackupManual.layout = page => <App title="Manual Backup">{page}</App>;
