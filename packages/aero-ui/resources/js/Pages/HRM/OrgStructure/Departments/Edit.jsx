import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, Field, Input, Select, Textarea, Button, HStack,
} from '@aero/ui';

export default function DepartmentsEdit({ department, parents, heads }) {
  const { data, setData, put, processing, errors } = useForm({
    name:        department.name         ?? '',
    parent_id:   department.parent_id    ?? '',
    head_id:     department.head_id      ?? '',
    description: department.description  ?? '',
  });

  // Exclude the department itself from the parent options (can't be its own parent)
  const eligibleParents = (parents ?? []).filter(p => p.id !== department.id);

  function submit(e) {
    e.preventDefault();
    put(route('hrm.org.departments.update', department.id));
  }

  return (
    <FormPageLayout
      title="Edit Department"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Org Structure' },
        { label: 'Departments', href: route('hrm.org.departments.index') },
        { label: department.name },
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
              {eligibleParents.map(p => (
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
              Save Changes
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

DepartmentsEdit.layout = page => <App title="Edit Department">{page}</App>;
