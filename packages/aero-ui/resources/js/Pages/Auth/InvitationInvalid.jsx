import { Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Alert, Button } from '@aero/ui';

export default function InvitationInvalid({ title, message }) {
  return (
    <AuthLayout title={title ?? 'Invalid Invitation'}>
      <div className="al-form">
        <Alert intent="danger" title={title}>{message}</Alert>
        <Button intent="ghost" fullWidth as={Link} href={route('login')}>
          Back to login
        </Button>
      </div>
    </AuthLayout>
  );
}
