import { router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Textarea, Button,
} from '@aero/ui';

export default function GoalsCreate() {
  const { data, setData, post, processing, errors } = useForm({
    title:       '',
    specific:    '',
    measurable:  '',
    achievable:  '',
    relevant:    '',
    time_bound:  '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.performance.goals.store'));
  }

  return (
    <FormPageLayout
      title="New Goal"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Performance' },
        { label: 'Goals', href: route('hrm.performance.goals.index') },
        { label: 'New' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          <Field label="Goal Title" htmlFor="title" error={errors.title} required>
            <Input
              id="title"
              value={data.title}
              onChange={e => setData('title', e.target.value)}
              placeholder="Short, descriptive title"
              error={!!errors.title}
            />
          </Field>

          <Field label="Specific" htmlFor="specific" error={errors.specific} required hint="What exactly will be accomplished?">
            <Textarea
              id="specific"
              value={data.specific}
              onChange={e => setData('specific', e.target.value)}
              placeholder="Describe the goal specifically..."
            />
          </Field>

          <Field label="Measurable" htmlFor="measurable" error={errors.measurable} required hint="How will progress and success be measured?">
            <Textarea
              id="measurable"
              value={data.measurable}
              onChange={e => setData('measurable', e.target.value)}
              placeholder="Define measurable criteria..."
            />
          </Field>

          <Field label="Achievable" htmlFor="achievable" error={errors.achievable} hint="How can the goal be accomplished? (optional)">
            <Textarea
              id="achievable"
              value={data.achievable}
              onChange={e => setData('achievable', e.target.value)}
              placeholder="Resources, steps needed..."
            />
          </Field>

          <Field label="Relevant" htmlFor="relevant" error={errors.relevant} hint="Why does this goal matter? (optional)">
            <Textarea
              id="relevant"
              value={data.relevant}
              onChange={e => setData('relevant', e.target.value)}
              placeholder="Alignment with team or company objectives..."
            />
          </Field>

          <Field label="Target Date" htmlFor="time_bound" error={errors.time_bound} required hint="Must be a future date">
            <Input
              id="time_bound"
              type="date"
              value={data.time_bound}
              onChange={e => setData('time_bound', e.target.value)}
              error={!!errors.time_bound}
            />
          </Field>

          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={processing}>
              Create Goal
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.performance.goals.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

GoalsCreate.layout = page => <App title="New Goal">{page}</App>;
