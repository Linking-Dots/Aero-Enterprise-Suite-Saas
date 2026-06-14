import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, Field, Input, Select, Textarea, Button, HStack,
} from '@aero/ui';

export default function DepartmentsCreate({ parents, heads }) {
  const { data, setData, post, processing, errors } = useForm({
    name:          '',
    parent_id:     '',
    head_id:       '',
    description:   '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.org.departments.store'));
  }

  return (
    <FormPageLayout
      title="New Department"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Org Structure' },
        { label: 'Departments', href: route('hrm.org.departments.index') },
        { label: 'New' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={4}>
          <Field label="Name" error={errors.name} required>
            <Input
              value={data.name}
              onChange={e => setData('name', e.target.value)}
              placeholder="e.g. Engineering"
            />
          </Field>

          <Field label="Parent Department" error={errors.parent_id}>
            <Select
              value={data.parent_id}
              onChange={e => setData('parent_id', e.target.value)}
            >
              <option value="">None (top-level)</option>
              {(parents ?? []).map(p => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </Select>
          </Field>

          <Field label="Department Head" error={errors.head_id}>
            <Select
              value={data.head_id}
              onChange={e => setData('head_id', e.target.value)}
            >
              <option value="">Unassigned</option>
              {(heads ?? []).map(h => (
                <option key={h.id} value={h.id}>{h.label}</option>
              ))}
            </Select>
          </Field>

          <Field label="Description" error={errors.description}>
            <Textarea
              value={data.description}
              onChange={e => setData('description', e.target.value)}
              rows={4}
              placeholder="Optional description"
            />
          </Field>

          <HStack gap={2}>
            <Button type="submit" intent="primary" loading={processing}>
              Create Department
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => window.history.back()}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

DepartmentsCreate.layout = page => <App title="New Department">{page}</App>;
