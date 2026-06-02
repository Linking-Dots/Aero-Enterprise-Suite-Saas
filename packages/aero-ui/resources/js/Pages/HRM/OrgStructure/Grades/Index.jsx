import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack, Field, Input, Select,
  Pagination, Modal, Badge, Toggle, Text,
} from '@aero/ui';

const EMPTY_FORM = {
  name:       '',
  code:       '',
  min_salary: '',
  max_salary: '',
  is_active:  true,
};

export default function GradesIndex({ grades, filters }) {
  const [search, setSearch]   = useState(filters?.search ?? '');
  const [modalOpen, setModal] = useState(false);
  const [editing, setEditing] = useState(null);

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.org.grades.index'),
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
      name:       row.name       ?? '',
      code:       row.code       ?? '',
      min_salary: row.min_salary ?? '',
      max_salary: row.max_salary ?? '',
      is_active:  row.is_active  ?? true,
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
      put(route('hrm.org.grades.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.org.grades.store'), { onSuccess: closeModal });
    }
  }

  function destroy(row) {
    if (!confirm(`Delete grade "${row.name}"?`)) return;
    router.delete(route('hrm.org.grades.destroy', row.id), { preserveScroll: true });
  }

  const columns = [
    { key: 'name',       label: 'Name' },
    { key: 'code',       label: 'Code', mono: true },
    { key: 'min_salary', label: 'Min Salary', align: 'right', render: row => row.min_salary ?? <Text tone="secondary">—</Text> },
    { key: 'max_salary', label: 'Max Salary', align: 'right', render: row => row.max_salary ?? <Text tone="secondary">—</Text> },
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
          <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          <Button intent="danger" size="sm" onClick={() => destroy(row)}>Delete</Button>
        </HStack>
      ),
    },
  ];

  const totalPages  = grades.last_page    ?? 1;
  const currentPage = grades.current_page ?? 1;

  return (
    <>
      <IndexPageLayout
        title="Grades"
        breadcrumb={[{ label: 'HRM' }, { label: 'Org Structure' }, { label: 'Grades' }]}
        actions={
          <Button intent="primary" onClick={openCreate}>
            New Grade
          </Button>
        }
        filters={
          <HStack gap={3} wrap>
            <Field label="Search">
              <Input
                value={search}
                onChange={e => setSearch(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && applyFilters()}
                placeholder="Grade name or code"
              />
            </Field>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={grades.data ?? []}
            empty="No grades found."
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
        title={editing ? 'Edit Grade' : 'New Grade'}
        size="md"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={processing} onClick={submit}>
              {editing ? 'Save Changes' : 'Create'}
            </Button>
            <Button type="button" intent="ghost" onClick={closeModal}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={4}>
            <Field label="Name" error={errors.name} required>
              <Input
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="e.g. L3 — Senior"
              />
            </Field>

            <Field label="Code" error={errors.code}>
              <Input
                value={data.code}
                onChange={e => setData('code', e.target.value)}
                placeholder="e.g. L3"
              />
            </Field>

            <HStack gap={3}>
              <Field label="Min Salary" error={errors.min_salary}>
                <Input
                  type="number"
                  value={data.min_salary}
                  onChange={e => setData('min_salary', e.target.value)}
                  placeholder="0.00"
                />
              </Field>

              <Field label="Max Salary" error={errors.max_salary}>
                <Input
                  type="number"
                  value={data.max_salary}
                  onChange={e => setData('max_salary', e.target.value)}
                  placeholder="0.00"
                />
              </Field>
            </HStack>

            <Toggle
              label="Active"
              checked={data.is_active}
              onChange={e => setData('is_active', e.target.checked)}
            />
          </VStack>
        </form>
      </Modal>
    </>
  );
}

GradesIndex.layout = page => <App title="Grades">{page}</App>;
