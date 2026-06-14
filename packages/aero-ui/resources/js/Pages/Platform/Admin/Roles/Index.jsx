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
  Input,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

/**
 * Platform roles — rendered by the shared HRMAC RoleController (central DB).
 * Roles carry no Spatie permissions; authorization is HRMAC module-access, edited
 * via the platform module-access surface. CRUD happens inline here.
 */
export default function RolesIndex({ roles, can_manage_super_admin }) {
  const toast     = useToast();
  const canManage = useHRMAC('platform-users.landlord-roles.manage');

  const [showForm, setShowForm]       = useState(false);
  const [editing, setEditing]         = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [form, setForm] = useState({ name: '', description: '' });

  const reset = () => { setForm({ name: '', description: '' }); setEditing(null); setShowForm(false); };

  const openCreate = () => { reset(); setShowForm(true); };
  const openEdit   = (row) => { setEditing(row); setForm({ name: row.name || '', description: row.description || '' }); setShowForm(true); };

  const submit = (e) => {
    e?.preventDefault();
    const opts = {
      preserveState: true,
      onSuccess: () => { toast.success(editing ? 'Role updated.' : 'Role created.'); reset(); },
      onError: (errs) => toast.error(Object.values(errs)[0] || 'Save failed.'),
    };
    if (editing) router.put(route('platform.admin.roles.update', editing.id), form, opts);
    else router.post(route('platform.admin.roles.store'), form, opts);
  };

  const confirmDelete = () => {
    if (!deleteTarget) return;
    router.delete(route('platform.admin.roles.destroy', deleteTarget.id), {
      onSuccess: () => { toast.success('Role deleted.'); setDeleteTarget(null); },
      onError: () => toast.error('Failed to delete role.'),
    });
  };

  const visibleRoles = can_manage_super_admin
    ? (roles ?? [])
    : (roles ?? []).filter(r => !r.is_protected);

  const columns = [
    {
      key: 'name', label: 'Role Name',
      render: (row) => (
        <HStack gap={2} align="center">
          <Text size="sm">{row.name}</Text>
          {row.is_protected && <Badge intent="warning" size="sm">Protected</Badge>}
        </HStack>
      ),
    },
    { key: 'description', label: 'Description', render: (row) => <Text tone="secondary">{row.description ?? '—'}</Text> },
    {
      key: 'module_access_count', label: 'Module Access',
      render: (row) => <Badge intent="neutral" size="sm">{row.module_access_count ?? 0} grant{(row.module_access_count ?? 0) === 1 ? '' : 's'}</Badge>,
    },
    { key: 'users_count', label: 'Users', render: (row) => <Text tone="secondary">{row.users_count ?? 0}</Text> },
    {
      key: 'actions', label: '', align: 'right',
      render: (row) => (
        <HStack gap={1} justify="end">
          {canManage && <Button intent="soft" size="sm" onClick={() => openEdit(row)}>Edit</Button>}
          {canManage && !row.is_protected && (
            <Button intent="danger" size="sm" onClick={() => setDeleteTarget(row)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Roles"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Roles' },
      ]}
      description="Manage platform roles. Module access is granted via the module-access editor."
      actions={canManage && <Button intent="primary" onClick={openCreate}>New Role</Button>}
      table={<DataTable columns={columns} rows={visibleRoles} empty="No roles found." />}
    >
      <Modal
        open={showForm}
        onClose={reset}
        title={editing ? 'Edit Role' : 'New Role'}
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={reset}>Cancel</Button>
            <Button intent="primary" onClick={submit}>{editing ? 'Save Changes' : 'Create Role'}</Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={3}>
            <Input label="Role Name *" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required />
            <Input label="Description" value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} />
          </VStack>
        </form>
      </Modal>

      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Role"
        description={`Permanently delete the role "${deleteTarget?.name}"? Users assigned this role will lose its access.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmDelete}>Delete</Button>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>Cancel</Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

RolesIndex.layout = page => <App title="Roles">{page}</App>;
