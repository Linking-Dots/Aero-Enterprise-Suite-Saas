import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Field,
  Select,
  Modal,
  Alert,
  useToast,
  useHRMAC,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = { completed: 'success', failed: 'danger', processing: 'warning', pending: 'neutral' };

const ENTITY_OPTIONS = [
  { value: 'users',       label: 'Users' },
  { value: 'employees',   label: 'Employees' },
  { value: 'departments', label: 'Departments' },
  { value: 'leaves',      label: 'Leaves' },
];

export default function ImportsIndex({ imports }) {
  const toast             = useToast();
  const canCreate         = useHRMAC('core.data_export_import.imports.create');
  const canDownloadTpl    = useHRMAC('core.data_export_import.imports.download_template');

  const fileInputRef = useRef(null);
  const [showModal,   setShowModal]   = useState(false);
  const [entityType,  setEntityType]  = useState('users');
  const [file,        setFile]        = useState(null);
  const [submitting,  setSubmitting]  = useState(false);

  const openModal  = () => { setEntityType('users'); setFile(null); setShowModal(true); };
  const closeModal = () => { setShowModal(false); setFile(null); };

  const handleFileChange = e => {
    if (e.target.files?.[0]) setFile(e.target.files[0]);
  };

  const handleImport = () => {
    if (!file) { toast.error('Please select a file.'); return; }
    setSubmitting(true);
    const data = new FormData();
    data.append('entity_type', entityType);
    data.append('file', file);
    router.post(route('core.export-import.imports.store'), data, {
      preserveState: true,
      onSuccess: () => { toast.success('Import job queued.'); closeModal(); },
      onError:   () => toast.error('Failed to start import.'),
      onFinish:  () => setSubmitting(false),
    });
  };

  const handleDownloadTemplate = () => {
    router.get(route('core.export-import.imports.template'), { entity_type: entityType });
  };

  const columns = [
    {
      key: 'entity_type', label: 'Entity', width: '18%',
      render: row => <Text size="sm">{row.entity_type}</Text>,
    },
    {
      key: 'filename', label: 'File', width: '18%',
      render: row => <Text size="sm">{row.filename ?? '—'}</Text>,
    },
    {
      key: 'status', label: 'Status', width: '12%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] || 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'records_imported', label: 'Imported', width: '12%',
      render: row => <Text size="sm">{row.records_imported ?? 0}</Text>,
    },
    {
      key: 'error_count', label: 'Errors', width: '10%',
      render: row => (
        <Badge intent={row.error_count > 0 ? 'danger' : 'neutral'}>{row.error_count ?? 0}</Badge>
      ),
    },
    {
      key: 'created_at', label: 'Started', width: '16%',
      render: row => <Text size="sm">{new Date(row.created_at).toLocaleString()}</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="Data Imports"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Export / Import' },
        { label: 'Imports' },
      ]}
      description="Import entity data from CSV or Excel files."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="upload" onClick={openModal}>
            Import Data
          </Button>
        )
      }
      kpis={[
        <Stat key="total"     title="Total Imports"  value={imports?.total ?? 0} icon="document" />,
        <Stat key="completed" title="Completed"       value={(imports?.data ?? []).filter(i => i.status === 'completed').length} icon="check" iconTone="success" />,
        <Stat key="errors"    title="With Errors"     value={(imports?.data ?? []).filter(i => (i.error_count ?? 0) > 0).length} icon="exclamation" iconTone="danger" />,
      ]}
      table={
        <DataTable
          columns={columns}
          rows={imports?.data || []}
          empty="No imports found."
        />
      }
      pagination={
        imports?.last_page > 1 && (
          <Pagination
            page={imports.current_page}
            total={imports.last_page}
            onChange={page => router.get(route('core.export-import.imports.index'), { page }, {
              preserveState: true, preserveScroll: true, only: ['imports'],
            })}
          />
        )
      }
    >
      <Modal open={showModal} onClose={closeModal} title="Import Data" size="sm">
        <VStack gap={4}>
          <Field label="Entity Type" htmlFor="imp-entity">
            <Select
              id="imp-entity"
              value={entityType}
              onChange={e => setEntityType(e.target.value)}
              options={ENTITY_OPTIONS}
            />
          </Field>

          {canDownloadTpl && (
            <HStack gap={2}>
              <Button intent="ghost" size="sm" leftIcon="download" onClick={handleDownloadTemplate}>
                Download Template
              </Button>
              <Text size="sm" tone="secondary">Download a blank template for this entity</Text>
            </HStack>
          )}

          <Field label="File (CSV / Excel)" htmlFor="imp-file">
            <input
              id="imp-file"
              ref={fileInputRef}
              type="file"
              accept=".csv,.xlsx,.xls"
              className="aeos-text-sm"
              onChange={handleFileChange}
            />
          </Field>

          {file && (
            <Alert intent="info" title={`Selected: ${file.name}`} />
          )}

          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={closeModal}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={!file} onClick={handleImport}>
              Start Import
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

ImportsIndex.layout = page => <App title="Data Imports">{page}</App>;
