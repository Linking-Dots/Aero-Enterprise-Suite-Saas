import { useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Field, Input, Select, Button,
  Alert, Badge, DataTable, Pagination, Modal,
} from '@aero/ui';

const TEMPLATE_TYPE_OPTIONS = [
  { value: 'onboarding',  label: 'Onboarding' },
  { value: 'offboarding', label: 'Offboarding' },
];

const ASSIGNEE_TYPE_OPTIONS = [
  { value: 'hr',       label: 'HR' },
  { value: 'manager',  label: 'Manager' },
  { value: 'it',       label: 'IT' },
  { value: 'employee', label: 'Employee' },
];

const EMPTY_TASK = { title: '', due_days: '', assignee_type: 'hr' };

function typeIntent(type) {
  return type === 'onboarding' ? 'success' : 'warning';
}

export default function TaskTemplatesIndex({ templates }) {
  const { props } = usePage();
  const flash = props.flash ?? {};
  const canManage = useHRMAC('hrm.settings.task-templates.manage');

  const [modalOpen, setModalOpen]   = useState(false);
  const [confirmId, setConfirmId]   = useState(null);
  const [confirmName, setConfirmName] = useState('');

  const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
    name:  '',
    type:  'onboarding',
    tasks: [{ ...EMPTY_TASK }],
  });

  function openCreate() {
    clearErrors();
    reset();
    setData({ name: '', type: 'onboarding', tasks: [{ ...EMPTY_TASK }] });
    setModalOpen(true);
  }

  function closeModal() {
    setModalOpen(false);
  }

  function addTaskRow() {
    setData('tasks', [...data.tasks, { ...EMPTY_TASK }]);
  }

  function removeTaskRow(index) {
    setData('tasks', data.tasks.filter((_, i) => i !== index));
  }

  function updateTask(index, field, value) {
    const updated = data.tasks.map((t, i) => i === index ? { ...t, [field]: value } : t);
    setData('tasks', updated);
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.settings.task-templates.store'), { onSuccess: closeModal });
  }

  function askDelete(row) {
    setConfirmId(row.id);
    setConfirmName(row.name);
  }

  function doDelete() {
    router.delete(route('hrm.settings.task-templates.destroy', confirmId), { preserveScroll: true });
    setConfirmId(null);
  }

  const totalPages  = templates?.last_page    ?? 1;
  const currentPage = templates?.current_page ?? 1;

  const columns = [
    {
      key: 'name',
      label: 'Template Name',
      render: row => <Text>{row.name}</Text>,
    },
    {
      key: 'type',
      label: 'Type',
      render: row => (
        <Badge intent={typeIntent(row.type)}>
          {row.type ? row.type.charAt(0).toUpperCase() + row.type.slice(1) : '—'}
        </Badge>
      ),
    },
    {
      key: 'tasks_count',
      label: 'Tasks',
      render: row => <Mono>{row.tasks_count ?? row.tasks?.length ?? 0}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: row => canManage ? (
        <HStack gap={1}>
          <Button intent="danger" size="sm" onClick={() => askDelete(row)}>
            Delete
          </Button>
        </HStack>
      ) : null,
    },
  ];

  return (
    <>
      <div className="p-6">
        <VStack gap={5}>
          <HStack gap={2} align="center">
            <Box grow>
              <div className="text-2xl font-bold mb-1">Task Templates</div>
              <Text tone="secondary">Reusable onboarding and offboarding task checklists.</Text>
            </Box>
            {canManage && (
              <Button intent="primary" onClick={openCreate}>
                New Template
              </Button>
            )}
          </HStack>

          {flash.success && <Alert intent="success" title={flash.success} />}
          {flash.error   && <Alert intent="danger"  title={flash.error}   />}

          <DataTable
            columns={columns}
            rows={templates?.data ?? []}
            empty="No task templates found. Create one to get started."
          />

          {totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page =>
                router.get(
                  route('hrm.settings.task-templates.index'),
                  { page },
                  { preserveState: true, preserveScroll: true, replace: true },
                )
              }
            />
          )}
        </VStack>
      </div>

      {/* Create Template Modal */}
      <Modal
        open={modalOpen}
        onClose={closeModal}
        title="New Task Template"
        size="lg"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={processing} onClick={submit}>
              Create Template
            </Button>
            <Button type="button" intent="ghost" onClick={closeModal}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={4}>
            <Field label="Template Name" htmlFor="tt-name" error={errors.name} required>
              <Input
                id="tt-name"
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="e.g. Standard Onboarding"
              />
            </Field>

            <Field label="Type" htmlFor="tt-type" error={errors.type} required>
              <Select
                id="tt-type"
                options={TEMPLATE_TYPE_OPTIONS}
                value={data.type}
                onChange={e => setData('type', e.target.value)}
              />
            </Field>

            <VStack gap={3}>
              <HStack gap={2} align="center">
                <Box grow>
                  <Eyebrow>Tasks</Eyebrow>
                </Box>
                <Button type="button" intent="soft" size="sm" onClick={addTaskRow}>
                  Add Task
                </Button>
              </HStack>

              {data.tasks.map((task, i) => (
                <div key={i} className="p-3 bg-default-50 border border-divider rounded-md">
                  <VStack gap={3}>
                    <HStack gap={3}>
                      <Box grow>
                        <Field
                          label="Task Title"
                          htmlFor={`task-title-${i}`}
                          error={errors[`tasks.${i}.title`]}
                          required
                        >
                          <Input
                            id={`task-title-${i}`}
                            value={task.title}
                            onChange={e => updateTask(i, 'title', e.target.value)}
                            placeholder="e.g. Set up email account"
                          />
                        </Field>
                      </Box>
                      <Field
                        label="Due (days)"
                        htmlFor={`task-due-${i}`}
                        error={errors[`tasks.${i}.due_days`]}
                        hint="Days from start"
                      >
                        <Input
                          id={`task-due-${i}`}
                          type="number"
                          value={task.due_days}
                          onChange={e => updateTask(i, 'due_days', e.target.value)}
                          placeholder="1"
                        />
                      </Field>
                    </HStack>

                    <HStack gap={2} align="center">
                      <Box grow>
                        <Field
                          label="Assignee Type"
                          htmlFor={`task-assignee-${i}`}
                          error={errors[`tasks.${i}.assignee_type`]}
                        >
                          <Select
                            id={`task-assignee-${i}`}
                            options={ASSIGNEE_TYPE_OPTIONS}
                            value={task.assignee_type}
                            onChange={e => updateTask(i, 'assignee_type', e.target.value)}
                          />
                        </Field>
                      </Box>
                      {data.tasks.length > 1 && (
                        <Button
                          type="button"
                          intent="danger"
                          size="sm"
                          onClick={() => removeTaskRow(i)}
                        >
                          Remove
                        </Button>
                      )}
                    </HStack>
                  </VStack>
                </div>
              ))}
            </VStack>
          </VStack>
        </form>
      </Modal>

      {/* Delete Confirm Modal */}
      <Modal
        open={!!confirmId}
        onClose={() => setConfirmId(null)}
        title="Delete Task Template"
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={doDelete}>Delete</Button>
            <Button intent="ghost" onClick={() => setConfirmId(null)}>Cancel</Button>
          </HStack>
        }
      >
        <Text>
          Are you sure you want to delete the template <strong>{confirmName}</strong>? This action cannot be undone.
        </Text>
      </Modal>
    </>
  );
}

TaskTemplatesIndex.layout = page => <App title="Task Templates">{page}</App>;
