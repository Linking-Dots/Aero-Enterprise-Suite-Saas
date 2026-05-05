import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  Select,
  HStack, VStack, Stack,
  Text, Heading,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function ImportsIndex({ entity_type, entities }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.data_export_import.imports.create');
  const canDownloadTemplate = useHRMAC('core.data_export_import.imports.download_template');

  const [selectedEntity, setSelectedEntity] = useState(entity_type);

  const form = useForm({
    entity_type: selectedEntity,
    file: null,
    format: 'csv',
    options: {},
  });

  const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      form.setData('file', e.target.files[0]);
    }
  };

  const handleImport = (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('entity_type', form.data.entity_type);
    formData.append('file', form.data.file);
    formData.append('format', form.data.format);
    formData.append('options', JSON.stringify(form.data.options));

    router.post(route('core.export-import.imports.create'), formData, {
      onSuccess: (response) => {
        toast.success('Import completed successfully');
        form.reset();
      },
      onError: () => {
        toast.error('Import failed');
      },
    });
  };

  const handleDownloadTemplate = () => {
    window.location.href = route('core.export-import.imports.template', { entity: selectedEntity });
  };

  return (
    <App title="Data Imports">
      <IndexPageLayout
        title="Data Imports"
        breadcrumbs={[
          { label: 'Core', href: route('core.dashboard') },
          { label: 'Export/Import', href: '#' },
          { label: 'Imports' },
        ]}
      >
        <VStack spacing={6}>
          {canCreate && (
            <Card>
              <CardHeader>
                <Heading size="md">Import Data</Heading>
              </CardHeader>
              <CardBody>
                <form onSubmit={handleImport}>
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
                        ]}
                      />
                    </HStack>
                    <input
                      type="file"
                      accept=".csv,.json"
                      onChange={handleFileChange}
                    />
                    {form.data.file && (
                      <Text size="sm">Selected: {form.data.file.name}</Text>
                    )}
                    <HStack>
                      <Button type="submit" isLoading={form.processing}>
                        Import Data
                      </Button>
                      {canDownloadTemplate && (
                        <Button variant="outline" onClick={handleDownloadTemplate}>
                          Download Template
                        </Button>
                      )}
                    </HStack>
                  </VStack>
                </form>
              </CardBody>
            </Card>
          )}

          <Card>
            <CardHeader>
              <Heading size="md">Import Instructions</Heading>
            </CardHeader>
            <CardBody>
              <VStack align="start" spacing={2}>
                <Text>1. Select the entity type you want to import data into</Text>
                <Text>2. Download the template file to see the required format</Text>
                <Text>3. Fill in your data following the template structure</Text>
                <Text>4. Upload your file and click Import</Text>
                <Text color="muted" size="sm">
                  Note: Maximum file size is 2MB. Supported formats: CSV, JSON
                </Text>
              </VStack>
            </CardBody>
          </Card>
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
