import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Button, Alert, Text } from '@aero/ui';

export default function ForgotPassword({ status }) {
  const { data, setData, post, processing, errors } = useForm({ email: '' });

  function submit(e) {
    e.preventDefault();
    post(route('password.email'));
  }

  return (
    <AuthLayout title="Reset your password">
      <form className="al-form" onSubmit={submit} noValidate>
        <Text tone="secondary" size="sm">
          Enter your email address and we'll send you a link to reset your password.
        </Text>

        {status && <Alert intent="success">{status}</Alert>}

        <Field label="Email address" htmlFor="email" error={errors.email} required>
          <Input
            id="email"
            type="email"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            placeholder="you@company.com"
            autoComplete="email"
            autoFocus
            error={!!errors.email}
          />
        </Field>

        <Button intent="primary" fullWidth loading={processing} type="submit">
          Send reset link
        </Button>

        <Text tone="secondary" size="sm" className="aeos-text-center">
          <Link href={route('login')} className="al-link">Back to login</Link>
        </Text>
      </form>
    </AuthLayout>
  );
}
