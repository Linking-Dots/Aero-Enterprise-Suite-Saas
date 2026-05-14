import { useEffect } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Toggle, Button, Alert, Text, HStack } from '@aero/ui';

function getOrCreateDeviceId() {
  let id = localStorage.getItem('aeos_device_id');
  if (!id) {
    id = crypto.randomUUID();
    localStorage.setItem('aeos_device_id', id);
  }
  return id;
}

export default function Login({
  canResetPassword,
  status,
  canRegister,
  oauthProviders = [],
  deviceBlocked,
  deviceMessage,
  blockedDeviceInfo,
}) {
  const { data, setData, post, processing, errors, reset } = useForm({
    email:     '',
    password:  '',
    remember:  false,
    device_id: '',
  });

  useEffect(() => {
    setData('device_id', getOrCreateDeviceId());
  }, []);

  function submit(e) {
    e.preventDefault();
    post(route('login'), { onFinish: () => reset('password') });
  }

  return (
    <AuthLayout title="Sign in to your account">
      <form className="al-form" onSubmit={submit} noValidate>
        {status && <Alert intent="info">{status}</Alert>}

        {deviceBlocked && (
          <Alert intent="danger" title="Device blocked">
            <Text>{deviceMessage ?? 'This device has been blocked. Contact your administrator.'}</Text>
            {blockedDeviceInfo && (
              <Text size="sm" tone="secondary" style={{ marginTop: 6 }}>
                Last seen: {blockedDeviceInfo.device_name} · {blockedDeviceInfo.last_used_at}
              </Text>
            )}
          </Alert>
        )}

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

        <Field label="Password" htmlFor="password" error={errors.password} required>
          <Input
            id="password"
            type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            placeholder="••••••••"
            autoComplete="current-password"
            error={!!errors.password}
          />
        </Field>

        <HStack justify="between" align="center">
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
        </HStack>

        <input type="hidden" name="device_id" value={data.device_id} />

        {errors.device_id && (
          <Alert intent="danger">{errors.device_id}</Alert>
        )}

        <Button intent="primary" fullWidth loading={processing} type="submit" size="lg">
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
                <a
                  key={p.name}
                  href={p.url}
                  className="aeos-btn aeos-btn-ghost"
                  style={{ flex: 1, justifyContent: 'center' }}
                >
                  {p.label}
                </a>
              ))}
            </div>
          </>
        )}

        {canRegister && (
          <Text tone="secondary" size="sm" style={{ textAlign: 'center', marginTop: 8 }}>
            Don't have an account?{' '}
            <Link href={route('platform.register.index')} className="al-link">
              Sign up
            </Link>
          </Text>
        )}
      </form>
    </AuthLayout>
  );
}
