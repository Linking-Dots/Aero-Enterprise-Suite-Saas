import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Field, Input, Textarea, Toggle, Pagination,
  Modal, Badge, Text,
} from '@aero/ui';

const EMPTY_FORM = {
  name:        '',
  description: '',
  color:       '#6366f1',
  is_active:   true,
};

function CategoryDot({ color }) {
  return (
    <span className="inline-block w-3 h-3 rounded-full mr-2 flex-shrink-0" style={{ background: color }} />
  );
}

export default function CategoriesIndex({ categories, filters }) {
  const canManage = useHRMAC('hrm.training.categories.create');

  const [search, setSearch]   = useState(filters?.search ?? '');
  const [modalOpen, setModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.training.categories.index'),
      { ...filters, search: search || undefined, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function openCreate() {
    clearErrors();
    reset();
    setEditing(null);
    setModal(true);
  }

  function openEdit(row) {
    clearErrors();
    setData({
      name:        row.name        ?? '',
      description: row.description ?? '',
      color:       row.color       ?? '#6366f1',
      is_active:   row.is_active   ?? true,
    });
    setEditing(row);
    setModal(true);
  }

  function closeModal() {
    setModal(false);
    setEditing(null);
  }

  function submit(e) {
    e.preventDefault();
    if (editing) {
      put(route('hrm.training.categories.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.training.categories.store'), { onSuccess: closeModal });
    }
  }

  function confirmDelete(row) {
    setDeleteTarget(row);
  }

  function closeDeleteModal() {
    setDeleteTarget(null);
  }

  function doDelete() {
    router.delete(route('hrm.training.categories.destroy', deleteTarget.id), {
      preserveScroll: true,
      onFinish: closeDeleteModal,
    });
  }

  const totalPages  = categories.last_page    ?? 1;
  const currentPage = categories.current_page ?? 1;

  const columns = [
    {
      key: 'name', label: 'Name',
      render: row => (
        <HStack gap={2} align="center">
          <CategoryDot color={row.color} />
          <Text>{row.name}</Text>
        </HStack>
      ),
    },
    {
      key: 'courses_count', label: 'Courses',
      render: row => <Text>{row.courses_count ?? 0}</Text>,
    },
    {
      key: 'is_active', label: 'Status',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <HStack gap={1}>
          {canManage && (
            <>
              <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>Edit</Button>
              <Button intent="danger" size="sm" onClick={() => confirmDelete(row)}>Delete</Button>
            </>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Training Categories"
        breadcrumb={[{ label: 'HRM' }, { label: 'Training' }, { label: 'Categories' }]}
        actions={
          <HStack gap={3} align="center" wrap>
            <Input
              placeholder="Search categories..."
              defaultValue={filters?.search ?? ''}
              onBlur={e => applyFilters({ search: e.target.value, page: 1 })}
            />
            {canManage && (
              <Button intent="primary" onClick={openCreate}>
                New Category
              </Button>
            )}
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={categories.data ?? []}
            empty="No categories found."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page => applyFilters({ page })}
            />
          )
        }
      />

      {/* Create / Edit Modal */}
      <Modal
        open={modalOpen}
        onClose={closeModal}
        title={editing ? 'Edit Category' : 'New Category'}
        size="md"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={processing} onClick={submit}>
              {editing ? 'Save Changes' : 'Create'}
            </Button>
            <Button type="button" intent="ghost" onClick={closeModal}>Cancel</Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={4}>
            <Field label="Name" error={errors.name} required>
              <Input
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="e.g. Technical Skills"
              />
            </Field>

            <Field label="Description" error={errors.description} hint="Optional">
              <Textarea
                value={data.description}
                onChange={e => setData('description', e.target.value)}
                placeholder="Describe this category..."
                rows={3}
              />
            </Field>

            <Field label="Color" error={errors.color}>
              <Input
                type="color"
                value={data.color}
                onChange={e => setData('color', e.target.value)}
              />
            </Field>

            <Toggle
              label="Active"
              checked={data.is_active}
              onChange={e => setData('is_active', e.target.checked)}
            />
          </VStack>
        </form>
      </Modal>

      {/* Delete Confirm Modal */}
      <Modal
        open={!!deleteTarget}
        onClose={closeDeleteModal}
        title="Delete Category"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="danger" onClick={doDelete}>Delete</Button>
            <Button type="button" intent="ghost" onClick={closeDeleteModal}>Cancel</Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          Are you sure you want to delete &ldquo;{deleteTarget?.name}&rdquo;? This cannot be undone.
        </Text>
      </Modal>
    </>
  );
}

CategoriesIndex.layout = page => <App title="Training Categories">{page}</App>;
