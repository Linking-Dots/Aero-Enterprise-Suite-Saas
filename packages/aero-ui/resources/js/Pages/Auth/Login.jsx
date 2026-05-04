import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Toggle, Button, Alert, Text } from '@aero/ui';

export default function Login({ canResetPassword, status, canRegister, oauthProviders = [], deviceBlocked, deviceMessage }) {
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
    <form className="al-form" onSubmit={submit} noValidate>
      {status && <Alert intent="info">{status}</Alert>}

      {deviceBlocked && (
        <Alert intent="danger" title="Device blocked">
          {deviceMessage ?? 'This device has been blocked. Contact your administrator.'}
        </Alert>
      )}

      <Field label="Email address" htmlFor="email" error={errors.email} required>
        <Input
          id="email"
          type="email"
          value={data.email}
          onChange={e => setData('email', e.target.value)}
          leftIcon="mail"
          placeholder="you@company.com"
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
          leftIcon="settings"
          placeholder="••••••••"
          autoComplete="current-password"
          error={!!errors.password}
        />
      </Field>

      <div className="al-row">
        <Toggle
          label="Remember me"
          checked={data.remember}
          onChange={e => setData('remember', e.target.checked)}
        />
        {canResetPassword && (
          <Link href={route('password.request')} className="al-link">
            Forgot password?
          </Link>
        )}
      </div>

      <Button intent="primary" fullWidth loading={processing} disabled={processing} type="submit" size="lg">
        Sign in
      </Button>

      {oauthProviders.length > 0 && (
        <>
          <div className="al-sep">
            <span className="al-sep-line" />
            <span className="al-sep-text">or continue with</span>
            <span className="al-sep-line" />
          </div>
          <div className="al-oauth-grid">
            {oauthProviders.map(p => (
              <a key={p.name} href={route('auth.social.redirect', { provider: p.name })} className="aeos-btn aeos-btn-ghost">
                {p.label}
              </a>
            ))}
          </div>
        </>
      )}

      {canRegister && (
        <div className="al-links">
          <Text tone="secondary">
            New to aeos365?{' '}
            <Link href={route('register')} className="al-link">Create an account</Link>
          </Text>
        </div>
      )}
    </form>
  );
}

Login.layout = page => (
  <AuthLayout title="Welcome back" eyebrow="Sign in to your account">
    {page}
  </AuthLayout>
);
