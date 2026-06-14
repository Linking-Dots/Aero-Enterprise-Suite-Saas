import { useForm } from '@inertiajs/react';
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
  useToast,
  TextField,
  Switch,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function BackupConfig({ title, config }) {
  const { toast } = useToast();
  const { data, setData, put, processing } = useForm({
    storage_driver: config.storage_driver || 'local',
    schedule_frequency: config.schedule_frequency || 'daily',
    retention_days: config.retention_days || 30,
    encryption_enabled: config.encryption_enabled || false,
    notification_email: config.notification_email || '',
    included_files: config.included_files || [],
    excluded_files: config.excluded_files || [],
    backup_type: config.backup_type || 'full',
    active: config.active ?? true,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    put(route('core.backup.config.update'), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Backup configuration updated successfully',
        });
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Failed to update backup configuration',
          variant: 'destructive',
        });
      },
    });
  };

  const handleTest = () => {
    router.post(route('core.backup.config.test'), {}, {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Configuration test passed',
        });
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Configuration test failed',
          variant: 'destructive',
        });
      },
    });
  };

  return (
    <DashboardLayout
      title={title}
      actions={
        <Button onClick={handleTest}>
          <Icon name="beaker" className="w-4 h-4" />
          Test Configuration
        </Button>
      }
    >
      <Card>
        <CardContent>
          <form onSubmit={handleSubmit}>
            <VStack gap={4}>
              <Text weight="medium" size="lg">Backup Configuration</Text>

              <TextField
                label="Storage Driver"
                select
                value={data.storage_driver}
                onChange={(e) => setData('storage_driver', e.target.value)}
              >
                <option value="local">Local Storage</option>
                <option value="s3">Amazon S3</option>
                <option value="dropbox">Dropbox</option>
                <option value="gcs">Google Cloud Storage</option>
              </TextField>

              <TextField
                label="Schedule Frequency"
                select
                value={data.schedule_frequency}
                onChange={(e) => setData('schedule_frequency', e.target.value)}
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
              </TextField>

              <TextField
                label="Retention Days"
                type="number"
                value={data.retention_days}
                onChange={(e) => setData('retention_days', parseInt(e.target.value))}
                helperText="Backups older than this will be automatically deleted"
              />

              <TextField
                label="Backup Type"
                select
                value={data.backup_type}
                onChange={(e) => setData('backup_type', e.target.value)}
              >
                <option value="full">Full Backup (Database + Files)</option>
                <option value="database">Database Only</option>
                <option value="files">Files Only</option>
              </TextField>

              <HStack gap={2} align="center">
                <Switch
                  checked={data.encryption_enabled}
                  onChange={(e) => setData('encryption_enabled', e.target.checked)}
                />
                <Text>Enable Backup Encryption</Text>
              </HStack>

              <TextField
                label="Notification Email"
                type="email"
                value={data.notification_email}
                onChange={(e) => setData('notification_email', e.target.value)}
                helperText="Email address for backup notifications"
              />

              <HStack gap={2} align="center">
                <Switch
                  checked={data.active}
                  onChange={(e) => setData('active', e.target.checked)}
                />
                <Text>Enable Automatic Backups</Text>
              </HStack>

              <HStack gap={2} justify="flex-end">
                <Button type="submit" disabled={processing}>
                  {processing ? 'Saving...' : 'Save Configuration'}
                </Button>
              </HStack>
            </VStack>
          </form>
        </CardContent>
      </Card>
    </DashboardLayout>
  );
}

BackupConfig.layout = page => <App title={page.props.title}>{page}</App>;
