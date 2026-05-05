import { Link } from '@inertiajs/react';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../App';
import {
  Button,
  Field,
  Input,
  Label,
  Select,
  Textarea,
  Toggle,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import {
  PlusIcon,
  CheckIcon,
  XIcon,
  PencilIcon,
  TrashIcon,
} from '@heroicons/react/24/outline';

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
  { value: 'leaves', label: 'Leaves' },
  { value: 'contacts', label: 'Contacts' },
  { value: 'opportunities', label: 'Opportunities' },
];

export default function CustomFieldsIndex({ fields, filters }) {
  const { toast } = useToast();
  const canCreate = useHRMAC('custom_fields.definitions.create');
  const canUpdate = useHRMAC('custom_fields.definitions.update');
  const canDelete = useHRMAC('custom_fields.definitions.delete');
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editingField, setEditingField] = useState(null);

  const createForm = useForm({
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
  });

  const editForm = useForm({
    name: '',
    entity_type: '',
    field_type: '',
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
  });

  const handleCreateSubmit = (e) => {
    e.preventDefault();
    createForm.post(route('core.custom-fields.store'), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Custom field created successfully',
        });
        setIsCreateModalOpen(false);
        createForm.reset();
      },
      onError: (errors) => {
        toast({
          title: 'Error',
          description: 'Failed to create custom field',
          variant: 'destructive',
        });
      },
    });
  };

  const handleEditSubmit = (e) => {
    e.preventDefault();
    editForm.put(route('core.custom-fields.update', editingField.id), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Custom field updated successfully',
        });
        setIsEditModalOpen(false);
        setEditingField(null);
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Failed to update custom field',
          variant: 'destructive',
        });
      },
    });
  };

  const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this custom field?')) {
      router.delete(route('core.custom-fields.destroy', id), {
        onSuccess: () => {
          toast({
            title: 'Success',
            description: 'Custom field deleted successfully',
          });
        },
      });
    }
  };

  const openEditModal = (field) => {
    setEditingField(field);
    editForm.setData({
      name: field.name,
      entity_type: field.entity_type,
      field_type: field.field_type,
      options: field.options ? JSON.stringify(field.options) : '',
      validation_rules: field.validation_rules ? JSON.stringify(field.validation_rules) : '',
      is_required: field.is_required,
      is_unique: field.is_unique,
      is_searchable: field.is_searchable,
      is_filterable: field.is_filterable,
      sort_order: field.sort_order,
      placeholder: field.placeholder || '',
      description: field.description || '',
      is_active: field.is_active,
    });
    setIsEditModalOpen(true);
  };

  const handleFilterChange = (key, value) => {
    router.get(route('core.custom-fields.index'), { ...filters, [key]: value }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <App title="Custom Fields">
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">Custom Fields</h1>
            <p className="text-muted-foreground">Manage custom field definitions for your entities</p>
          </div>
          {canCreate && (
            <Button onClick={() => setIsCreateModalOpen(true)}>
              <PlusIcon className="mr-2 h-4 w-4" />
              Add Field
            </Button>
          )}
        </div>

        <div className="flex gap-4">
          <Input
            placeholder="Search fields..."
            value={filters.search || ''}
            onChange={(e) => handleFilterChange('search', e.target.value)}
            className="max-w-xs"
          />
          <Select
            value={filters.entity_type || ''}
            onValueChange={(value) => handleFilterChange('entity_type', value)}
          >
            <SelectTrigger className="max-w-xs">
              <SelectValue placeholder="Filter by entity type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">All Entity Types</SelectItem>
              {ENTITY_TYPES.map((type) => (
                <SelectItem key={type.value} value={type.value}>
                  {type.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="rounded-md border">
          <div className="relative w-full overflow-auto">
            <table className="w-full caption-bottom text-sm">
              <thead className="[&_tr]:border-b">
                <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                  <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                  <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Code</th>
                  <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Entity Type</th>
                  <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                  <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Required</th>
                  <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Active</th>
                  <th className="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                </tr>
              </thead>
              <tbody>
                {fields.data.map((field) => (
                  <tr key={field.id} className="border-b transition-colors hover:bg-muted/50">
                    <td className="p-4 align-middle font-medium">{field.name}</td>
                    <td className="p-4 align-middle text-muted-foreground">{field.code}</td>
                    <td className="p-4 align-middle capitalize">{field.entity_type}</td>
                    <td className="p-4 align-middle">{field.field_type_label}</td>
                    <td className="p-4 align-middle">
                      {field.is_required ? (
                        <CheckIcon className="h-4 w-4 text-green-500" />
                      ) : (
                        <XIcon className="h-4 w-4 text-gray-400" />
                      )}
                    </td>
                    <td className="p-4 align-middle">
                      {field.is_active ? (
                        <CheckIcon className="h-4 w-4 text-green-500" />
                      ) : (
                        <XIcon className="h-4 w-4 text-gray-400" />
                      )}
                    </td>
                    <td className="p-4 align-middle text-right">
                      <div className="flex justify-end gap-2">
                        {canUpdate && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => openEditModal(field)}
                          >
                            <PencilIcon className="h-4 w-4" />
                          </Button>
                        )}
                        {canDelete && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleDelete(field.id)}
                          >
                            <TrashIcon className="h-4 w-4 text-red-500" />
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Create Modal */}
        <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Create Custom Field</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Field Name *</Label>
                  <Input
                    id="name"
                    value={createForm.data.name}
                    onChange={(e) => createForm.setData('name', e.target.value)}
                    error={createForm.errors.name}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="entity_type">Entity Type *</Label>
                  <Select
                    value={createForm.data.entity_type}
                    onValueChange={(value) => createForm.setData('entity_type', value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {ENTITY_TYPES.map((type) => (
                        <SelectItem key={type.value} value={type.value}>
                          {type.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="field_type">Field Type *</Label>
                  <Select
                    value={createForm.data.field_type}
                    onValueChange={(value) => createForm.setData('field_type', value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {FIELD_TYPES.map((type) => (
                        <SelectItem key={type.value} value={type.value}>
                          {type.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="sort_order">Sort Order</Label>
                  <Input
                    id="sort_order"
                    type="number"
                    value={createForm.data.sort_order}
                    onChange={(e) => createForm.setData('sort_order', parseInt(e.target.value))}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="placeholder">Placeholder</Label>
                <Input
                  id="placeholder"
                  value={createForm.data.placeholder}
                  onChange={(e) => createForm.setData('placeholder', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={createForm.data.description}
                  onChange={(e) => createForm.setData('description', e.target.value)}
                />
              </div>

              {(createForm.data.field_type === 'select' || createForm.data.field_type === 'multi_select') && (
                <div className="space-y-2">
                  <Label htmlFor="options">Options (JSON array)</Label>
                  <Textarea
                    id="options"
                    placeholder='["Option 1", "Option 2", "Option 3"]'
                    value={createForm.data.options}
                    onChange={(e) => createForm.setData('options', e.target.value)}
                  />
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="validation_rules">Validation Rules (JSON object)</Label>
                <Textarea
                  id="validation_rules"
                  placeholder='{"min": 1, "max": 100}'
                  value={createForm.data.validation_rules}
                  onChange={(e) => createForm.setData('validation_rules', e.target.value)}
                />
              </div>

              <div className="flex flex-wrap gap-4">
                <div className="flex items-center space-x-2">
                  <Switch
                    id="is_required"
                    checked={createForm.data.is_required}
                    onCheckedChange={(checked) => createForm.setData('is_required', checked)}
                  />
                  <Label htmlFor="is_required">Required</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="is_unique"
                    checked={createForm.data.is_unique}
                    onCheckedChange={(checked) => createForm.setData('is_unique', checked)}
                  />
                  <Label htmlFor="is_unique">Unique</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="is_searchable"
                    checked={createForm.data.is_searchable}
                    onCheckedChange={(checked) => createForm.setData('is_searchable', checked)}
                  />
                  <Label htmlFor="is_searchable">Searchable</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="is_filterable"
                    checked={createForm.data.is_filterable}
                    onCheckedChange={(checked) => createForm.setData('is_filterable', checked)}
                  />
                  <Label htmlFor="is_filterable">Filterable</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="is_active"
                    checked={createForm.data.is_active}
                    onCheckedChange={(checked) => createForm.setData('is_active', checked)}
                  />
                  <Label htmlFor="is_active">Active</Label>
                </div>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={createForm.processing}>
                  {createForm.processing ? 'Creating...' : 'Create Field'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        {/* Edit Modal */}
        <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Edit Custom Field</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleEditSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="edit_name">Field Name *</Label>
                  <Input
                    id="edit_name"
                    value={editForm.data.name}
                    onChange={(e) => editForm.setData('name', e.target.value)}
                    error={editForm.errors.name}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit_entity_type">Entity Type *</Label>
                  <Select
                    value={editForm.data.entity_type}
                    onValueChange={(value) => editForm.setData('entity_type', value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {ENTITY_TYPES.map((type) => (
                        <SelectItem key={type.value} value={type.value}>
                          {type.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="edit_field_type">Field Type *</Label>
                  <Select
                    value={editForm.data.field_type}
                    onValueChange={(value) => editForm.setData('field_type', value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {FIELD_TYPES.map((type) => (
                        <SelectItem key={type.value} value={type.value}>
                          {type.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit_sort_order">Sort Order</Label>
                  <Input
                    id="edit_sort_order"
                    type="number"
                    value={editForm.data.sort_order}
                    onChange={(e) => editForm.setData('sort_order', parseInt(e.target.value))}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="edit_placeholder">Placeholder</Label>
                <Input
                  id="edit_placeholder"
                  value={editForm.data.placeholder}
                  onChange={(e) => editForm.setData('placeholder', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="edit_description">Description</Label>
                <Textarea
                  id="edit_description"
                  value={editForm.data.description}
                  onChange={(e) => editForm.setData('description', e.target.value)}
                />
              </div>

              {(editForm.data.field_type === 'select' || editForm.data.field_type === 'multi_select') && (
                <div className="space-y-2">
                  <Label htmlFor="edit_options">Options (JSON array)</Label>
                  <Textarea
                    id="edit_options"
                    placeholder='["Option 1", "Option 2", "Option 3"]'
                    value={editForm.data.options}
                    onChange={(e) => editForm.setData('options', e.target.value)}
                  />
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="edit_validation_rules">Validation Rules (JSON object)</Label>
                <Textarea
                  id="edit_validation_rules"
                  placeholder='{"min": 1, "max": 100}'
                  value={editForm.data.validation_rules}
                  onChange={(e) => editForm.setData('validation_rules', e.target.value)}
                />
              </div>

              <div className="flex flex-wrap gap-4">
                <div className="flex items-center space-x-2">
                  <Switch
                    id="edit_is_required"
                    checked={editForm.data.is_required}
                    onCheckedChange={(checked) => editForm.setData('is_required', checked)}
                  />
                  <Label htmlFor="edit_is_required">Required</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="edit_is_unique"
                    checked={editForm.data.is_unique}
                    onCheckedChange={(checked) => editForm.setData('is_unique', checked)}
                  />
                  <Label htmlFor="edit_is_unique">Unique</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="edit_is_searchable"
                    checked={editForm.data.is_searchable}
                    onCheckedChange={(checked) => editForm.setData('is_searchable', checked)}
                  />
                  <Label htmlFor="edit_is_searchable">Searchable</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="edit_is_filterable"
                    checked={editForm.data.is_filterable}
                    onCheckedChange={(checked) => editForm.setData('is_filterable', checked)}
                  />
                  <Label htmlFor="edit_is_filterable">Filterable</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="edit_is_active"
                    checked={editForm.data.is_active}
                    onCheckedChange={(checked) => editForm.setData('is_active', checked)}
                  />
                  <Label htmlFor="edit_is_active">Active</Label>
                </div>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsEditModalOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={editForm.processing}>
                  {editForm.processing ? 'Updating...' : 'Update Field'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>
    </App>
  );
}

CustomFieldsIndex.layout = (page) => <App title="Custom Fields">{page}</App>;
