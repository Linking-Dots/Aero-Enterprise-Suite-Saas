import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Button, Alert, Text } from '@aero/ui';

export default function VerifyEmail({ status }) {
  const { post, processing } = useForm({});

  return (
    <AuthLayout title="Verify your email">
      <div className="al-form">
        <Text tone="secondary" size="sm">
          Please verify your email address by clicking the link we sent when you registered.
          If you didn't receive the email, click below to request another.
        </Text>

        {status === 'verification-link-sent' && (
          <Alert intent="success">
            A new verification link has been sent to your email address.
          </Alert>
        )}

        <Button
          intent="primary"
          fullWidth
          loading={processing}
          onClick={() => post(route('core.verification.send'))}
        >
          Resend verification email
        </Button>

        <Text tone="secondary" size="sm" className="aeos-text-center">
          <Link href={route('logout')} method="post" as="button" className="al-link">
            Sign out
          </Link>
        </Text>
      </div>
    </AuthLayout>
  );
}
