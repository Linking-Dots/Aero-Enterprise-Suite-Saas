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
  Field,
  Select,
  Modal,
  useToast,
  useHRMAC,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = { completed: 'success', failed: 'danger', processing: 'warning', pending: 'neutral' };

const ENTITY_OPTIONS = [
  { value: 'users',       label: 'Users' },
  { value: 'employees',   label: 'Employees' },
  { value: 'payroll',     label: 'Payroll' },
  { value: 'leaves',      label: 'Leaves' },
  { value: 'departments', label: 'Departments' },
];

const FORMAT_OPTIONS = [
  { value: 'csv',  label: 'CSV' },
  { value: 'json', label: 'JSON' },
  { value: 'xlsx', label: 'Excel (.xlsx)' },
];

export default function ExportsIndex({ exports }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('core.data_export_import.exports.create');
  const canDownload = useHRMAC('core.data_export_import.exports.download');
  const canDelete  = useHRMAC('core.data_export_import.exports.delete');

  const [showModal,   setShowModal]   = useState(false);
  const [entityType,  setEntityType]  = useState('users');
  const [format,      setFormat]      = useState('csv');
  const [submitting,  setSubmitting]  = useState(false);

  const openModal  = () => { setEntityType('users'); setFormat('csv'); setShowModal(true); };
  const closeModal = () => setShowModal(false);

  const handleCreate = () => {
    setSubmitting(true);
    router.post(route('core.export-import.exports.store'), { entity_type: entityType, format }, {
      preserveState: true,
      onSuccess: () => { toast.success('Export job queued.'); closeModal(); },
      onError:   () => toast.error('Failed to create export.'),
      onFinish:  () => setSubmitting(false),
    });
  };

  const handleDownload = id => {
    router.get(route('core.export-import.exports.download', id));
  };

  const handleDelete = id => {
    if (!confirm('Delete this export?')) return;
    router.delete(route('core.export-import.exports.destroy', id), {
      preserveState: true,
      onSuccess: () => toast.success('Export deleted.'),
      onError:   () => toast.error('Failed to delete export.'),
    });
  };

  const columns = [
    {
      key: 'entity_type', label: 'Entity', width: '18%',
      render: row => <Text size="sm">{row.entity_type}</Text>,
    },
    {
      key: 'format', label: 'Format', width: '10%',
      render: row => <Badge intent="neutral">{(row.format || '').toUpperCase()}</Badge>,
    },
    {
      key: 'status', label: 'Status', width: '14%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] || 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'file_size', label: 'Size', width: '12%',
      render: row => <Text size="sm" tone="secondary">{row.file_size ?? '—'}</Text>,
    },
    {
      key: 'created_at', label: 'Created', width: '16%',
      render: row => <Text size="sm">{new Date(row.created_at).toLocaleString()}</Text>,
    },
    {
      key: 'actions', label: '', width: '30%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canDownload && row.status === 'completed' && (
            <Button intent="soft" size="sm" leftIcon="download" onClick={() => handleDownload(row.id)}>
              Download
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
      title="Data Exports"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Export / Import' },
        { label: 'Exports' },
      ]}
      description="Export entity data to CSV, JSON, or Excel."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="download" onClick={openModal}>
            Create Export
          </Button>
        )
      }
      kpis={[
        <Stat key="total"     title="Total Exports"     value={exports?.total ?? 0}   icon="document" />,
        <Stat key="completed" title="Completed"          value={(exports?.data ?? []).filter(e => e.status === 'completed').length} icon="check" iconTone="success" />,
        <Stat key="failed"    title="Failed"             value={(exports?.data ?? []).filter(e => e.status === 'failed').length}    icon="exclamation" iconTone="danger" />,
      ]}
      table={
        <DataTable
          columns={columns}
          rows={exports?.data || []}
          empty="No exports found."
        />
      }
      pagination={
        exports?.last_page > 1 && (
          <Pagination
            page={exports.current_page}
            total={exports.last_page}
            onChange={page => router.get(route('core.export-import.exports.index'), { page }, {
              preserveState: true, preserveScroll: true, only: ['exports'],
            })}
          />
        )
      }
    >
      <Modal open={showModal} onClose={closeModal} title="Create Export" size="sm">
        <VStack gap={4}>
          <Field label="Entity Type" htmlFor="exp-entity">
            <Select
              id="exp-entity"
              value={entityType}
              onChange={e => setEntityType(e.target.value)}
              options={ENTITY_OPTIONS}
            />
          </Field>
          <Field label="Format" htmlFor="exp-format">
            <Select
              id="exp-format"
              value={format}
              onChange={e => setFormat(e.target.value)}
              options={FORMAT_OPTIONS}
            />
          </Field>
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={closeModal}>Cancel</Button>
            <Button intent="primary" loading={submitting} onClick={handleCreate}>
              Start Export
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

ExportsIndex.layout = page => <App title="Data Exports">{page}</App>;
