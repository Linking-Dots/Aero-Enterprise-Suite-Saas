import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Input,
  Select,
  Modal,
  Checkbox,
  Field,
  Textarea,
} from '@aero/ui';
import App from '../../App.jsx';
import { useHRMAC } from '../../../hooks/useHRMAC';

const FIELD_TYPES = [
  { value: 'text', label: 'Text' },
  { value: 'number', label: 'Number' },
  { value: 'email', label: 'Email' },
  { value: 'date', label: 'Date' },
  { value: 'datetime', label: 'Date Time' },
  { value: 'boolean', label: 'Yes/No' },
  { value: 'select', label: 'Dropdown' },
  { value: 'multi_select', label: 'Multi Select' },
  { value: 'textarea', label: 'Text Area' },
  { value: 'file', label: 'File Upload' },
  { value: 'currency', label: 'Currency' },
];

const ENTITY_TYPES = [
  { value: 'users', label: 'Users' },
  { value: 'employees', label: 'Employees' },
  { value: 'departments', label: 'Departments' },
  { value: 'leaves', label: 'Leaves' },
  { value: 'contacts', label: 'Contacts' },
  { value: 'opportunities', label: 'Opportunities' },
];

const emptyField = {
  name: '',
  entity_type: 'users',
  field_type: 'text',
  options: '',
  validation_rules: '',
  is_required: false,
  is_unique: false,
  is_searchable: true,
  is_filterable: true,
  sort_order: 0,
  placeholder: '',
  description: '',
  is_active: true,
};

