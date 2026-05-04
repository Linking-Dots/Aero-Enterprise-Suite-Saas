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
} from '@aero/ui';
import App from '../../App.jsx';

export default function UsersIndex({ users, roles, filters, stats }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.user_management.users.create');
  const canEdit = useHRMAC('core.user_management.users.edit');
  const canDelete = useHRMAC('core.user_management.users.delete');
  const canView = useHRMAC('core.user_management.users.view');
  const [selectedIds, setSelectedIds] = useState([]);
  const [search, setSearch] = useState(filters?.search || '');
  const [status, setStatus] = useState(filters?.status || '');
  const [role, setRole] = useState(filters?.role || '');

  const bulkForm = useForm({
    user_ids: [],
    active: true,
    roles: [],
  });

  const applyFilters = () => {
    router.get(route('core.users.index'), {
      search,
      status,
      role,
    }, {
      preserveState: true,
      preserveScroll: true,
      only: ['users', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch('');
    setStatus('');
    setRole('');
    router.get(route('core.users.index'), {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['users', 'filters'],
    });
  };

  const handleApplySavedView = (config) => {
    if (config.filters) {
      setSearch(config.filters.search || '');
      setStatus(config.filters.status || '');
      setRole(config.filters.role || '');
      router.get(route('core.users.index'), {
        search: config.filters.search || '',
        status: config.filters.status || '',
        role: config.filters.role || '',
      }, {
        preserveState: true,
        preserveScroll: true,
        only: ['users', 'filters'],
      });
    }
  };

  const getCurrentFilters = () => ({
    filters: { search, status, role },
    sort: null,
    columns: null,
  });

  const toggleUserStatus = async (id, activate) => {
    try {
      const res = await fetch(route('core.users.toggleStatus', id), {
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
        router.reload({ only: ['users', 'stats'] });
      } else {
        const err = await res.json();
        toast.error(err.error || 'Failed to update status');
      }
    } catch {
      toast.error('Network error');
    }
  };

  const deleteUser = async (id, force = false) => {
    if (!confirm(force ? 'Permanently delete this user?' : 'Deactivate this user?')) return;
    try {
      const url = force
        ? route('core.users.forceDelete', id)
        : route('core.users.destroy', id);
      const res = await fetch(url, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
      if (res.ok) {
        const data = await res.json();
        toast.success(data.message || 'User deleted');
        router.reload({ only: ['users', 'stats'] });
      } else {
        const err = await res.json();
        toast.error(err.error || 'Failed to delete user');
      }
    } catch {
      toast.error('Network error');
    }
  };

  const handleBulkToggle = async (activate) => {
    if (selectedIds.length === 0) return;
    if (!confirm(`${activate ? 'Activate' : 'Deactivate'} ${selectedIds.length} users?`)) return;
    try {
      const res = await fetch(route('core.users.bulk.toggleStatus'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ user_ids: selectedIds, active: activate }),
      });
      if (res.ok) {
        const data = await res.json();
        toast.success(data.message);
        setSelectedIds([]);
        router.reload({ only: ['users', 'stats'] });
      }
    } catch {
      toast.error('Bulk action failed');
    }
  };

  const handleBulkDelete = async () => {
    if (selectedIds.length === 0) return;
    if (!confirm(`Delete ${selectedIds.length} users?`)) return;
    try {
      const res = await fetch(route('core.users.bulk.delete'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ user_ids: selectedIds }),
      });
      if (res.ok) {
        const data = await res.json();
        toast.success(data.message);
        setSelectedIds([]);
        router.reload({ only: ['users', 'stats'] });
      }
    } catch {
      toast.error('Bulk delete failed');
    }
  };

  const columns = [
    {
      key: 'select',
      label: '',
      width: '40px',
      render: (row) => (
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
      key: 'name',
      label: 'Name',
      width: '20%',
      render: (row) => (
        <Text weight="semibold">
          {row.name}
          {row.user_name && <span className="text-muted ml-2">@{row.user_name}</span>}
        </Text>
      ),
    },
    {
      key: 'email',
      label: 'Email',
      width: '20%',
      render: (row) => row.email,
    },
    {
      key: 'roles',
      label: 'Roles',
      width: '18%',
      render: (row) => (
        <HStack gap={1} wrap>
          {row.roles?.map(r => (
            <Badge key={r.id} variant="secondary">{r.name}</Badge>
          )) || <span className="text-muted">—</span>}
        </HStack>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      width: '12%',
      render: (row) => (
        <Badge variant={row.deleted_at ? 'danger' : row.active ? 'success' : 'warning'}>
          {row.deleted_at ? 'Inactive' : row.active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      width: '30%',
      align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canView && (
            <Button variant="secondary" size="sm" onClick={() => router.visit(route('core.users.show', row.id))}>
              View
            </Button>
          )}
          {canEdit && (
            <Button variant="secondary" size="sm" onClick={() => router.visit(route('core.users.edit', row.id))}>
              Edit
            </Button>
          )}
          {canEdit && (row.deleted_at || !row.active ? (
            <Button variant="success" size="sm" onClick={() => toggleUserStatus(row.id, true)}>
              Activate
            </Button>
          ) : (
            <Button variant="warning" size="sm" onClick={() => toggleUserStatus(row.id, false)}>
              Deactivate
            </Button>
          ))}
          {canDelete && (
            <Button variant="danger" size="sm" onClick={() => deleteUser(row.id)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
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
          <Button variant="primary" onClick={() => router.visit(route('core.users.create'))}>
            Create User
          </Button>
        )
      }
    >
      <VStack gap={4}>
        {/* Stats */}
        <HStack gap={3} className="w-full">
          <Card className="flex-1">
            <CardContent>
              <Text variant="caption">Total Users</Text>
              <Text variant="h3">{stats?.total || 0}</Text>
            </CardContent>
          </Card>
          <Card className="flex-1">
            <CardContent>
              <Text variant="caption">Active</Text>
              <Text variant="h3" className="text-success">{stats?.active || 0}</Text>
            </CardContent>
          </Card>
          <Card className="flex-1">
            <CardContent>
              <Text variant="caption">Inactive</Text>
              <Text variant="h3" className="text-danger">{stats?.inactive || 0}</Text>
            </CardContent>
          </Card>
        </HStack>

        {/* Filters */}
        <HStack gap={3} align="end" className="w-full">
          <Input
            placeholder="Search name, email, username..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
            className="flex-1"
          />
          <Select
            placeholder="All Statuses"
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={[
              { value: '', label: 'All Statuses' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]}
          />
          <Select
            placeholder="All Roles"
            value={role}
            onChange={e => setRole(e.target.value)}
            options={[
              { value: '', label: 'All Roles' },
              ...roles.map(r => ({ value: r.name, label: r.name })),
            ]}
          />
          <Button variant="secondary" onClick={applyFilters}>Filter</Button>
          <Button variant="ghost" onClick={resetFilters}>Reset</Button>
          <SavedViewsDropdown
            moduleCode="core"
            route={route('core.users.index')}
            currentFilters={getCurrentFilters()}
            onApply={handleApplySavedView}
          />
        </HStack>

        {/* Bulk actions */}
        {selectedIds.length > 0 && (
          <HStack gap={2} className="w-full">
            <Text size="sm">{selectedIds.length} selected</Text>
            <Button variant="success" size="sm" onClick={() => handleBulkToggle(true)}>Activate</Button>
            <Button variant="warning" size="sm" onClick={() => handleBulkToggle(false)}>Deactivate</Button>
            <Button variant="danger" size="sm" onClick={handleBulkDelete}>Delete</Button>
          </HStack>
        )}

        {/* Table */}
        <DataTable
          columns={columns}
          rows={users?.data || []}
          empty="No users found."
        />

        {/* Pagination */}
        {users?.last_page > 1 && (
          <Pagination
            page={users?.current_page}
            total={users?.last_page}
            onChange={page => router.get(route('core.users.index'), { page, search, status, role }, {
              preserveState: true,
              preserveScroll: true,
              only: ['users'],
            })}
          />
        )}
      </VStack>
    </IndexPageLayout>
  );
}

UsersIndex.layout = page => (
  <App title="Users">{page}</App>
);
