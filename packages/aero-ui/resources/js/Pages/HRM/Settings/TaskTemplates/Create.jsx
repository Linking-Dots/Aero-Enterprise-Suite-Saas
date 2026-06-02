import { useForm, usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Field, Input, Select, Button, Alert,
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

export default function TaskTemplatesCreate() {
  const { props } = usePage();
  const flash = props.flash ?? {};

  const { data, setData, post, processing, errors } = useForm({
    name:  '',
    type:  'onboarding',
    tasks: [{ ...EMPTY_TASK }],
  });

  function addTaskRow() {
    setData('tasks', [...data.tasks, { ...EMPTY_TASK }]);
  }

  function removeTaskRow(index) {
    if (data.tasks.length === 1) return;
    setData('tasks', data.tasks.filter((_, i) => i !== index));
  }

  function updateTask(index, field, value) {
    const updated = data.tasks.map((t, i) => i === index ? { ...t, [field]: value } : t);
    setData('tasks', updated);
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.settings.task-templates.store'));
  }

  return (
    <>
      <div className="p-6 max-w-3xl">
        <VStack gap={5}>
          <HStack gap={2} align="center">
            <Box grow>
              <div className="text-2xl font-bold mb-1">New Task Template</div>
              <Text tone="secondary">Create a reusable onboarding or offboarding task checklist.</Text>
            </Box>
            <Button
              type="button"
              intent="ghost"
              leftIcon="arrowLeft"
              onClick={() => router.get(route('hrm.settings.task-templates.index'))}
            >
              Back
            </Button>
          </HStack>

          {flash.success && <Alert intent="success" title={flash.success} />}
          {flash.error   && <Alert intent="danger"  title={flash.error}   />}

          <form onSubmit={submit}>
            <VStack gap={5}>
              {/* Template Info */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <Eyebrow>Template Details</Eyebrow>

                  <Field label="Template Name" htmlFor="name" error={errors.name} required>
                    <Input
                      id="name"
                      value={data.name}
                      onChange={e => setData('name', e.target.value)}
                      placeholder="e.g. Standard Onboarding"
                    />
                  </Field>

                  <Field label="Type" htmlFor="type" error={errors.type} required>
                    <Select
                      id="type"
                      options={TEMPLATE_TYPE_OPTIONS}
                      value={data.type}
                      onChange={e => setData('type', e.target.value)}
                    />
                  </Field>
                </VStack>
              </div>

              {/* Tasks */}
              <div className="p-6 bg-content1 border border-divider rounded-xl">
                <VStack gap={4}>
                  <HStack gap={2} align="center">
                    <Box grow>
                      <Eyebrow>Tasks</Eyebrow>
                    </Box>
                    <Button type="button" intent="soft" size="sm" onClick={addTaskRow}>
                      Add Task
                    </Button>
                  </HStack>

                  {data.tasks.length === 0 && (
                    <Text tone="secondary">No tasks added yet. Click "Add Task" to begin.</Text>
                  )}

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
                            hint="Days from hire date"
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
              </div>

              <HStack gap={2}>
                <Button type="submit" intent="primary" loading={processing}>
                  Create Template
                </Button>
                <Button
                  type="button"
                  intent="ghost"
                  onClick={() => router.get(route('hrm.settings.task-templates.index'))}
                >
                  Cancel
                </Button>
              </HStack>
            </VStack>
          </form>
        </VStack>
      </div>
    </>
  );
}

TaskTemplatesCreate.layout = page => <App title="New Task Template">{page}</App>;
