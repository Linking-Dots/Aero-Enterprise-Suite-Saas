import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  Select,
  HStack, VStack, Stack,
  Text, Heading,
  Badge,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function ExportsIndex({ entity_type, entities, history }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.data_export_import.exports.create');
  const canDownload = useHRMAC('core.data_export_import.exports.download');
  const canDelete = useHRMAC('core.data_export_import.exports.delete');

  const [selectedEntity, setSelectedEntity] = useState(entity_type);

  const form = useForm({
    entity_type: selectedEntity,
    format: 'csv',
    filters: {},
  });

  const handleCreateExport = (e) => {
    e.preventDefault();
    form.post(route('core.export-import.exports.create'), {
      onSuccess: () => {
        toast.success('Export job created successfully');
        router.reload();
      },
      onError: () => {
        toast.error('Failed to create export');
      },
    });
  };

  const handleDownload = (exportId) => {
    window.location.href = route('core.export-import.exports.download', { id: exportId });
  };

  const handleDelete = (exportId) => {
    if (confirm('Are you sure you want to delete this export?')) {
      router.delete(route('core.export-import.exports.delete', { id: exportId }), {
        onSuccess: () => {
          toast.success('Export deleted successfully');
          router.reload();
        },
      });
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed':
        return 'success';
      case 'failed':
        return 'error';
      case 'processing':
        return 'warning';
      default:
        return 'default';
    }
  };

  return (
    <App title="Data Exports">
      <IndexPageLayout
        title="Data Exports"
        breadcrumbs={[
          { label: 'Core', href: route('core.dashboard') },
          { label: 'Export/Import', href: '#' },
          { label: 'Exports' },
        ]}
      >
        <VStack spacing={6}>
          {canCreate && (
            <Card>
              <CardHeader>
                <Heading size="md">Create New Export</Heading>
              </CardHeader>
              <CardBody>
                <form onSubmit={handleCreateExport}>
                  <VStack spacing={4}>
                    <HStack>
                      <Select
                        label="Entity"
                        value={selectedEntity}
                        onChange={(e) => setSelectedEntity(e.target.value)}
                        options={Object.entries(entities).map(([key, label]) => ({ value: key, label }))}
                      />
                      <Select
                        label="Format"
                        value={form.data.format}
                        onChange={(e) => form.setData('format', e.target.value)}
                        options={[
                          { value: 'csv', label: 'CSV' },
                          { value: 'json', label: 'JSON' },
                          { value: 'xlsx', label: 'Excel' },
                        ]}
                      />
                    </HStack>
                    <Button type="submit" isLoading={form.processing}>
                      Create Export
                    </Button>
                  </VStack>
                </form>
              </CardBody>
            </Card>
          )}

          <Card>
            <CardHeader>
              <Heading size="md">Export History</Heading>
            </CardHeader>
            <CardBody>
              {history.length === 0 ? (
                <Text color="muted">No exports found</Text>
              ) : (
                <Stack spacing={3}>
                  {history.map((export) => (
                    <HStack key={export.id} justify="space-between" align="center">
                      <VStack align="start" spacing={1}>
                        <HStack>
                          <Text fontWeight="medium">{export.entity_type}</Text>
                          <Badge color={getStatusColor(export.status)}>{export.status}</Badge>
                        </HStack>
                        <Text size="sm" color="muted">
                          {export.record_count} records • {export.format.toUpperCase()} • {export.created_at}
                        </Text>
                      </VStack>
                      <HStack>
                        {canDownload && export.status === 'completed' && (
                          <Button size="sm" onClick={() => handleDownload(export.id)}>
                            Download
                          </Button>
                        )}
                        {canDelete && (
                          <Button size="sm" variant="ghost" onClick={() => handleDelete(export.id)}>
                            Delete
                          </Button>
                        )}
                      </HStack>
                    </HStack>
                  ))}
                </Stack>
              )}
            </CardBody>
          </Card>
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
