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

export default function CategoriesIndex({ categories }) {
  const canManage = useHRMAC('hrm.expenses.expense-categories.manage');

  const [modalOpen, setModal]       = useState(false);
  const [editing, setEditing]       = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.expenses.categories.index'),
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
      put(route('hrm.expenses.categories.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.expenses.categories.store'), { onSuccess: closeModal });
    }
  }

  function confirmDelete(row) {
    setDeleteTarget(row);
  }

  function closeDeleteModal() {
    setDeleteTarget(null);
  }

  function doDelete() {
    router.delete(route('hrm.expenses.categories.destroy', deleteTarget.id), {
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
      render: row => <Text tone="secondary">{row.description || '—'}</Text>,
    },
    {
      key: 'items_count', label: 'Items Count',
      render: row => <Text>{row.items_count ?? 0}</Text>,
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
              {row.items_count === 0 && (
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
        title="Expense Categories"
        breadcrumb={[{ label: 'HRM' }, { label: 'Expenses' }, { label: 'Categories' }]}
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
            empty="No expense categories found."
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
                placeholder="e.g. Travel"
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

CategoriesIndex.layout = page => <App title="Expense Categories">{page}</App>;
