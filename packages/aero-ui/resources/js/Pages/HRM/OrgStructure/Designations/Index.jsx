import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack, Field, Input, Select,
  Pagination, Modal, Text,
} from '@aero/ui';

const EMPTY_FORM = { title: '', department_id: '', grade_id: '' };

export default function DesignationsIndex({ designations, departments, grades, filters }) {
  const [search, setSearch]     = useState(filters?.search ?? '');
  const [modalOpen, setModal]   = useState(false);
  const [editing, setEditing]   = useState(null); // null = create mode

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.org.designations.index'),
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
    setData({ title: row.title ?? '', department_id: row.department_id ?? '', grade_id: row.grade_id ?? '' });
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
      put(route('hrm.org.designations.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.org.designations.store'), { onSuccess: closeModal });
    }
  }

  function destroy(row) {
    if (!confirm(`Delete designation "${row.title}"?`)) return;
    router.delete(route('hrm.org.designations.destroy', row.id), { preserveScroll: true });
  }

  const columns = [
    { key: 'title',           label: 'Title' },
    { key: 'department_name', label: 'Department', render: row => row.department?.name ?? <Text tone="secondary">—</Text> },
    { key: 'grade_name',      label: 'Grade',      render: row => row.grade?.name      ?? <Text tone="secondary">—</Text> },
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

  const totalPages  = designations.last_page    ?? 1;
  const currentPage = designations.current_page ?? 1;

  return (
    <>
      <IndexPageLayout
        title="Designations"
        breadcrumb={[{ label: 'HRM' }, { label: 'Org Structure' }, { label: 'Designations' }]}
        actions={
          <Button intent="primary" onClick={openCreate}>
            New Designation
          </Button>
        }
        filters={
          <HStack gap={3} wrap>
            <Field label="Search">
              <Input
                value={search}
                onChange={e => setSearch(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && applyFilters()}
                placeholder="Designation title"
              />
            </Field>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={designations.data ?? []}
            empty="No designations found."
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
        title={editing ? 'Edit Designation' : 'New Designation'}
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
            <Field label="Title" error={errors.title} required>
              <Input
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="e.g. Senior Engineer"
              />
            </Field>

            <Field label="Department" error={errors.department_id}>
              <Select
                value={data.department_id}
                onChange={e => setData('department_id', e.target.value)}
              >
                <option value="">None</option>
                {(departments ?? []).map(d => (
                  <option key={d.id} value={d.id}>{d.name}</option>
                ))}
              </Select>
            </Field>

            <Field label="Grade" error={errors.grade_id}>
              <Select
                value={data.grade_id}
                onChange={e => setData('grade_id', e.target.value)}
              >
                <option value="">None</option>
                {(grades ?? []).map(g => (
                  <option key={g.id} value={g.id}>{g.name}</option>
                ))}
              </Select>
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

DesignationsIndex.layout = page => <App title="Designations">{page}</App>;
