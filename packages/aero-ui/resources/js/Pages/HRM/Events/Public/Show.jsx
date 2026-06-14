import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Text, Eyebrow,
  Field, Input, Select, Button, Card, CardBody, Alert,
} from '@aero/ui';

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

export default function PublicEventShow({ event, sessions }) {
  const sessionOptions = [
    { value: '', label: 'No preference / single session' },
    ...(sessions ?? []).map(s => ({
      value: String(s.id),
      label: `${s.title || 'Session'} — ${fmtDatetime(s.starts_at)}`,
    })),
  ];

  const { data, setData, post, processing, errors, wasSuccessful } = useForm({
    attendee_name:  '',
    attendee_email: '',
    session_id:     '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.events.registrations.store', event.slug ?? ''));
  }

  return (
    <FormPageLayout
      title={event.title}
      breadcrumb={[{ label: 'Events' }, { label: event.title }]}
      actions={null}
    >
      <VStack gap={6}>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Event Information</Eyebrow>

              <HStack gap={6} wrap>
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Starts At</Text>
                  <Text>{fmtDatetime(event.starts_at)}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Ends At</Text>
                  <Text>{fmtDatetime(event.ends_at)}</Text>
                </VStack>
                {event.location && (
                  <VStack gap={1}>
                    <Text size="xs" tone="tertiary">Location</Text>
                    <Text>{event.location}</Text>
                  </VStack>
                )}
              </HStack>

              {event.description && (
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">About This Event</Text>
                  <Text tone="secondary">{event.description}</Text>
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {wasSuccessful ? (
          <Alert intent="success" title="Registration Successful">
            You are registered for this event. Please check your email for your
            attendance token.
          </Alert>
        ) : (
          <Card>
            <CardBody>
              <form onSubmit={submit}>
                <VStack gap={4}>
                  <Eyebrow>Register for this Event</Eyebrow>

                  <Field label="Full Name" error={errors.attendee_name} required>
                    <Input
                      value={data.attendee_name}
                      onChange={e => setData('attendee_name', e.target.value)}
                      placeholder="Your full name"
                    />
                  </Field>

                  <Field label="Email Address" error={errors.attendee_email} required>
                    <Input
                      type="email"
                      value={data.attendee_email}
                      onChange={e => setData('attendee_email', e.target.value)}
                      placeholder="your@email.com"
                    />
                  </Field>

                  {sessionOptions.length > 1 && (
                    <Field
                      label="Session"
                      error={errors.session_id}
                      hint="Optional — select a preferred session"
                    >
                      <Select
                        options={sessionOptions}
                        value={data.session_id}
                        onChange={e => setData('session_id', e.target.value)}
                      />
                    </Field>
                  )}

                  <HStack>
                    <Button
                      type="submit"
                      intent="primary"
                      loading={processing}
                    >
                      Register Now
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}

      </VStack>
    </FormPageLayout>
  );
}

PublicEventShow.layout = page => <App title="Event Registration">{page}</App>;
