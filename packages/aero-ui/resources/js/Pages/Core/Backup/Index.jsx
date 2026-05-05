import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  DashboardLayout,
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Icon,
  Button,
  Badge,
  useToast,
  TextField,
} from '@aero/ui';
import App from '../../App';

export default function BackupIndex({ title, backups, stats, filters }) {
  const { toast } = useToast();
  const [isCreating, setIsCreating] = useState(false);
  const [backupName, setBackupName] = useState('');
  const [backupType, setBackupType] = useState('full');

  const handleCreateBackup = () => {
    setIsCreating(true);
    router.post(route('core.backup.store'), {
      name: backupName || null,
      type: backupType,
    }, {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Backup created successfully',
        });
        setBackupName('');
        setIsCreating(false);
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Failed to create backup',
          variant: 'destructive',
        });
        setIsCreating(false);
      },
    });
  };

  const handleDelete = (backupId) => {
    if (confirm('Are you sure you want to delete this backup?')) {
      router.delete(route('core.backup.destroy', backupId), {
        onSuccess: () => {
          toast({
            title: 'Success',
            description: 'Backup deleted successfully',
          });
        },
        onError: () => {
          toast({
            title: 'Error',
            description: 'Failed to delete backup',
            variant: 'destructive',
          });
        },
      });
    }
  };

  const handleDownload = (backupId) => {
    window.open(route('core.backup.download', backupId), '_blank');
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed': return 'success';
      case 'failed': return 'danger';
      case 'pending': return 'warning';
      default: return 'neutral';
    }
  };

  const getTypeIcon = (type) => {
    switch (type) {
      case 'full': return 'server';
      case 'database': return 'database';
      case 'files': return 'folder';
      default: return 'document';
    }
  };

  return (
    <DashboardLayout
      title={title}
      actions={
        <Button onClick={() => setIsCreating(!isCreating)}>
          <Icon name="plus" className="w-4 h-4" />
          Create Backup
        </Button>
      }
    >
      <VStack gap={4}>
        {/* Stats Cards */}
        <HStack gap={4}>
          <Card style={{ flex: 1 }}>
            <CardContent>
              <VStack gap={2}>
                <Text tone="secondary" size="sm">Total Backups</Text>
                <Text size="xl" weight="medium">{stats.total_backups}</Text>
              </VStack>
            </CardContent>
          </Card>
          <Card style={{ flex: 1 }}>
            <CardContent>
              <VStack gap={2}>
                <Text tone="secondary" size="sm">Successful</Text>
                <Text size="xl" weight="medium">{stats.successful_backups}</Text>
              </VStack>
            </CardContent>
          </Card>
          <Card style={{ flex: 1 }}>
            <CardContent>
              <VStack gap={2}>
                <Text tone="secondary" size="sm">Success Rate</Text>
                <Text size="xl" weight="medium">{stats.success_rate}%</Text>
              </VStack>
            </CardContent>
          </Card>
          <Card style={{ flex: 1 }}>
            <CardContent>
              <VStack gap={2}>
                <Text tone="secondary" size="sm">Total Size</Text>
                <Text size="xl" weight="medium">{(stats.total_size / (1024 * 1024)).toFixed(2)} MB</Text>
              </VStack>
            </CardContent>
          </Card>
        </HStack>

        {/* Create Backup Form */}
        {isCreating && (
          <Card>
            <CardContent>
              <VStack gap={3}>
                <Text weight="medium">Create New Backup</Text>
                <TextField
                  label="Backup Name (Optional)"
                  placeholder="Enter backup name"
                  value={backupName}
                  onChange={(e) => setBackupName(e.target.value)}
                />
                <TextField
                  label="Backup Type"
                  select
                  value={backupType}
                  onChange={(e) => setBackupType(e.target.value)}
                >
                  <option value="full">Full Backup (Database + Files)</option>
                  <option value="database">Database Only</option>
                  <option value="files">Files Only</option>
                </TextField>
                <HStack gap={2} justify="flex-end">
                  <Button variant="ghost" onClick={() => setIsCreating(false)}>
                    Cancel
                  </Button>
                  <Button onClick={handleCreateBackup} disabled={isCreating}>
                    {isCreating ? 'Creating...' : 'Create Backup'}
                  </Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        {/* Backups List */}
        {backups.data.length === 0 ? (
          <Card>
            <CardContent>
              <VStack gap={4} align="center">
                <Icon name="server" className="w-12 h-12" tone="secondary" />
                <Text tone="secondary">No backups found</Text>
                <Text tone="secondary" size="sm">Create your first backup to get started</Text>
              </VStack>
            </CardContent>
          </Card>
        ) : (
          <VStack gap={3}>
            {backups.data.map((backup) => (
              <Card key={backup.id}>
                <CardContent>
                  <HStack gap={3} align="center" justify="space-between">
                    <HStack gap={3} align="center">
                      <Icon name={getTypeIcon(backup.type)} className="w-8 h-8" tone="secondary" />
                      <VStack gap={0}>
                        <HStack gap={2} align="center">
                          <Text weight="medium">{backup.name}</Text>
                          <Badge intent={getStatusColor(backup.status)}>{backup.status}</Badge>
                          <Badge intent="secondary">{backup.type}</Badge>
                        </HStack>
                        <Text tone="secondary" size="sm">
                          {new Date(backup.created_at).toLocaleString()} • {backup.get_human_readable_size}
                        </Text>
                      </VStack>
                    </HStack>
                    <HStack gap={2}>
                      <Button variant="ghost" size="sm" onClick={() => handleDownload(backup.id)}>
                        <Icon name="download" className="w-4 h-4" />
                      </Button>
                      <Button variant="ghost" size="sm" onClick={() => handleDelete(backup.id)}>
                        <Icon name="trash" className="w-4 h-4" />
                      </Button>
                    </HStack>
                  </HStack>
                </CardContent>
              </Card>
            ))}
          </VStack>
        )}
      </VStack>
    </DashboardLayout>
  );
}

BackupIndex.layout = page => <App title={page.props.title}>{page}</App>;
