import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Field, Input, Select, Textarea, Pagination, Badge,
  Modal, Text, Alert,
} from '@aero/ui';

const SEVERITY_OPTIONS = [
  { value: '', label: 'Select severity' },
  { value: 'minor',    label: 'Minor' },
  { value: 'moderate', label: 'Moderate' },
  { value: 'major',    label: 'Major' },
  { value: 'gross',    label: 'Gross Misconduct' },
];

const SEVERITY_INTENT = {
  minor:    'neutral',
  moderate: 'warning',
  major:    'danger',
  gross:    'danger',
};

const EMPTY_FORM = {
  name:                   '',
  severity:               '',
  description:            '',
  escalates_after_count:  '',
  escalates_to_type_id:   '',
};

export default function ActionTypesIndex({ types }) {
  const canManage = useHRMAC('hrm.disciplinary.action-types.manage');

  const [createOpen, setCreateOpen]   = useState(false);
  const [editTarget,  setEditTarget]  = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const createForm = useForm({ ...EMPTY_FORM });
  const editForm   = useForm({ ...EMPTY_FORM });

  function openCreate() {
    createForm.reset();
    setCreateOpen(true);
  }

  function openEdit(type) {
    editForm.setData({
      name:                  type.name ?? '',
      severity:              type.severity ?? '',
      description:           type.description ?? '',
      escalates_after_count: type.escalates_after_count ?? '',
      escalates_to_type_id:  type.escalates_to_type_id ?? '',
    });
    setEditTarget(type);
  }

  function submitCreate(e) {
    e.preventDefault();
    createForm.post(route('hrm.disciplinary.action-types.store'), {
      onSuccess: () => { setCreateOpen(false); createForm.reset(); },
    });
  }

  function submitEdit(e) {
    e.preventDefault();
    editForm.put(route('hrm.disciplinary.action-types.update', editTarget.id), {
      onSuccess: () => setEditTarget(null),
    });
  }

  function confirmDelete(type) {
    setDeleteTarget(type);
  }

  function handleDelete() {
    router.delete(route('hrm.disciplinary.action-types.destroy', deleteTarget.id), {
      onSuccess: () => setDeleteTarget(null),
    });
  }

  function applyPage(p) {
    router.get(route('hrm.disciplinary.action-types.index'), { page: p }, {
      preserveState: true, replace: true,
    });
  }

  const typeOptions = [
    { value: '', label: 'None (no escalation)' },
    ...(types.data ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  const columns = [
    { key: 'name',     label: 'Name' },
    {
      key: 'severity', label: 'Severity',
      render: row => (
        <Badge intent={SEVERITY_INTENT[row.severity] ?? 'neutral'}>
          {row.severity ?? '—'}
        </Badge>
      ),
    },
    { key: 'description', label: 'Description', render: row => row.description ?? '—' },
    {
      key: 'escalation', label: 'Escalation',
      render: row => row.escalates_after_count
        ? `After ${row.escalates_after_count} → ${row.escalatesTo?.name ?? 'N/A'}`
        : '—',
    },
    ...(canManage ? [{
      key: 'actions', label: '',
      render: row => (
        <HStack gap={2}>
          <Button size="sm" intent="soft" onClick={() => openEdit(row)}>Edit</Button>
          <Button size="sm" intent="danger" onClick={() => confirmDelete(row)}>Delete</Button>
        </HStack>
      ),
    }] : []),
  ];

  const FormFields = ({ data, setData, errors, typeOpts }) => (
    <VStack gap={4}>
      <Field label="Name" error={errors.name} required>
        <Input
          value={data.name}
          onChange={e => setData('name', e.target.value)}
          placeholder="e.g. Written Warning"
        />
      </Field>
      <Field label="Severity" error={errors.severity} required>
        <Select
          options={SEVERITY_OPTIONS}
          value={data.severity}
          onChange={e => setData('severity', e.target.value)}
        />
      </Field>
      <Field label="Description" error={errors.description}>
        <Textarea
          value={data.description}
          onChange={e => setData('description', e.target.value)}
          placeholder="Brief description of this action type"
          rows={3}
        />
      </Field>
      <HStack gap={3}>
        <Field label="Escalates After (occurrences)" error={errors.escalates_after_count}>
          <Input
            type="number"
            value={data.escalates_after_count}
            onChange={e => setData('escalates_after_count', e.target.value)}
            placeholder="e.g. 3"
          />
        </Field>
        <Field label="Escalates To Type" error={errors.escalates_to_type_id}>
          <Select
            options={typeOpts}
            value={data.escalates_to_type_id}
            onChange={e => setData('escalates_to_type_id', e.target.value)}
          />
        </Field>
      </HStack>
    </VStack>
  );

  return (
    <>
      <IndexPageLayout
        title="Disciplinary Action Types"
        breadcrumb={[{ label: 'HRM' }, { label: 'Disciplinary' }, { label: 'Action Types' }]}
        actions={
          canManage && (
            <Button intent="primary" onClick={openCreate}>New Action Type</Button>
          )
        }
        table={
          <DataTable
            columns={columns}
            rows={types.data ?? []}
            empty="No action types configured."
          />
        }
        pagination={
          (types.last_page ?? 1) > 1 && (
            <Pagination
              total={types.last_page ?? 1}
              page={types.current_page ?? 1}
              onChange={applyPage}
            />
          )
        }
      />

      {/* Create Modal */}
      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="New Action Type"
        size="md"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={createForm.processing} onClick={submitCreate}>
              Create
            </Button>
            <Button type="button" intent="ghost" onClick={() => setCreateOpen(false)}>Cancel</Button>
          </HStack>
        }
      >
        <form onSubmit={submitCreate}>
          {Object.keys(createForm.errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below." />
          )}
          <FormFields
            data={createForm.data}
            setData={createForm.setData}
            errors={createForm.errors}
            typeOpts={typeOptions}
          />
        </form>
      </Modal>

      {/* Edit Modal */}
      <Modal
        open={!!editTarget}
        onClose={() => setEditTarget(null)}
        title="Edit Action Type"
        size="md"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={editForm.processing} onClick={submitEdit}>
              Save
            </Button>
            <Button type="button" intent="ghost" onClick={() => setEditTarget(null)}>Cancel</Button>
          </HStack>
        }
      >
        <form onSubmit={submitEdit}>
          {Object.keys(editForm.errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below." />
          )}
          <FormFields
            data={editForm.data}
            setData={editForm.setData}
            errors={editForm.errors}
            typeOpts={typeOptions}
          />
        </form>
      </Modal>

      {/* Delete Confirm Modal */}
      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Action Type"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="danger" onClick={handleDelete}>Delete</Button>
            <Button type="button" intent="ghost" onClick={() => setDeleteTarget(null)}>Cancel</Button>
          </HStack>
        }
      >
        <Text>
          Are you sure you want to delete "{deleteTarget?.name}"? This cannot be undone.
        </Text>
      </Modal>
    </>
  );
}

ActionTypesIndex.layout = page => <App title="Disciplinary Action Types">{page}</App>;
