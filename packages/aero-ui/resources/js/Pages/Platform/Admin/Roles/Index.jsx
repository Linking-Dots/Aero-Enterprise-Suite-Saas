import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function RolesIndex({ roles }) {
  const toast     = useToast();
  const canManage = useHRMAC('platform-users.landlord-roles.manage');

  const [cloneTarget, setCloneTarget] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  function confirmClone() {
    if (!cloneTarget) return;
    router.post(
      route('platform.admin.roles.clone', cloneTarget.id),
      { name: `${cloneTarget.name} (copy)` },
      {
        onSuccess: () => {
          toast.success(`"${cloneTarget.name}" cloned.`);
          setCloneTarget(null);
        },
        onError: () => toast.error('Clone failed.'),
      }
    );
  }

  function confirmDelete() {
    if (!deleteTarget) return;
    router.delete(
      route('platform.admin.roles.destroy', deleteTarget.id),
      {
        onSuccess: () => {
          toast.success('Role deleted.');
          setDeleteTarget(null);
        },
        onError: () => toast.error('Failed to delete role.'),
      }
    );
  }

  const columns = [
    {
      key: 'name',
      label: 'Role Name',
      render: (row) => (
        <HStack gap={2} align="center">
          <Button
            intent="ghost"
            size="sm"
            onClick={() => canManage && router.get(route('platform.admin.roles.edit', row.id))}
          >
            {row.name}
          </Button>
          {row.is_system && (
            <Badge intent="amber">system</Badge>
          )}
        </HStack>
      ),
    },
    {
      key: 'description',
      label: 'Description',
      render: (row) => (
        <Text tone="secondary">{row.description ?? '—'}</Text>
      ),
    },
    {
      key: 'permissions',
      label: 'Permissions',
      render: (row) => (
        <Mono>{(row.permissions ?? []).length}</Mono>
      ),
    },
    {
      key: 'users_count',
      label: 'Users',
      render: (row) => (
        <Text tone="secondary">{row.users_count ?? 0}</Text>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canManage && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => router.get(route('platform.admin.roles.edit', row.id))}
            >
              Edit
            </Button>
          )}
          {canManage && (
            <Button
              intent="soft"
              size="sm"
              onClick={() => setCloneTarget(row)}
            >
              Clone
            </Button>
          )}
          {canManage && !row.is_system && (
            <Button
              intent="danger"
              size="sm"
              onClick={() => setDeleteTarget(row)}
            >
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Roles"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.p1.dashboard') },
        { label: 'Roles' },
      ]}
      description="Manage landlord roles and their permission sets."
      actions={
        canManage && (
          <Button
            intent="primary"
            onClick={() => router.get(route('platform.admin.roles.create'))}
          >
            New Role
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={roles ?? []}
          empty="No roles found."
        />
      </VStack>

      <Modal
        open={!!cloneTarget}
        onClose={() => setCloneTarget(null)}
        title="Clone Role"
        description={`Clone "${cloneTarget?.name}"? A copy will be created with "(copy)" appended to the name.`}
        footer={
          <HStack gap={2}>
            <Button intent="primary" onClick={confirmClone}>
              Clone
            </Button>
            <Button intent="ghost" onClick={() => setCloneTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Role"
        description={`Permanently delete the role "${deleteTarget?.name}"? Users assigned this role will lose its permissions.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmDelete}>
              Delete
            </Button>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

RolesIndex.layout = page => <App title="Roles">{page}</App>;
