import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, VStack, HStack, Field, Input,
  Button, Text, Eyebrow, Toggle, DataTable,
} from '@aero/ui';

export default function EventsCreate() {
  const canCreate = useHRMAC('hrm.events.events-list.edit');

  const { data, setData, post, processing, errors } = useForm({
    title:       '',
    description: '',
    location:    '',
    starts_at:   '',
    ends_at:     '',
    capacity:    '',
    is_public:   false,
    sessions:    [],
  });

  function addSession() {
    setData('sessions', [
      ...data.sessions,
      { title: '', starts_at: '', ends_at: '', location: '' },
    ]);
  }

  function updateSession(index, field, value) {
    const updated = data.sessions.map((s, i) =>
      i === index ? { ...s, [field]: value } : s,
    );
    setData('sessions', updated);
  }

  function removeSession(index) {
    setData('sessions', data.sessions.filter((_, i) => i !== index));
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.events.store'));
  }

  const sessionColumns = [
    {
      key: 'title', label: 'Session Title',
      render: (row, index) => (
        <Field error={errors[`sessions.${index}.title`]}>
          <Input
            value={row.title}
            onChange={e => updateSession(index, 'title', e.target.value)}
            placeholder="Session title"
          />
        </Field>
      ),
    },
    {
      key: 'starts_at', label: 'Starts At',
      render: (row, index) => (
        <Field error={errors[`sessions.${index}.starts_at`]}>
          <Input
            type="datetime-local"
            value={row.starts_at}
            onChange={e => updateSession(index, 'starts_at', e.target.value)}
          />
        </Field>
      ),
    },
    {
      key: 'ends_at', label: 'Ends At',
      render: (row, index) => (
        <Field error={errors[`sessions.${index}.ends_at`]}>
          <Input
            type="datetime-local"
            value={row.ends_at}
            onChange={e => updateSession(index, 'ends_at', e.target.value)}
          />
        </Field>
      ),
    },
    {
      key: 'location', label: 'Location',
      render: (row, index) => (
        <Field error={errors[`sessions.${index}.location`]}>
          <Input
            value={row.location}
            onChange={e => updateSession(index, 'location', e.target.value)}
            placeholder="Optional location"
          />
        </Field>
      ),
    },
    {
      key: 'actions', label: '',
      render: (row, index) => (
        <Button
          type="button"
          intent="danger"
          size="sm"
          onClick={() => removeSession(index)}
        >
          Remove
        </Button>
      ),
    },
  ];

  return (
    <FormPageLayout
      title="New Event"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Events', href: route('hrm.events.index') },
        { label: 'New Event' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.events.index'))}
          >
            Cancel
          </Button>
          <Button
            type="submit"
            intent="primary"
            loading={processing}
            disabled={!canCreate}
            onClick={submit}
          >
            Create Event
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          <VStack gap={4}>
            <Eyebrow>Event Details</Eyebrow>

            <Field label="Title" error={errors.title} required>
              <Input
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="e.g. Annual Company Picnic"
              />
            </Field>

            <Field label="Description" error={errors.description}>
              <Input
                value={data.description}
                onChange={e => setData('description', e.target.value)}
                placeholder="Event description"
              />
            </Field>

            <Field label="Location" error={errors.location}>
              <Input
                value={data.location}
                onChange={e => setData('location', e.target.value)}
                placeholder="Venue or meeting link"
              />
            </Field>

            <HStack gap={3}>
              <Field label="Starts At" error={errors.starts_at} required>
                <Input
                  type="datetime-local"
                  value={data.starts_at}
                  onChange={e => setData('starts_at', e.target.value)}
                />
              </Field>

              <Field label="Ends At" error={errors.ends_at} required>
                <Input
                  type="datetime-local"
                  value={data.ends_at}
                  onChange={e => setData('ends_at', e.target.value)}
                />
              </Field>
            </HStack>

            <Field
              label="Capacity"
              error={errors.capacity}
              hint="Leave blank for unlimited"
            >
              <Input
                type="number"
                value={data.capacity}
                onChange={e => setData('capacity', e.target.value)}
                placeholder="e.g. 100"
              />
            </Field>

            <Toggle
              label="Public Event (visible without login)"
              checked={data.is_public}
              onChange={e => setData('is_public', e.target.checked)}
            />
          </VStack>

          <VStack gap={4}>
            <HStack gap={3} align="center">
              <Eyebrow>Sessions</Eyebrow>
              {errors.sessions && (
                <Text tone="secondary">{errors.sessions}</Text>
              )}
            </HStack>

            <Text tone="secondary">
              Optionally break the event into multiple sessions (e.g. morning and afternoon tracks).
            </Text>

            {data.sessions.length === 0 ? (
              <Text tone="secondary">No sessions added. The event will run as a single block.</Text>
            ) : (
              <DataTable
                columns={sessionColumns}
                rows={data.sessions.map((s, i) => ({ ...s, _index: i }))}
                empty=""
              />
            )}

            <Button type="button" intent="soft" size="sm" onClick={addSession}>
              Add Session
            </Button>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

EventsCreate.layout = page => <App title="New Event">{page}</App>;
