import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Input,
  Select,
  useToast,
  Card, CardContent,
  useHRMAC,
  SavedViewsDropdown,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function UsersIndex({ users, roles, filters, stats }) {
  const toast = useToast();
  const canCreate     = useHRMAC('core.user_management.users.create');
  const canEdit       = useHRMAC('core.user_management.users.edit');
  const canDelete     = useHRMAC('core.user_management.users.delete');
  const canView       = useHRMAC('core.user_management.users.view');
  const canActivate   = useHRMAC('core.user_management.users.activate');
  const canImpersonate = useHRMAC('core.user_management.users.impersonate');
  const canBulkDelete = useHRMAC('core.user_management.users.bulk_delete');

  const [selectedIds, setSelectedIds] = useState([]);
  const [search, setSearch]   = useState(filters?.search  || '');
  const [status, setStatus]   = useState(filters?.status  || '');
  const [role,   setRole]     = useState(filters?.role    || '');

  const applyFilters = () => {
    router.get(route('core.users.index'), { search, status, role }, {
      preserveState: true, preserveScroll: true, only: ['users', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch(''); setStatus(''); setRole('');
    router.get(route('core.users.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['users', 'filters'],
    });
  };

  const handleApplySavedView = config => {
    if (config.filters) {
      setSearch(config.filters.search  || '');
      setStatus(config.filters.status  || '');
      setRole  (config.filters.role    || '');
      router.get(route('core.users.index'), {
        search: config.filters.search || '',
        status: config.filters.status || '',
        role:   config.filters.role   || '',
      }, { preserveState: true, preserveScroll: true, only: ['users', 'filters'] });
    }
  };

  const getCurrentFilters = () => ({ filters: { search, status, role }, sort: null, columns: null });

  const toggleUserStatus = (id, activate) => {
    router.put(route('core.users.toggle-status', id), { active: activate }, {
      preserveState: true,
      onSuccess: () => toast.success(activate ? 'User activated.' : 'User deactivated.'),
      onError:   () => toast.error('Failed to update status'),
    });
  };

  const deleteUser = id => {
    if (!confirm('Delete this user?')) return;
    router.delete(route('core.users.destroy', id), {
      onSuccess: () => toast.success('User deleted.'),
      onError:   () => toast.error('Failed to delete user'),
    });
  };

  const handleBulkToggle = activate => {
    if (!selectedIds.length) return;
    if (!confirm(`${activate ? 'Activate' : 'Deactivate'} ${selectedIds.length} user(s)?`)) return;
    router.post(route('core.users.bulk.toggle-status'), { user_ids: selectedIds, active: activate }, {
      preserveState: true,
      onSuccess: () => { toast.success('Bulk status updated.'); setSelectedIds([]); },
      onError:   () => toast.error('Bulk action failed'),
    });
  };

  const handleBulkDelete = () => {
    if (!selectedIds.length) return;
    if (!confirm(`Delete ${selectedIds.length} user(s)?`)) return;
    router.post(route('core.users.bulk.delete'), { user_ids: selectedIds }, {
      onSuccess: () => { toast.success('Users deleted.'); setSelectedIds([]); },
      onError:   () => toast.error('Bulk delete failed'),
    });
  };

  const impersonateUser = id => {
    router.post(route('core.users.impersonate', id), {}, {
      onSuccess: () => toast.success('Impersonating user…'),
      onError:   () => toast.error('Failed to impersonate'),
    });
  };

  const columns = [
    {
      key: 'select', label: '', width: '40px',
      render: row => (
        <input
          type="checkbox"
          checked={selectedIds.includes(row.id)}
          onChange={() => setSelectedIds(prev =>
            prev.includes(row.id) ? prev.filter(i => i !== row.id) : [...prev, row.id]
          )}
        />
      ),
    },
    {
      key: 'name', label: 'Name', width: '20%',
      render: row => (
        <Text size="sm">
          {row.name}
          {row.user_name && <span className="aeos-text-tertiary"> @{row.user_name}</span>}
        </Text>
      ),
    },
    { key: 'email', label: 'Email', width: '20%', render: row => row.email },
    {
      key: 'roles', label: 'Roles', width: '18%',
      render: row => (
        <HStack gap={1} wrap>
          {row.roles?.map(r => (
            <Badge key={r.id} intent="neutral" size="sm">{r.name}</Badge>
          )) || <Text tone="secondary" size="sm">—</Text>}
        </HStack>
      ),
    },
    {
      key: 'status', label: 'Status', width: '12%',
      render: row => (
        <Badge intent={row.deleted_at ? 'danger' : row.active ? 'success' : 'warning'}>
          {row.deleted_at ? 'Deleted' : row.active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    { key: 'created_at', label: 'Joined', width: '12%', render: row => new Date(row.created_at).toLocaleDateString() },
    {
      key: 'actions', label: '', width: '28%', align: 'right',
      render: row => {
        const isActive = !row.deleted_at && row.active;
        return (
          <HStack gap={2} justify="end">
            {canView && (
              <Button intent="soft" size="sm" onClick={() => router.visit(route('core.users.show', row.id))}>
                View
              </Button>
            )}
            {canEdit && (
              <Button intent="soft" size="sm" onClick={() => router.visit(route('core.users.edit', row.id))}>
                Edit
              </Button>
            )}
            {canActivate && (
              isActive
                ? <Button intent="ghost" size="sm" onClick={() => toggleUserStatus(row.id, false)}>Deactivate</Button>
                : <Button intent="soft"  size="sm" onClick={() => toggleUserStatus(row.id, true)}>Activate</Button>
            )}
            {canImpersonate && (
              <Button intent="ghost" size="sm" onClick={() => impersonateUser(row.id)}>Impersonate</Button>
            )}
            {canDelete && (
              <Button intent="danger" size="sm" onClick={() => deleteUser(row.id)}>Delete</Button>
            )}
          </HStack>
        );
      },
    },
  ];

  return (
    <IndexPageLayout
      title="Users"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Users' },
      ]}
      description="Manage user accounts, roles, and permissions."
      actions={
        canCreate && (
          <Button intent="primary" onClick={() => router.visit(route('core.users.create'))}>
            Create User
          </Button>
        )
      }
      kpis={[
        <Stat key="total"    title="Total Users" value={stats?.total    ?? 0} icon="users"  />,
        <Stat key="active"   title="Active"      value={stats?.active   ?? 0} icon="users"  iconTone="success" />,
        <Stat key="inactive" title="Inactive"    value={stats?.inactive ?? 0} icon="users"  iconTone="amber" />,
      ]}
      filters={
        <HStack gap={3} align="end" wrap>
          <Input
            placeholder="Search name, email, username…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
          />
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={[
              { value: '', label: 'All Statuses' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]}
          />
          <Select
            value={role}
            onChange={e => setRole(e.target.value)}
            options={[
              { value: '', label: 'All Roles' },
              ...(roles ?? []).map(r => ({ value: r.name, label: r.name })),
            ]}
          />
          <Button intent="primary" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
          <SavedViewsDropdown
            moduleCode="core"
            route={route('core.users.index')}
            currentFilters={getCurrentFilters()}
            onApply={handleApplySavedView}
          />
        </HStack>
      }
      table={
        <VStack gap={3}>
          {selectedIds.length > 0 && (
            <HStack gap={2}>
              <Text size="sm">{selectedIds.length} selected</Text>
              {canActivate && (
                <Button intent="soft"  size="sm" onClick={() => handleBulkToggle(true)}>Activate</Button>
              )}
              {canActivate && (
                <Button intent="ghost" size="sm" onClick={() => handleBulkToggle(false)}>Deactivate</Button>
              )}
              {canBulkDelete && (
                <Button intent="danger" size="sm" onClick={handleBulkDelete}>Delete</Button>
              )}
            </HStack>
          )}
          <DataTable
            columns={columns}
            rows={users?.data || []}
            empty="No users found."
          />
        </VStack>
      }
      pagination={
        users?.last_page > 1 && (
          <Pagination
            page={users.current_page}
            total={users.last_page}
            onChange={page => router.get(route('core.users.index'), { page, search, status, role }, {
              preserveState: true, preserveScroll: true, only: ['users'],
            })}
          />
        )
      }
    />
  );
}

UsersIndex.layout = page => (
  <App title="Users">{page}</App>
);
