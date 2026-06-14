import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  VStack,
  Text,
  Mono,
  Input,
  Select,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  true: 'success',
  false: 'neutral',
};

export default function UsersIndex({ users, filters }) {
  const toast     = useToast();
  const canCreate = useHRMAC('platform-users.landlord-user-list.create');
  const canEdit   = useHRMAC('platform-users.landlord-user-list.edit');
  const canDelete = useHRMAC('platform-users.landlord-user-list.delete');

  const [search, setSearch]           = useState(filters?.q ?? '');
  const [statusFilter, setStatusFilter] = useState(filters?.active ?? '');
  const [toggleTarget, setToggleTarget] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  function applyFilters() {
    router.get(
      route('platform.admin.users.index'),
      { q: search, active: statusFilter },
      { preserveState: true, preserveScroll: true, only: ['users', 'filters'] }
    );
  }

  function resetFilters() {
    setSearch('');
    setStatusFilter('');
    router.get(route('platform.admin.users.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['users', 'filters'],
    });
  }

  function confirmToggle() {
    if (!toggleTarget) return;
    router.patch(
      route('platform.admin.users.toggle-status', toggleTarget.id),
      {},
      {
        onSuccess: () => {
          toast.success('User status updated.');
          setToggleTarget(null);
        },
        onError: () => toast.error('Failed to update status.'),
      }
    );
  }

  function confirmDelete() {
    if (!deleteTarget) return;
    router.delete(
      route('platform.admin.users.destroy', deleteTarget.id),
      {
        onSuccess: () => {
          toast.success('User deleted.');
          setDeleteTarget(null);
        },
        onError: () => toast.error('Failed to delete user.'),
      }
    );
  }

  const statusOptions = [
    { value: '',    label: 'All Statuses' },
    { value: '1',   label: 'Active' },
    { value: '0',   label: 'Inactive' },
  ];

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.users.edit', row.id))}
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'email',
      label: 'Email',
      render: (row) => <Mono>{row.email}</Mono>,
    },
    {
      key: 'roles',
      label: 'Roles',
      render: (row) => (
        <HStack gap={1} wrap>
          {(row.landlord_roles ?? []).length > 0
            ? (row.landlord_roles ?? []).map((r) => (
                <Badge key={r.id} intent="neutral">{r.name}</Badge>
              ))
            : <Text tone="secondary">—</Text>
          }
        </HStack>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.active ? 'success' : 'neutral'}>
          {row.active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'last_login_at',
      label: 'Last Login',
      render: (row) => (
        <Text tone="secondary">
          {row.last_login_at
            ? new Date(row.last_login_at).toLocaleDateString()
            : '—'}
        </Text>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canEdit && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => router.get(route('platform.admin.users.edit', row.id))}
            >
              Edit
            </Button>
          )}
          {canEdit && (
            <Button
              intent="soft"
              size="sm"
              onClick={() => setToggleTarget(row)}
            >
              {row.active ? 'Deactivate' : 'Activate'}
            </Button>
          )}
          {canDelete && (
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
      title="Platform Users"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Users' },
      ]}
      description="Manage landlord users and their role assignments."
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('platform.admin.users.create'))}
          >
            Invite User
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <HStack gap={3}>
          <Input
            placeholder="Search by name or email..."
            leftIcon="search"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
          />
          <Select
            value={statusFilter}
            onChange={e => setStatusFilter(e.target.value)}
            options={statusOptions}
          />
          <Button intent="soft" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>

        <DataTable
          columns={columns}
          rows={users?.data ?? []}
          empty="No users found."
        />

        {users?.last_page > 1 && (
          <Pagination
            page={users.current_page}
            total={users.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.users.index'),
                { page, q: search, active: statusFilter },
                { preserveState: true, preserveScroll: true, only: ['users'] }
              )
            }
          />
        )}
      </VStack>

      <Modal
        open={!!toggleTarget}
        onClose={() => setToggleTarget(null)}
        title={toggleTarget?.active ? 'Deactivate User' : 'Activate User'}
        description={
          toggleTarget?.active
            ? `Deactivate "${toggleTarget?.name}"? They will no longer be able to log in.`
            : `Activate "${toggleTarget?.name}"? They will regain access to the platform.`
        }
        footer={
          <HStack gap={2}>
            <Button
              intent={toggleTarget?.active ? 'danger' : 'primary'}
              onClick={confirmToggle}
            >
              {toggleTarget?.active ? 'Deactivate' : 'Activate'}
            </Button>
            <Button intent="ghost" onClick={() => setToggleTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete User"
        description={`Permanently delete "${deleteTarget?.name}" (${deleteTarget?.email})? This cannot be undone.`}
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

UsersIndex.layout = page => <App title="Platform Users">{page}</App>;
