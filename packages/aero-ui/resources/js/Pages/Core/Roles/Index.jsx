import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Input,
  Select,
  Modal,
  useToast,
  Card, CardContent,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function RolesIndex({ roles, users, can_manage_super_admin, error }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.roles.create');
  const canEdit = useHRMAC('core.roles.edit');
  const canDelete = useHRMAC('core.roles.delete');
  const canAssign = useHRMAC('core.roles.assign_permissions');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const [showCreate, setShowCreate] = useState(false);
  const [editingRole, setEditingRole] = useState(null);
  const [assignRoleId, setAssignRoleId] = useState(null);
  const [formData, setFormData] = useState({ name: '', description: '', default_dashboard: '', priority: 0 });
  const [selectedUserId, setSelectedUserId] = useState('');

  if (error) {
    return (
      <IndexPageLayout title="Role Management" breadcrumb={[{ label: 'Dashboard', href: route('core.dashboard') }, { label: 'Roles' }]}>
        <Card variant="danger">
          <CardContent>
            <Text variant="h4" className="text-danger">Error loading roles</Text>
            <Text>{error.message}</Text>
          </CardContent>
        </Card>
      </IndexPageLayout>
    );
  }

  const resetForm = () => {
    setFormData({ name: '', description: '', default_dashboard: '', priority: 0 });
    setEditingRole(null);
    setShowCreate(false);
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    try {
      const res = await fetch(route('core.roles.store'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(formData),
      });
      if (res.ok) {
        toast.success('Role created successfully.');
        resetForm();
        router.reload({ only: ['roles'] });
      } else {
        const err = await res.json();
        toast.error(err.message || Object.values(err.errors || {})[0]?.[0] || 'Failed to create role');
      }
    } catch {
      toast.error('Network error');
    }
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    try {
      const res = await fetch(route('core.roles.update', editingRole.id), {
        method: 'PUT',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(formData),
      });
      if (res.ok) {
        toast.success('Role updated successfully.');
        resetForm();
        router.reload({ only: ['roles'] });
      } else {
        const err = await res.json();
        toast.error(err.message || Object.values(err.errors || {})[0]?.[0] || 'Failed to update role');
      }
    } catch {
      toast.error('Network error');
    }
  };

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this role?')) return;
    try {
      const res = await fetch(route('core.roles.delete', id), {
        method: 'DELETE',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
      });
      if (res.ok) {
        toast.success('Role deleted.');
        router.reload({ only: ['roles'] });
      } else {
        const err = await res.json();
        toast.error(err.message || 'Failed to delete role');
      }
    } catch {
      toast.error('Network error');
    }
  };

  const handleToggle = async (id, current) => {
    try {
      const res = await fetch(route('core.roles.toggle-status', id), {
        method: 'PATCH',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ is_active: !current }),
      });
      if (res.ok) {
        toast.success('Role status updated.');
        router.reload({ only: ['roles'] });
      }
    } catch {
      toast.error('Failed to toggle status');
    }
  };

  const handleAssign = async (e) => {
    e.preventDefault();
    if (!selectedUserId || !assignRoleId) return;
    try {
      const res = await fetch(route('core.roles.assign-user'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ user_id: selectedUserId, roles: [assignRoleId] }),
      });
      if (res.ok) {
        toast.success('Role assigned successfully.');
        setAssignRoleId(null);
        setSelectedUserId('');
        router.reload({ only: ['roles', 'users'] });
      } else {
        const err = await res.json();
        toast.error(err.message || 'Failed to assign role');
      }
    } catch {
      toast.error('Network error');
    }
  };

  const openEdit = (role) => {
    setEditingRole(role);
    setFormData({
      name: role.name,
      description: role.description || '',
      default_dashboard: role.default_dashboard || '',
      priority: role.priority || 0,
    });
    setShowCreate(true);
  };

  const openCreate = () => {
    resetForm();
    setShowCreate(true);
  };

  const columns = [
    {
      key: 'name',
      label: 'Name',
      width: '20%',
      render: (row) => (
        <HStack gap={2} align="center">
          <Text weight="semibold">{row.name}</Text>
          {row.is_protected && <Badge variant="warning">Protected</Badge>}
        </HStack>
      ),
    },
    {
      key: 'description',
      label: 'Description',
      width: '25%',
      render: (row) => row.description || '—',
    },
    {
      key: 'users_count',
      label: 'Users',
      width: '10%',
      render: (row) => row.users_count || 0,
    },
    {
      key: 'priority',
      label: 'Priority',
      width: '10%',
      render: (row) => row.priority ?? 0,
    },
    {
      key: 'scope',
      label: 'Scope',
      width: '12%',
      render: (row) => <Badge variant="secondary">{row.scope || 'platform'}</Badge>,
    },
    {
      key: 'actions',
      label: '',
      width: '23%',
      align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canAssign && (
            <Button variant="secondary" size="sm" onClick={() => { setAssignRoleId(row.id); setSelectedUserId(''); }}>
              Assign
            </Button>
          )}
          {canEdit && (
            <Button variant="secondary" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          {canDelete && !row.is_protected && (
            <Button variant="danger" size="sm" onClick={() => handleDelete(row.id)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const visibleRoles = can_manage_super_admin ? roles : roles.filter(r => r.name !== 'Super Administrator');

  return (
    <IndexPageLayout
      title="Role Management"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Roles' },
      ]}
      description="Manage roles and assign them to users."
      actions={
        canCreate && (
          <Button variant="primary" onClick={openCreate}>
            Create Role
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={visibleRoles}
          empty="No roles found."
        />
      </VStack>

      {/* Create / Edit Modal */}
      {showCreate && (
        <Modal
          title={editingRole ? 'Edit Role' : 'Create Role'}
          onClose={resetForm}
          footer={
            <HStack gap={2}>
              <Button variant="secondary" onClick={resetForm}>Cancel</Button>
              <Button variant="primary" onClick={editingRole ? handleUpdate : handleCreate}>
                {editingRole ? 'Save Changes' : 'Create Role'}
              </Button>
            </HStack>
          }
        >
          <VStack gap={3} className="min-w-[400px]">
            <Input
              label="Role Name *"
              value={formData.name}
              onChange={e => setFormData({ ...formData, name: e.target.value })}
              required
            />
            <Input
              label="Description"
              value={formData.description}
              onChange={e => setFormData({ ...formData, description: e.target.value })}
            />
            <Input
              label="Default Dashboard"
              value={formData.default_dashboard}
              onChange={e => setFormData({ ...formData, default_dashboard: e.target.value })}
              placeholder="e.g. core.dashboard"
            />
            <Input
              label="Priority"
              type="number"
              value={formData.priority}
              onChange={e => setFormData({ ...formData, priority: parseInt(e.target.value) || 0 })}
            />
          </VStack>
        </Modal>
      )}

      {/* Assign Role Modal */}
      {assignRoleId && (
        <Modal
          title="Assign Role to User"
          onClose={() => { setAssignRoleId(null); setSelectedUserId(''); }}
          footer={
            <HStack gap={2}>
              <Button variant="secondary" onClick={() => { setAssignRoleId(null); setSelectedUserId(''); }}>
                Cancel
              </Button>
              <Button variant="primary" onClick={handleAssign} disabled={!selectedUserId}>
                Assign
              </Button>
            </HStack>
          }
        >
          <VStack gap={3} className="min-w-[300px]">
            <Text>Assigning: <strong>{roles.find(r => r.id === assignRoleId)?.name}</strong></Text>
            <Select
              label="Select User *"
              value={selectedUserId}
              onChange={e => setSelectedUserId(e.target.value)}
              options={[
                { value: '', label: 'Choose a user...' },
                ...users.map(u => ({ value: String(u.id), label: `${u.name} (${u.email})` })),
              ]}
            />
          </VStack>
        </Modal>
      )}
    </IndexPageLayout>
  );
}

RolesIndex.layout = page => (
  <App title="Role Management">{page}</App>
);
