import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Card, CardContent,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function UsersShow({ user }) {
  const toast          = useToast();
  const canActivate    = useHRMAC('core.user_management.users.activate');
  const canDeactivate  = useHRMAC('core.user_management.users.deactivate');
  const canDelete      = useHRMAC('core.user_management.users.delete');
  const canEdit        = useHRMAC('core.user_management.users.edit');
  const canImpersonate = useHRMAC('core.user_management.users.impersonate');

  const isActive = !user.deleted_at; // active = not trashed (SoftDeletes)

  const toggleStatus = () => {
    const activate = !isActive;
    router.put(route('core.users.toggle-status', user.id), { active: activate }, {
      onSuccess: () => {
        toast.success(activate ? 'User activated.' : 'User deactivated.');
        router.reload();
      },
      onError: () => toast.error('Failed to update status'),
    });
  };

  const deleteUser = () => {
    if (!confirm('Permanently delete this user? This cannot be undone.')) return;
    router.delete(route('core.users.destroy', user.id), {
      onSuccess: () => {
        toast.success('User deleted.');
        router.visit(route('core.users.index'));
      },
      onError: () => toast.error('Failed to delete user'),
    });
  };

  const impersonate = () => {
    router.post(route('core.users.impersonate', user.id), {}, {
      onSuccess: () => toast.success('Impersonating user…'),
      onError:   () => toast.error('Failed to impersonate'),
    });
  };

  return (
    <DetailPageLayout
      title={user.name}
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Users',     href: route('core.users.index') },
        { label: user.name },
      ]}
      description="User profile and account details."
      actions={
        <HStack gap={2}>
          {canEdit && (
            <Button intent="soft" onClick={() => router.visit(route('core.users.edit', user.id))}>
              Edit
            </Button>
          )}
          {(isActive ? canDeactivate : canActivate) && (
            <Button intent={isActive ? 'ghost' : 'soft'} onClick={toggleStatus}>
              {isActive ? 'Deactivate' : 'Activate'}
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" onClick={deleteUser}>Delete</Button>
          )}
          {canImpersonate && (
            <Button intent="ghost" onClick={impersonate}>Impersonate</Button>
          )}
        </HStack>
      }
    >
      <VStack gap={4} className="max-w-3xl">
        <Card>
          <CardContent>
            <VStack gap={3}>
              <HStack gap={2} align="center">
                <Text size="lg">{user.name}</Text>
                <Badge intent={isActive ? 'success' : 'danger'}>
                  {isActive ? 'Active' : 'Inactive'}
                </Badge>
              </HStack>

              <VStack gap={1}>
                <Text><strong>Email:</strong> {user.email}</Text>
                {user.user_name && <Text><strong>Username:</strong> @{user.user_name}</Text>}
                {user.phone     && <Text><strong>Phone:</strong> {user.phone}</Text>}
                <Text><strong>Created:</strong> {new Date(user.created_at).toLocaleString()}</Text>
                {user.email_verified_at && (
                  <Text><strong>Verified:</strong> {new Date(user.email_verified_at).toLocaleString()}</Text>
                )}
              </VStack>

              <div>
                <Text tone="secondary" size="sm">Roles</Text>
                <HStack gap={1} wrap>
                  {user.roles?.length > 0
                    ? user.roles.map(r => <Badge key={r.id} intent="neutral">{r.name}</Badge>)
                    : <Text tone="secondary" size="sm">No roles assigned</Text>}
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
