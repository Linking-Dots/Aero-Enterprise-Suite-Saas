import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Input,
  Select,
  Field,
  Toggle,
  Textarea,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const FIELD_TYPES = [
  { value: 'text',      label: 'Text' },
  { value: 'number',    label: 'Number' },
  { value: 'email',     label: 'Email' },
  { value: 'date',      label: 'Date' },
  { value: 'select',    label: 'Select / Dropdown' },
  { value: 'checkbox',  label: 'Checkbox' },
  { value: 'textarea',  label: 'Textarea' },
];

const ENTITY_TYPES = [
  { value: 'employees',   label: 'Employees' },
  { value: 'departments', label: 'Departments' },
  { value: 'users',       label: 'Users' },
  { value: 'contacts',    label: 'Contacts' },
  { value: 'projects',    label: 'Projects' },
];

const emptyField = {
  label:       '',
  field_type:  'text',
  is_required: false,
  sort_order:  0,
  placeholder: '',
  description: '',
  options:     '',
};

export default function CustomFieldsIndex({ entity_types = [], selected_entity = '', fields = [] }) {
  const toast     = useToast();
  const canCreate = useHRMAC('custom_fields.definitions.create');
  const canUpdate = useHRMAC('custom_fields.definitions.update');
  const canDelete = useHRMAC('custom_fields.definitions.delete');

  const [entityFilter, setEntityFilter] = useState(selected_entity || '');
  const [showCreate,   setShowCreate]   = useState(false);
  const [editing,      setEditing]      = useState(null);

  const createForm = useForm({ ...emptyField, entity_type: entityFilter || '' });
  const editForm   = useForm({ ...emptyField, entity_type: '' });

  const applyEntityFilter = (val) => {
    setEntityFilter(val);
    router.get(route('custom-fields.index'), { entity_type: val }, {
      preserveState: true, preserveScroll: true, only: ['fields', 'selected_entity'],
    });
  };

  const handleCreate = (e) => {
    e.preventDefault();
    createForm.post(route('custom-fields.store'), {
      onSuccess: () => { setShowCreate(false); createForm.reset(); toast.success('Field created.'); },
      onError:   () => toast.error('Failed to create field.'),
    });
  };

  const openEdit = (field) => {
    setEditing(field);
    editForm.setData({
      label:       field.label       ?? field.name ?? '',
      field_type:  field.field_type  ?? 'text',
      is_required: !!field.is_required,
      sort_order:  field.sort_order  ?? 0,
      placeholder: field.placeholder ?? '',
      description: field.description ?? '',
      options:     field.options ? (typeof field.options === 'string' ? field.options : JSON.stringify(field.options)) : '',
      entity_type: field.entity_type ?? '',
    });
  };

  const handleEdit = (e) => {
    e.preventDefault();
    if (!editing) return;
    editForm.put(route('custom-fields.update', editing.id), {
      onSuccess: () => { setEditing(null); toast.success('Field updated.'); },
      onError:   () => toast.error('Failed to update field.'),
    });
  };

  const handleDelete = (field) => {
    if (!confirm(`Delete field "${field.label || field.name}"?`)) return;
    router.delete(route('custom-fields.destroy', field.id), {
      onSuccess: () => toast.success('Field deleted.'),
      onError:   () => toast.error('Failed to delete field.'),
    });
  };

  const columns = [
    {
      key: 'label', label: 'Label', width: '25%',
      render: (row) => <Text size="sm">{row.label || row.name}</Text>,
    },
    {
      key: 'field_type', label: 'Type', width: '15%',
      render: (row) => <Badge intent="neutral">{row.field_type_label || row.field_type}</Badge>,
    },
    {
      key: 'is_required', label: 'Required', width: '12%',
      render: (row) => row.is_required
        ? <Badge intent="warning">Required</Badge>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'sort_order', label: 'Order', width: '10%',
      render: (row) => row.sort_order ?? 0,
    },
    {
      key: 'actions', label: '', width: '22%', align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canUpdate && (
            <Button intent="soft" size="sm" leftIcon="pencil" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const entityOptions = [
    { value: '', label: 'All Entity Types' },
    ...(entity_types.length
      ? entity_types.map(et => typeof et === 'string' ? { value: et, label: et } : et)
      : ENTITY_TYPES
    ),
  ];

  const fieldRows = Array.isArray(fields) ? fields : (fields?.data ?? []);

  const renderFieldForm = (form) => (
    <VStack gap={4}>
      <Field label="Label" htmlFor="cf-label" error={form.errors.label} required>
        <Input
          id="cf-label"
          value={form.data.label}
          onChange={e => form.setData('label', e.target.value)}
          placeholder="e.g. Blood Type"
          error={!!form.errors.label}
        />
      </Field>
      <HStack gap={3}>
        <Field label="Field Type" htmlFor="cf-type" error={form.errors.field_type} required>
          <Select
            id="cf-type"
            value={form.data.field_type}
            onChange={e => form.setData('field_type', e.target.value)}
            options={FIELD_TYPES}
          />
        </Field>
        <Field label="Sort Order" htmlFor="cf-order" error={form.errors.sort_order}>
          <Input
            id="cf-order"
            type="number"
            value={form.data.sort_order}
            onChange={e => form.setData('sort_order', parseInt(e.target.value) || 0)}
          />
        </Field>
      </HStack>
      <Field label="Placeholder" htmlFor="cf-placeholder" error={form.errors.placeholder}>
        <Input
          id="cf-placeholder"
          value={form.data.placeholder}
          onChange={e => form.setData('placeholder', e.target.value)}
          placeholder="Optional placeholder text"
        />
      </Field>
      {(form.data.field_type === 'select') && (
        <Field label="Options (one per line or JSON array)" htmlFor="cf-options" error={form.errors.options}>
          <Textarea
            id="cf-options"
            value={form.data.options}
            onChange={e => form.setData('options', e.target.value)}
            placeholder='["Option A","Option B"]'
            rows={3}
          />
        </Field>
      )}
      <Toggle
        label="Required"
        checked={form.data.is_required}
        onChange={checked => form.setData('is_required', checked)}
      />
    </VStack>
  );

  return (
    <>
      <IndexPageLayout
        title="Custom Fields"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Custom Fields' },
        ]}
        description="Define custom fields for entities across the platform."
        actions={
          canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
              Add Field
            </Button>
          )
        }
        filters={
          <HStack gap={3} align="end" wrap>
            <Select
              value={entityFilter}
              onChange={e => applyEntityFilter(e.target.value)}
              options={entityOptions}
            />
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={fieldRows}
            empty="No custom fields defined."
          />
        }
        pagination={
          fields?.last_page > 1 && (
            <Pagination
              page={fields.current_page}
              total={fields.last_page}
              onChange={page => router.get(route('custom-fields.index'), { page, entity_type: entityFilter }, {
                preserveState: true, preserveScroll: true, only: ['fields'],
              })}
            />
          )
        }
      />

      {/* Create Modal */}
      <Modal
        open={showCreate}
        onClose={() => setShowCreate(false)}
        title="Add Custom Field"
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setShowCreate(false)}>Cancel</Button>
            <Button intent="primary" loading={createForm.processing} onClick={handleCreate}>
              Create Field
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleCreate}>{renderFieldForm(createForm)}</form>
      </Modal>

      {/* Edit Modal */}
      <Modal
        open={!!editing}
        onClose={() => setEditing(null)}
        title="Edit Custom Field"
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setEditing(null)}>Cancel</Button>
            <Button intent="primary" loading={editForm.processing} onClick={handleEdit}>
              Update Field
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleEdit}>{renderFieldForm(editForm)}</form>
      </Modal>
    </>
  );
}

CustomFieldsIndex.layout = page => <App title="Custom Fields">{page}</App>;