export default function CustomFieldsIndex({ fields, filters = {} }) {
  const canCreate = useHRMAC('custom_fields.definitions.create');
  const canUpdate = useHRMAC('custom_fields.definitions.update');
  const canDelete = useHRMAC('custom_fields.definitions.delete');

  const [showCreate, setShowCreate] = useState(false);
  const [editing, setEditing] = useState(null);

  const createForm = useForm({ ...emptyField });
  const editForm = useForm({ ...emptyField });

  const handleCreate = (e) => {
    e.preventDefault();
    createForm.post(route('custom-fields.store'), {
      onSuccess: () => {
        setShowCreate(false);
        createForm.reset();
      },
    });
  };

  const openEdit = (field) => {
    setEditing(field);
    editForm.setData({
      name: field.name ?? '',
      entity_type: field.entity_type ?? 'users',
      field_type: field.field_type ?? 'text',
      options: field.options ? JSON.stringify(field.options) : '',
      validation_rules: field.validation_rules ? JSON.stringify(field.validation_rules) : '',
      is_required: !!field.is_required,
      is_unique: !!field.is_unique,
      is_searchable: !!field.is_searchable,
      is_filterable: !!field.is_filterable,
      sort_order: field.sort_order ?? 0,
      placeholder: field.placeholder ?? '',
      description: field.description ?? '',
      is_active: field.is_active ?? true,
    });
  };

  const handleEdit = (e) => {
    e.preventDefault();
    if (!editing) return;
    editForm.put(route('custom-fields.update', editing.id), {
      onSuccess: () => setEditing(null),
    });
  };

  const handleDelete = (field) => {
    if (!confirm(`Delete custom field "${field.name}"?`)) return;
    router.delete(route('custom-fields.destroy', field.id));
  };

  const handleFilter = (key, value) => {
    router.get(route('custom-fields.index'), { ...filters, [key]: value }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const rows = fields?.data ?? fields ?? [];

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => (
        <VStack gap={0}>
          <Text weight="semibold">{row.name}</Text>
          {row.code && <Text size="sm" tone="secondary">{row.code}</Text>}
        </VStack>
      ),
    },
    {
      key: 'entity_type',
      label: 'Entity',
      render: (row) => <Badge variant="secondary">{row.entity_type}</Badge>,
    },
    {
      key: 'field_type',
      label: 'Type',
      render: (row) => <Badge variant="info">{row.field_type_label || row.field_type}</Badge>,
    },
    {
      key: 'is_required',
      label: 'Required',
      render: (row) => row.is_required ? <Badge variant="warning">Required</Badge> : '—',
    },
    {
      key: 'is_active',
      label: 'Status',
      render: (row) => (
        <Badge variant={row.is_active ? 'success' : 'warning'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canUpdate && (
            <Button variant="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          {canDelete && (
            <Button variant="danger" size="sm" onClick={() => handleDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const renderFieldForm = (form) => (
    <VStack gap={4}>
      <Field label="Field Name" required>
        <Input
          value={form.data.name}
          onChange={(e) => form.setData('name', e.target.value)}
          placeholder="e.g., Blood Type"
          error={form.errors.name}
        />
      </Field>

      <HStack gap={3}>
        <Field label="Entity Type" required>
          <Select
            value={form.data.entity_type}
            onChange={(e) => form.setData('entity_type', e.target.value)}
            options={ENTITY_TYPES}
          />
        </Field>
        <Field label="Field Type" required>
          <Select
            value={form.data.field_type}
            onChange={(e) => form.setData('field_type', e.target.value)}
            options={FIELD_TYPES}
          />
        </Field>
        <Field label="Sort Order">
          <Input
            type="number"
            value={form.data.sort_order}
            onChange={(e) => form.setData('sort_order', parseInt(e.target.value) || 0)}
          />
        </Field>
      </HStack>

      <Field label="Placeholder">
        <Input
          value={form.data.placeholder}
          onChange={(e) => form.setData('placeholder', e.target.value)}
        />
      </Field>

      <Field label="Description">
        <Textarea
          value={form.data.description}
          onChange={(e) => form.setData('description', e.target.value)}
          rows={2}
        />
      </Field>

      {(form.data.field_type === 'select' || form.data.field_type === 'multi_select') && (
        <Field label="Options (JSON array)">
          <Textarea
            value={form.data.options}
            onChange={(e) => form.setData('options', e.target.value)}
            placeholder='["Option 1", "Option 2", "Option 3"]'
            rows={2}
          />
        </Field>
      )}

      <Field label="Validation Rules (JSON)">
        <Textarea
          value={form.data.validation_rules}
          onChange={(e) => form.setData('validation_rules', e.target.value)}
          placeholder='{"min": 1, "max": 100}'
          rows={2}
        />
      </Field>

      <HStack gap={4}>
        <Checkbox
          checked={form.data.is_required}
          onChange={(checked) => form.setData('is_required', checked)}
          label="Required"
        />
        <Checkbox
          checked={form.data.is_unique}
          onChange={(checked) => form.setData('is_unique', checked)}
          label="Unique"
        />
        <Checkbox
          checked={form.data.is_searchable}
          onChange={(checked) => form.setData('is_searchable', checked)}
          label="Searchable"
        />
        <Checkbox
          checked={form.data.is_filterable}
          onChange={(checked) => form.setData('is_filterable', checked)}
          label="Filterable"
        />
        <Checkbox
          checked={form.data.is_active}
          onChange={(checked) => form.setData('is_active', checked)}
          label="Active"
        />
      </HStack>
    </VStack>
  );

  return (
    <IndexPageLayout
      title="Custom Fields"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Custom Fields' },
      ]}
      description="Define custom field definitions for your entities."
      actions={
        canCreate && (
          <Button variant="primary" onClick={() => setShowCreate(true)}>
            Add Field
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <HStack gap={3} align="end">
          <Input
            placeholder="Search fields..."
            value={filters.search || ''}
            onChange={(e) => handleFilter('search', e.target.value)}
            className="flex-1"
          />
          <Select
            value={filters.entity_type || ''}
            onChange={(e) => handleFilter('entity_type', e.target.value)}
            options={[
              { value: '', label: 'All Entity Types' },
              ...ENTITY_TYPES,
            ]}
          />
        </HStack>

        <DataTable
          columns={columns}
          rows={rows}
          empty="No custom fields defined."
        />

        {fields?.last_page > 1 && (
          <div className="flex justify-center">
            <Text size="sm">
              Page {fields.current_page} of {fields.last_page}
            </Text>
          </div>
        )}
      </VStack>

      {/* Create Modal */}
      <Modal
        open={showCreate}
        onClose={() => setShowCreate(false)}
        title="Add Custom Field"
        size="lg"
        footer={
          <>
            <Button variant="ghost" onClick={() => setShowCreate(false)}>Cancel</Button>
            <Button variant="primary" onClick={handleCreate} disabled={createForm.processing}>
              {createForm.processing ? 'Creating...' : 'Create Field'}
            </Button>
          </>
        }
      >
        <form onSubmit={handleCreate}>
          {renderFieldForm(createForm)}
        </form>
      </Modal>

      {/* Edit Modal */}
      <Modal
        open={!!editing}
        onClose={() => setEditing(null)}
        title="Edit Custom Field"
        size="lg"
        footer={
          <>
            <Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button>
            <Button variant="primary" onClick={handleEdit} disabled={editForm.processing}>
              {editForm.processing ? 'Updating...' : 'Update Field'}
            </Button>
          </>
        }
      >
        <form onSubmit={handleEdit}>
          {renderFieldForm(editForm)}
        </form>
      </Modal>
    </IndexPageLayout>
  );
}

CustomFieldsIndex.layout = (page) => <App title="Custom Fields">{page}</App>;
