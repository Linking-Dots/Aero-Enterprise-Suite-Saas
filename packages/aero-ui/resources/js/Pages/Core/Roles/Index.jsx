import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
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
import App from '@/Pages/App.jsx';

export default function RolesIndex({ roles, users, can_manage_super_admin, error }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('core.roles_permissions.roles.create');
  const canEdit    = useHRMAC('core.roles_permissions.roles.edit');
  const canDelete  = useHRMAC('core.roles_permissions.roles.delete');
  const canAssign  = useHRMAC('core.roles_permissions.roles.assign');

  const [showCreate,   setShowCreate]   = useState(false);
  const [editingRole,  setEditingRole]  = useState(null);
  const [assignRoleId, setAssignRoleId] = useState(null);
  const [selectedUserId, setSelectedUserId] = useState('');
  const [formData, setFormData] = useState({
    name: '', description: '', default_dashboard: '', priority: 0,
  });

  if (error) {
    return (
      <IndexPageLayout
        title="Role Management"
        breadcrumb={[{ label: 'Dashboard', href: route('core.dashboard') }, { label: 'Roles' }]}
      >
        <Card>
          <CardContent>
            <Text tone="secondary">Error loading roles: {error.message}</Text>
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

  const handleCreate = e => {
    e.preventDefault();
    router.post(route('core.roles.store'), formData, {
      preserveState: true,
      onSuccess: () => { toast.success('Role created.'); resetForm(); },
      onError:   errors => { const first = Object.values(errors)[0]; toast.error(first || 'Failed to create role'); },
    });
  };

  const handleUpdate = e => {
    e.preventDefault();
    router.put(route('core.roles.update', editingRole.id), formData, {
      preserveState: true,
      onSuccess: () => { toast.success('Role updated.'); resetForm(); },
      onError:   errors => { const first = Object.values(errors)[0]; toast.error(first || 'Failed to update role'); },
    });
  };

  const handleDelete = id => {
    if (!confirm('Are you sure you want to delete this role?')) return;
    router.delete(route('core.roles.destroy', id), {
      onSuccess: () => toast.success('Role deleted.'),
      onError:   () => toast.error('Failed to delete role'),
    });
  };

  const handleAssign = e => {
    e.preventDefault();
    if (!selectedUserId || !assignRoleId) return;
    router.post(route('core.roles.assign-user'), { user_id: selectedUserId, roles: [assignRoleId] }, {
      preserveState: true,
      onSuccess: () => { toast.success('Role assigned.'); setAssignRoleId(null); setSelectedUserId(''); },
      onError:   () => toast.error('Failed to assign role'),
    });
  };

  const openEdit = role => {
    setEditingRole(role);
    setFormData({
      name:              role.name              || '',
      description:       role.description       || '',
      default_dashboard: role.default_dashboard || '',
      priority:          role.priority          || 0,
    });
    setShowCreate(true);
  };

  const columns = [
    {
      key: 'name', label: 'Name', width: '18%',
      render: row => (
        <HStack gap={2} align="center">
          <Text size="sm">{row.name}</Text>
          {row.is_protected && <Badge intent="warning" size="sm">Protected</Badge>}
        </HStack>
      ),
    },
    { key: 'description', label: 'Description', width: '22%', render: row => row.description || '—' },
    { key: 'users_count',         label: 'Users',         width: '9%', render: row => row.users_count ?? 0 },
    { key: 'module_access_count', label: 'Module Access', width: '11%', render: row => (
      <Badge intent="neutral" size="sm">{row.module_access_count ?? 0} grant{(row.module_access_count ?? 0) === 1 ? '' : 's'}</Badge>
    )},
    { key: 'priority',          label: 'Priority',    width: '9%', render: row => row.priority ?? 0 },
    {
      key: 'scope', label: 'Scope', width: '10%',
      render: row => <Badge intent="neutral" size="sm">{row.scope || 'platform'}</Badge>,
    },
    {
      key: 'actions', label: '', width: '23%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canAssign && (
            <Button intent="soft" size="sm" onClick={() => { setAssignRoleId(row.id); setSelectedUserId(''); }}>
              Assign
            </Button>
          )}
          {canEdit && (
            <Button intent="soft" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          )}
          {canDelete && !row.is_protected && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row.id)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  const visibleRoles = can_manage_super_admin
    ? (roles ?? [])
    : (roles ?? []).filter(r => r.name !== 'Super Administrator');

  return (
    <>
      <IndexPageLayout
        title="Role Management"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Roles' },
        ]}
        description="Manage roles and assign them to users."
        actions={
          canCreate && (
            <Button intent="primary" onClick={() => { resetForm(); setShowCreate(true); }}>
              Create Role
            </Button>
          )
        }
        table={
          <DataTable
            columns={columns}
            rows={visibleRoles}
            empty="No roles found."
          />
        }
      />

      {/* Create / Edit Modal */}
      <Modal
        open={showCreate}
        title={editingRole ? 'Edit Role' : 'Create Role'}
        onClose={resetForm}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={resetForm}>Cancel</Button>
            <Button intent="primary" onClick={editingRole ? handleUpdate : handleCreate}>
              {editingRole ? 'Save Changes' : 'Create Role'}
            </Button>
          </HStack>
        }
      >
        <form onSubmit={editingRole ? handleUpdate : handleCreate}>
          <VStack gap={3}>
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
        </form>
      </Modal>

      {/* Assign Role Modal */}
      <Modal
        open={!!assignRoleId}
        title="Assign Role to User"
        onClose={() => { setAssignRoleId(null); setSelectedUserId(''); }}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => { setAssignRoleId(null); setSelectedUserId(''); }}>
              Cancel
            </Button>
            <Button intent="primary" onClick={handleAssign} disabled={!selectedUserId}>
              Assign
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleAssign}>
          <VStack gap={3}>
            <Text size="sm">
              Assigning: <strong>{(roles ?? []).find(r => r.id === assignRoleId)?.name}</strong>
            </Text>
            <Select
              label="Select User *"
              value={selectedUserId}
              onChange={e => setSelectedUserId(e.target.value)}
              options={[
                { value: '', label: 'Choose a user…' },
                ...(users ?? []).map(u => ({ value: String(u.id), label: `${u.name} (${u.email})` })),
              ]}
            />
          </VStack>
        </form>
      </Modal>
    </>
  );
}

RolesIndex.layout = page => (
  <App title="Role Management">{page}</App>
);
