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
  is_active:   true,
};

export default function AssetCategoriesIndex({ categories }) {
  const canManage = useHRMAC('hrm.assets.asset-categories.manage');

  const [modalOpen,     setModal]       = useState(false);
  const [editing,       setEditing]     = useState(null);
  const [deleteTarget,  setDeleteTarget] = useState(null);

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.assets.categories.index'),
      { ...overrides },
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
      put(route('hrm.assets.categories.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.assets.categories.store'), { onSuccess: closeModal });
    }
  }

  function confirmDelete(row) {
    setDeleteTarget(row);
  }

  function closeDeleteModal() {
    setDeleteTarget(null);
  }

  function doDelete() {
    router.delete(route('hrm.assets.categories.destroy', deleteTarget.id), {
      preserveScroll: true,
      onFinish: closeDeleteModal,
    });
  }

  const totalPages  = categories.last_page    ?? 1;
  const currentPage = categories.current_page ?? 1;

  const columns = [
    {
      key: 'name', label: 'Name',
      render: row => <Text>{row.name}</Text>,
    },
    {
      key: 'description', label: 'Description',
      render: row => <Text tone="secondary">{row.description ?? '—'}</Text>,
    },
    {
      key: 'assets_count', label: 'Assets Count',
      render: row => <Text>{row.assets_count ?? 0}</Text>,
    },
    {
      key: 'is_active', label: 'Active',
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
              {(row.assets_count ?? 0) === 0 && (
                <Button intent="danger" size="sm" onClick={() => confirmDelete(row)}>Delete</Button>
              )}
            </>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Asset Categories"
        breadcrumb={[{ label: 'HRM' }, { label: 'Assets' }, { label: 'Categories' }]}
        actions={
          <HStack gap={3} align="center" wrap>
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
                placeholder="e.g. IT Equipment"
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

AssetCategoriesIndex.layout = page => <App title="Asset Categories">{page}</App>;
