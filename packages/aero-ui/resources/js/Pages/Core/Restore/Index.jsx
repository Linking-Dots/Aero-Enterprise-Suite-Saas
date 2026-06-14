import { useState } from 'react';
import { router } from '@inertiajs/react';
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
  Switch,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function RestoreIndex({ title, restore_points, filters }) {
  const { toast } = useToast();
  const [selectedBackup, setSelectedBackup] = useState(null);
  const [restoreType, setRestoreType] = useState('full');
  const [restoreDatabase, setRestoreDatabase] = useState(true);
  const [restoreFiles, setRestoreFiles] = useState(true);
  const [isValidating, setIsValidating] = useState(false);
  const [validation, setValidation] = useState(null);

  const handleValidate = async (backupId) => {
    setIsValidating(true);
    try {
      const response = await router.post(route('core.restore.validate', backupId));
      setValidation(response.data);
      toast({
        title: 'Validation Complete',
        description: response.data.valid ? 'Backup is valid' : 'Backup has issues',
        variant: response.data.valid ? 'default' : 'destructive',
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to validate backup',
        variant: 'destructive',
      });
    } finally {
      setIsValidating(false);
    }
  };

  const handleRestore = (backupId) => {
    if (!confirm('Are you sure you want to restore from this backup? This action cannot be undone.')) {
      return;
    }

    router.post(route('core.restore.restore', backupId), {
      restore_type: restoreType,
      restore_database: restoreDatabase,
      restore_files: restoreFiles,
    }, {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Restore completed successfully',
        });
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Restore failed',
          variant: 'destructive',
        });
      },
    });
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
    <DashboardLayout title={title}>
      <VStack gap={4}>
        {restore_points.data.length === 0 ? (
          <Card>
            <CardContent>
              <VStack gap={4} align="center">
                <Icon name="arrow-path" className="w-12 h-12" tone="secondary" />
                <Text tone="secondary">No restore points found</Text>
                <Text tone="secondary" size="sm">Create a backup to generate restore points</Text>
              </VStack>
            </CardContent>
          </Card>
        ) : (
          <VStack gap={3}>
            {restore_points.data.map((backup) => (
              <Card key={backup.id}>
                <CardContent>
                  <VStack gap={3}>
                    <HStack gap={3} align="center" justify="space-between">
                      <HStack gap={3} align="center">
                        <Icon name={getTypeIcon(backup.type)} className="w-8 h-8" tone="secondary" />
                        <VStack gap={0}>
                          <HStack gap={2} align="center">
                            <Text weight="medium">{backup.name}</Text>
                            <Badge intent="success">{backup.status}</Badge>
                            <Badge intent="secondary">{backup.type}</Badge>
                          </HStack>
                          <Text tone="secondary" size="sm">
                            {new Date(backup.created_at).toLocaleString()} • {backup.get_human_readable_size}
                          </Text>
                        </VStack>
                      </HStack>
                      <HStack gap={2}>
                        <Button variant="ghost" size="sm" onClick={() => handleValidate(backup.id)} disabled={isValidating}>
                          <Icon name="check-circle" className="w-4 h-4" />
                          Validate
                        </Button>
                        <Button size="sm" onClick={() => setSelectedBackup(backup.id)}>
                          <Icon name="arrow-path" className="w-4 h-4" />
                          Restore
                        </Button>
                      </HStack>
                    </HStack>

                    {selectedBackup === backup.id && (
                      <Card>
                        <CardContent>
                          <VStack gap={3}>
                            <Text weight="medium">Restore Options</Text>
                            
                            <TextField
                              label="Restore Type"
                              select
                              value={restoreType}
                              onChange={(e) => setRestoreType(e.target.value)}
                            >
                              <option value="full">Full Restore</option>
                              <option value="selective">Selective Restore</option>
                            </TextField>

                            {restoreType === 'full' && (
                              <>
                                <HStack gap={2} align="center">
                                  <Switch
                                    checked={restoreDatabase}
                                    onChange={(e) => setRestoreDatabase(e.target.checked)}
                                  />
                                  <Text>Restore Database</Text>
                                </HStack>
                                <HStack gap={2} align="center">
                                  <Switch
                                    checked={restoreFiles}
                                    onChange={(e) => setRestoreFiles(e.target.checked)}
                                  />
                                  <Text>Restore Files</Text>
                                </HStack>
                              </>
                            )}

                            {validation && (
                              <Card>
                                <CardContent>
                                  <VStack gap={2}>
                                    <Text weight="medium" size="sm">Validation Result</Text>
                                    <Text tone={validation.valid ? 'success' : 'danger'} size="sm">
                                      {validation.valid ? '✓ Backup is valid' : '✗ Backup has issues'}
                                    </Text>
                                    {validation.errors && validation.errors.length > 0 && (
                                      <VStack gap={1}>
                                        {validation.errors.map((error, index) => (
                                          <Text key={index} tone="danger" size="sm">• {error}</Text>
                                        ))}
                                      </VStack>
                                    )}
                                  </VStack>
                                </CardContent>
                              </Card>
                            )}

                            <HStack gap={2} justify="flex-end">
                              <Button variant="ghost" onClick={() => setSelectedBackup(null)}>
                                Cancel
                              </Button>
                              <Button onClick={() => handleRestore(backup.id)}>
                                <Icon name="arrow-path" className="w-4 h-4" />
                                Start Restore
                              </Button>
                            </HStack>
                          </VStack>
                        </CardContent>
                      </Card>
                    )}
                  </VStack>
                </CardContent>
              </Card>
            ))}
          </VStack>
        )}
      </VStack>
    </DashboardLayout>
  );
}

RestoreIndex.layout = page => <App title={page.props.title}>{page}</App>;
