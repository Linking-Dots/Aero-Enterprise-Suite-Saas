import { useForm } from '@inertiajs/react';
import AuthLayout from '../../Auth/AuthLayout.jsx';
import { Field, Input, Toggle, Button, Alert } from '@aero/ui';

export default function SharedLogin({ status, canResetPassword }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    email:    '',
    password: '',
    remember: false,
  });

  function submit(e) {
    e.preventDefault();
    post(route('login'), { onFinish: () => reset('password') });
  }

  return (
    <AuthLayout title="Platform Admin">
      <form className="al-form" onSubmit={submit} noValidate>
        {status && <Alert intent="info">{status}</Alert>}

        <Field label="Email address" htmlFor="email" error={errors.email} required>
          <Input
            id="email"
            type="email"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            autoComplete="email"
            autoFocus
            error={!!errors.email}
          />
        </Field>

        <Field label="Password" htmlFor="password" error={errors.password} required>
          <Input
            id="password"
            type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            autoComplete="current-password"
            error={!!errors.password}
          />
        </Field>

        <Toggle
          label="Remember me"
          checked={data.remember}
          onChange={e => setData('remember', e.target.checked)}
        />

        <Button intent="primary" fullWidth loading={processing} type="submit" size="lg">
          Sign in to Platform
        </Button>
      </form>
    </AuthLayout>
  );
}
