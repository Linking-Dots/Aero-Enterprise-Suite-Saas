import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack, Field, Input, Select,
  Pagination, Modal, Badge, Toggle, Text,
} from '@aero/ui';

const EMPTY_FORM = {
  name:               '',
  code:               '',
  color:              '#3b82f6',
  days_per_year:      0,
  max_carry_forward:  '',
  is_paid:            true,
  requires_approval:  true,
  carry_forward:      false,
  encashable:         false,
  is_active:          true,
};

export default function LeaveTypesIndex({ types }) {
  const [modalOpen, setModal] = useState(false);
  const [editing, setEditing] = useState(null);

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  function openCreate() {
    clearErrors();
    reset();
    setEditing(null);
    setModal(true);
  }

  function openEdit(row) {
    clearErrors();
    setData({
      name:              row.name              ?? '',
      code:              row.code              ?? '',
      color:             row.color             ?? '#3b82f6',
      days_per_year:     row.days_per_year     ?? 0,
      max_carry_forward: row.max_carry_forward ?? '',
      is_paid:           row.is_paid           ?? true,
      requires_approval: row.requires_approval ?? true,
      carry_forward:     row.carry_forward     ?? false,
      encashable:        row.encashable        ?? false,
      is_active:         row.is_active         ?? true,
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
      put(route('hrm.leave.types.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.leave.types.store'), { onSuccess: closeModal });
    }
  }

  function destroy(row) {
    if (!confirm(`Delete leave type "${row.name}"?`)) return;
    router.delete(route('hrm.leave.types.destroy', row.id), { preserveScroll: true });
  }

  const columns = [
    {
      key: 'name', label: 'Name',
      render: row => (
        <HStack gap={2} align="center">
          <span className="leave-type-swatch" style={{ background: row.color }} />
          <Text>{row.name}</Text>
        </HStack>
      ),
    },
    { key: 'code',  label: 'Code',     mono: true },
    { key: 'days',  label: 'Days/Year', render: row => row.days_per_year },
    {
      key: 'is_paid', label: 'Paid',
      render: row => (
        <Badge intent={row.is_paid ? 'success' : 'neutral'}>
          {row.is_paid ? 'Yes' : 'No'}
        </Badge>
      ),
    },
    {
      key: 'requires_approval', label: 'Approval',
      render: row => (
        <Badge intent={row.requires_approval ? 'warning' : 'neutral'}>
          {row.requires_approval ? 'Required' : 'Auto'}
        </Badge>
      ),
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
          <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          <Button intent="danger" size="sm" onClick={() => destroy(row)}>Delete</Button>
        </HStack>
      ),
    },
  ];

  const totalPages  = types.last_page    ?? 1;
  const currentPage = types.current_page ?? 1;

  return (
    <>
      <style>{`
        .leave-type-swatch {
          display: inline-block;
          width: 0.75rem;
          height: 0.75rem;
          border-radius: var(--aeos-r-sm);
          flex-shrink: 0;
        }
      `}</style>

      <IndexPageLayout
        title="Leave Types"
        breadcrumb={[{ label: 'HRM' }, { label: 'Leave' }, { label: 'Types' }]}
        actions={
          <Button intent="primary" onClick={openCreate}>
            New Leave Type
          </Button>
        }
        table={
          <DataTable
            columns={columns}
            rows={types.data ?? []}
            empty="No leave types configured."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page => router.get(route('hrm.leave.types.index'), { page }, { preserveState: true })}
            />
          )
        }
      />

      <Modal
        open={modalOpen}
        onClose={closeModal}
        title={editing ? 'Edit Leave Type' : 'New Leave Type'}
        size="lg"
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
            <HStack gap={3}>
              <Field label="Name" error={errors.name} required>
                <Input
                  value={data.name}
                  onChange={e => setData('name', e.target.value)}
                  placeholder="e.g. Annual Leave"
                />
              </Field>

              <Field label="Code" error={errors.code} required>
                <Input
                  value={data.code}
                  onChange={e => setData('code', e.target.value)}
                  placeholder="e.g. AL"
                />
              </Field>
            </HStack>

            <HStack gap={3}>
              <Field label="Color" error={errors.color}>
                <Input
                  type="color"
                  value={data.color}
                  onChange={e => setData('color', e.target.value)}
                />
              </Field>

              <Field label="Days / Year" error={errors.days_per_year} required>
                <Input
                  type="number"
                  value={String(data.days_per_year)}
                  onChange={e => setData('days_per_year', e.target.value)}
                  placeholder="0"
                />
              </Field>

              <Field label="Max Carry Forward" error={errors.max_carry_forward} hint="Leave blank for unlimited">
                <Input
                  type="number"
                  value={String(data.max_carry_forward ?? '')}
                  onChange={e => setData('max_carry_forward', e.target.value)}
                  placeholder="0"
                />
              </Field>
            </HStack>

            <HStack gap={4} wrap>
              <Toggle
                label="Paid leave"
                checked={data.is_paid}
                onChange={e => setData('is_paid', e.target.checked)}
              />
              <Toggle
                label="Requires approval"
                checked={data.requires_approval}
                onChange={e => setData('requires_approval', e.target.checked)}
              />
              <Toggle
                label="Carry forward"
                checked={data.carry_forward}
                onChange={e => setData('carry_forward', e.target.checked)}
              />
              <Toggle
                label="Encashable"
                checked={data.encashable}
                onChange={e => setData('encashable', e.target.checked)}
              />
              <Toggle
                label="Active"
                checked={data.is_active}
                onChange={e => setData('is_active', e.target.checked)}
              />
            </HStack>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

LeaveTypesIndex.layout = page => <App title="Leave Types">{page}</App>;
