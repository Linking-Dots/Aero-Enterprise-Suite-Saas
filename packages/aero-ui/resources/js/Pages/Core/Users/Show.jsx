import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Card, CardContent,
  useToast,
} from '@aero/ui';
import App from '../../App.jsx';

export default function UsersShow({ user }) {
  const toast = useToast();

  const toggleStatus = async () => {
    const activate = !!user.deleted_at || !user.active;
    try {
      const res = await fetch(route('core.users.toggleStatus', user.id), {
        method: 'PUT',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ active: activate }),
      });
      if (res.ok) {
        const data = await res.json();
        toast.success(data.message);
        router.reload();
      }
    } catch {
      toast.error('Failed to update status');
    }
  };

  const deleteUser = async () => {
    if (!confirm('Deactivate this user?')) return;
    try {
      const res = await fetch(route('core.users.destroy', user.id), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
      if (res.ok) {
        toast.success('User deactivated');
        router.visit(route('core.users.index'));
      }
    } catch {
      toast.error('Failed to delete user');
    }
  };

  const impersonate = async () => {
    try {
      const res = await fetch(route('core.users.impersonate', user.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
      if (res.ok) {
        toast.success('Impersonating user...');
        window.location.reload();
      }
    } catch {
      toast.error('Failed to impersonate');
    }
  };

  const isActive = !user.deleted_at && user.active;

  return (
    <DetailPageLayout
      title={user.name}
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Users', href: route('core.users.index') },
        { label: user.name },
      ]}
      description="User profile and account details."
      actions={
        <HStack gap={2}>
          <Button variant="secondary" onClick={() => router.visit(route('core.users.edit', user.id))}>
            Edit
          </Button>
          <Button variant={isActive ? 'warning' : 'success'} onClick={toggleStatus}>
            {isActive ? 'Deactivate' : 'Activate'}
          </Button>
          <Button variant="danger" onClick={deleteUser}>
            Delete
          </Button>
          <Button variant="ghost" onClick={impersonate}>
            Impersonate
          </Button>
        </HStack>
      }
    >
      <VStack gap={4} className="max-w-3xl">
        <Card>
          <CardContent>
            <VStack gap={3}>
              <HStack gap={2} align="center">
                <Text variant="h4">{user.name}</Text>
                <Badge variant={isActive ? 'success' : 'danger'}>
                  {isActive ? 'Active' : 'Inactive'}
                </Badge>
              </HStack>

              <VStack gap={1}>
                <Text><strong>Email:</strong> {user.email}</Text>
                {user.user_name && <Text><strong>Username:</strong> @{user.user_name}</Text>}
                {user.phone && <Text><strong>Phone:</strong> {user.phone}</Text>}
                <Text><strong>Created:</strong> {new Date(user.created_at).toLocaleString()}</Text>
                {user.email_verified_at && (
                  <Text><strong>Verified:</strong> {new Date(user.email_verified_at).toLocaleString()}</Text>
                )}
              </VStack>

              <div>
                <Text variant="caption" className="mb-2">Roles</Text>
                <HStack gap={1} wrap>
                  {user.roles?.length > 0 ? user.roles.map(r => (
                    <Badge key={r.id} variant="secondary">{r.name}</Badge>
                  )) : (
                    <span className="text-muted">No roles assigned</span>
                  )}
                </HStack>
              </div>
            </VStack>
          </CardContent>
        </Card>
      </VStack>
    </DetailPageLayout>
  );
}

UsersShow.layout = page => (
  <App title={page.props.user?.name || 'User Details'}>{page}</App>
);
