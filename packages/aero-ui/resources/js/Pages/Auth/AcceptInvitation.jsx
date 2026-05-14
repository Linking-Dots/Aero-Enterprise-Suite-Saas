import { useForm } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Button, Text, VStack, Badge, HStack } from '@aero/ui';

export default function AcceptInvitation({ invitation, token }) {
  const { data, setData, post, processing, errors } = useForm({
    token,
    name:                  invitation.name ?? '',
    password:              '',
    password_confirmation: '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('invitation.accept.store', token));
  }

  return (
    <AuthLayout title="Accept your invitation">
      <form className="al-form" onSubmit={submit} noValidate>
        <VStack gap={2} className="aai-header">
          {invitation.inviter && (
            <Text tone="secondary" size="sm">
              {invitation.inviter.name} has invited you to join.
            </Text>
          )}
          <Text size="sm">
            <strong>{invitation.email}</strong>
          </Text>
          {invitation.roles?.length > 0 && (
            <HStack gap={2} wrap>
              {invitation.roles.map(r => (
                <Badge key={r} intent="primary">{r}</Badge>
              ))}
            </HStack>
          )}
        </VStack>

        <Field label="Your name" htmlFor="name" error={errors.name} required>
          <Input
            id="name"
            type="text"
            value={data.name}
            onChange={e => setData('name', e.target.value)}
            autoComplete="name"
            autoFocus
            error={!!errors.name}
          />
        </Field>

        <Field label="Choose a password" htmlFor="password" error={errors.password} required>
          <Input
            id="password"
            type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            placeholder="At least 8 characters"
            autoComplete="new-password"
            error={!!errors.password}
          />
        </Field>

        <Field label="Confirm password" htmlFor="password_confirmation" error={errors.password_confirmation} required>
          <Input
            id="password_confirmation"
            type="password"
            value={data.password_confirmation}
            onChange={e => setData('password_confirmation', e.target.value)}
            autoComplete="new-password"
            error={!!errors.password_confirmation}
          />
        </Field>

        <Button intent="primary" fullWidth loading={processing} type="submit">
          Create account and sign in
        </Button>
      </form>

      <style>{`
        .aai-header { margin-bottom: 1rem; }
      `}</style>
    </AuthLayout>
  );
}
