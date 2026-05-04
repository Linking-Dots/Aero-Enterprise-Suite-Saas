import { useForm } from '@inertiajs/react';
import { VStack, Field, Input, Button, Alert } from '@aero/ui';
import AuthLayout from '../../Auth/AuthLayout.jsx';

/**
 * AdminSetup — post-provisioning admin account creation on the tenant domain.
 *
 * Renders on the tenant subdomain after workspace provisioning.
 * Creates the first admin user for the new tenant.
 */
export default function AdminSetup({ tenant = {} }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    name:                  '',
    user_name:             '',
    email:                 '',
    phone:                 '',
    password:              '',
    password_confirmation: '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('admin.setup.store'), {
      onFinish: () => reset('password', 'password_confirmation'),
    });
  }

  return (
    <form className="al-form" onSubmit={submit} noValidate>
      {/* Server-side general error */}
      {errors.general && (
        <Alert intent="danger">{errors.general}</Alert>
      )}

      <Field label="Full Name" htmlFor="name" error={errors.name} required>
        <Input
          id="name"
          type="text"
          leftIcon="user"
          placeholder="Jane Smith"
          value={data.name}
          onChange={e => setData('name', e.target.value)}
          error={!!errors.name}
          autoComplete="name"
        />
      </Field>

      <Field
        label="Username"
        htmlFor="user_name"
        error={errors.user_name}
        hint="Letters, numbers, underscores only"
        required
      >
        <Input
          id="user_name"
          type="text"
          leftIcon="user"
          placeholder="jane_smith"
          value={data.user_name}
          onChange={e => setData('user_name', e.target.value)}
          error={!!errors.user_name}
          autoComplete="username"
        />
      </Field>

      <Field label="Email Address" htmlFor="email" error={errors.email} required>
        <Input
          id="email"
          type="email"
          leftIcon="mail"
          placeholder="jane@company.com"
          value={data.email}
          onChange={e => setData('email', e.target.value)}
          error={!!errors.email}
          autoComplete="email"
        />
      </Field>

      <Field label="Phone" htmlFor="phone" error={errors.phone}>
        <Input
          id="phone"
          type="tel"
          leftIcon="phone"
          placeholder="+1 555 000 0000"
          value={data.phone}
          onChange={e => setData('phone', e.target.value)}
          error={!!errors.phone}
          autoComplete="tel"
        />
      </Field>

      <Field
        label="Password"
        htmlFor="password"
        error={errors.password}
        hint="Min 8 chars, mixed case, numbers"
        required
      >
        <Input
          id="password"
          type="password"
          leftIcon="settings"
          placeholder="••••••••"
          value={data.password}
          onChange={e => setData('password', e.target.value)}
          error={!!errors.password}
          autoComplete="new-password"
        />
      </Field>

      <Field label="Confirm Password" htmlFor="password_confirmation" error={errors.password_confirmation} required>
        <Input
          id="password_confirmation"
          type="password"
          leftIcon="settings"
          placeholder="••••••••"
          value={data.password_confirmation}
          onChange={e => setData('password_confirmation', e.target.value)}
          error={!!errors.password_confirmation}
          autoComplete="new-password"
        />
      </Field>

      <Button
        type="submit"
        intent="primary"
        fullWidth
        size="lg"
        loading={processing}
        rightIcon="arrowRight"
      >
        Complete Setup
      </Button>
    </form>
  );
}

AdminSetup.layout = page => (
  <AuthLayout
    title={`Welcome to ${page.props.tenant?.name ?? 'aeos365'}`}
    eyebrow="Complete your account setup"
  >
    {page}
  </AuthLayout>
);
