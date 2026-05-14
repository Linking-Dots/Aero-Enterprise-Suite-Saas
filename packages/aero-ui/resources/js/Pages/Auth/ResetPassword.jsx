import { useForm } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Button } from '@aero/ui';

export default function ResetPassword({ token, email }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    token,
    email:                 email ?? '',
    password:              '',
    password_confirmation: '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('password.store'), {
      onFinish: () => reset('password', 'password_confirmation'),
    });
  }

  return (
    <AuthLayout title="Set new password">
      <form className="al-form" onSubmit={submit} noValidate>
        <Field label="Email address" htmlFor="email" error={errors.email} required>
          <Input
            id="email"
            type="email"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            autoComplete="email"
            error={!!errors.email}
          />
        </Field>

        <Field label="New password" htmlFor="password" error={errors.password} required>
          <Input
            id="password"
            type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            placeholder="At least 8 characters"
            autoComplete="new-password"
            autoFocus
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
          Reset password
        </Button>
      </form>
    </AuthLayout>
  );
}
