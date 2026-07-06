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

export default function UsersShow({ user, routePrefix, hrmacNamespace, scope, capabilities, dashboardRoute }) {
  routePrefix    = routePrefix    ?? 'core.users';
  hrmacNamespace = hrmacNamespace ?? 'core.user_management';
  scope          = scope          ?? 'tenant';
  capabilities   = capabilities   ?? { impersonation: false, invitations: scope !== 'platform' };
  dashboardRoute = dashboardRoute ?? (scope === 'platform' ? 'platform.admin.dashboard' : 'core.dashboard');

  const toast              = useToast();
  const canActivate        = useHRMAC(`${hrmacNamespace}.users.activate`);
  const canDeactivate      = useHRMAC(`${hrmacNamespace}.users.deactivate`);
  const canDelete          = useHRMAC(`${hrmacNamespace}.users.delete`);
  const canEdit            = useHRMAC(`${hrmacNamespace}.users.edit`);
  const canImpersonateGate = useHRMAC(`${hrmacNamespace}.users.impersonate`);
  const canImpersonate     = capabilities.impersonation && canImpersonateGate;

  const isActive = !user.deleted_at; // active = not trashed (SoftDeletes)

  const toggleStatus = () => {
    const activate = !isActive;
    router.put(route(`${routePrefix}.toggle-status`, user.id), { active: activate }, {
      onSuccess: () => {
        toast.success(activate ? 'User activated.' : 'User deactivated.');
        router.reload();
      },
      onError: () => toast.error('Failed to update status'),
    });
  };

  const deleteUser = () => {
    if (!confirm('Permanently delete this user? This cannot be undone.')) return;
    router.delete(route(`${routePrefix}.destroy`, user.id), {
      onSuccess: () => {
        toast.success('User deleted.');
        router.visit(route(`${routePrefix}.index`));
      },
      onError: () => toast.error('Failed to delete user'),
    });
  };

  const impersonate = () => {
    router.post(route(`${routePrefix}.impersonate`, user.id), {}, {
      onSuccess: () => toast.success('Impersonating user…'),
      onError:   () => toast.error('Failed to impersonate'),
    });
  };

  return (
    <DetailPageLayout
      title={user.name}
      breadcrumb={[
        { label: 'Dashboard', href: route(dashboardRoute) },
        { label: 'Users',     href: route(`${routePrefix}.index`) },
        { label: user.name },
      ]}
      description="User profile and account details."
      actions={
        <HStack gap={2}>
          {canEdit && (
            <Button intent="soft" onClick={() => router.visit(route(`${routePrefix}.edit`, user.id))}>
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
