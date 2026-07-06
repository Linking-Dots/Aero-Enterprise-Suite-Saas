import { useState, useEffect, useMemo } from 'react';
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
  useToast,
  Card, CardContent,
  useHRMAC,
  SavedViewsDropdown,
  Stat,
  Menu,
  Tabs,
  Drawer,
  EmptyState,
} from '@aero/ui';
import { EllipsisHorizontalIcon } from '@heroicons/react/24/outline';
import App from '@/Pages/App.jsx';
import RolesRail from './RolesRail.jsx';
import AccessDrawer from './AccessDrawer.jsx';

const PROTECTED_HIDDEN = 'Super Administrator';

/**
 * Shared, context-parameterized Roles & Access index. Renders identically for
 * every calling context (tenant admin, platform admin, …) — only the route-name
 * prefix, module-access route prefix, HRMAC gate namespace, and dashboard
 * breadcrumb target differ, all supplied by the controller as page props.
 *
 * Prop contract:
 * - routePrefix            role CRUD/assign route-name prefix (default 'core.roles').
 * - moduleAccessRoutePrefix module-access show/sync route-name prefix, consumed
 *                           by AccessDrawer (default 'core.modules').
 * - hrmacNamespace         HRMAC gate namespace every `useHRMAC()` call builds on
 *                           (default 'core.roles_permissions').
 * - scope                  'tenant' | 'platform' (default 'tenant').
 * - dashboardRoute         breadcrumb target (falls back per scope).
 * - roles, users, modules, accessScopes, statistics, can_manage_super_admin — data.
 */
export default function RolesIndex({
  roles, users, can_manage_super_admin, modules, accessScopes, statistics, error,
  routePrefix, moduleAccessRoutePrefix, hrmacNamespace, scope, dashboardRoute,
}) {
  routePrefix             = routePrefix             ?? 'core.roles';
  moduleAccessRoutePrefix = moduleAccessRoutePrefix ?? 'core.modules';
  hrmacNamespace          = hrmacNamespace          ?? 'core.roles_permissions';
  scope                   = scope                   ?? 'tenant';
  dashboardRoute          = dashboardRoute           ?? (scope === 'platform' ? 'platform.admin.dashboard' : 'core.dashboard');
  const moduleCode = scope === 'platform' ? 'platform' : 'core';

  const toast       = useToast();
  const canCreate   = useHRMAC(`${hrmacNamespace}.roles.create`);
  const canEdit     = useHRMAC(`${hrmacNamespace}.roles.edit`);
  const canDelete   = useHRMAC(`${hrmacNamespace}.roles.delete`);
  const canAssign   = useHRMAC(`${hrmacNamespace}.roles.assign`);
  const canConfigure = useHRMAC(`${hrmacNamespace}.module_access.configure`);

  // ── Filters & tabs (client-side: the role list is small and returned whole) ──
  const [tab, setTab]       = useState('all');
  const [search, setSearch] = useState('');
  const [scopeFilter, setScopeFilter] = useState('');

  // ── Skeleton while an Inertia reload (create/edit/delete) is in flight ──
  const [tableLoading, setTableLoading] = useState(false);
  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  // ── Drawers ──
  const [roleDrawer, setRoleDrawer]   = useState(false);   // create/edit role
  const [editingRole, setEditingRole] = useState(null);
  const [assignOpen, setAssignOpen]   = useState(false);   // assign user
  const [accessRole, setAccessRole]   = useState(null);    // module-access drawer

  const roleForm = useForm({ name: '', description: '', default_dashboard: '', priority: 0 });
  const assignForm = useForm({ user_id: '', roles: [] });

  const openCreate = () => { setEditingRole(null); roleForm.reset(); roleForm.clearErrors(); setRoleDrawer(true); };
  const openEdit = role => {
    setEditingRole(role);
    roleForm.setData({
      name: role.name || '',
      description: role.description || '',
      default_dashboard: role.default_dashboard || '',
      priority: role.priority || 0,
    });
    roleForm.clearErrors();
    setRoleDrawer(true);
  };
  const openAssign = role => {
    assignForm.setData({ user_id: '', roles: role ? [String(role.id)] : [] });
    assignForm.clearErrors();
    setAssignOpen(true);
  };

  // ── Rail / deep-link wiring ──
  useEffect(() => {
    const onCreate = () => canCreate && openCreate();
    const onAssign = () => canAssign && openAssign(null);
    const onSaved  = () => router.reload({ only: ['roles'] }); // refresh access counts after a sync
    window.addEventListener('aeos:open-create-role', onCreate);
    window.addEventListener('aeos:open-assign-role', onAssign);
    window.addEventListener('aeos:access-saved', onSaved);
    return () => {
      window.removeEventListener('aeos:open-create-role', onCreate);
      window.removeEventListener('aeos:open-assign-role', onAssign);
      window.removeEventListener('aeos:access-saved', onSaved);
    };
  }, [canCreate, canAssign]);

  // ?role=ID deep-link (e.g. the retired /modules redirect) auto-opens the access drawer.
  useEffect(() => {
    const id = new URLSearchParams(window.location.search).get('role');
    if (id && roles) {
      const r = roles.find(x => String(x.id) === String(id));
      if (r) setAccessRole(r);
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const submitRole = e => {
    e?.preventDefault();
    const opts = {
      preserveScroll: true,
      onSuccess: () => { toast.success(editingRole ? 'Role updated.' : 'Role created.'); setRoleDrawer(false); roleForm.reset(); setEditingRole(null); },
      onError: () => toast.error(editingRole ? 'Failed to update role.' : 'Failed to create role.'),
    };
    editingRole
      ? roleForm.put(route(`${routePrefix}.update`, editingRole.id), opts)
      : roleForm.post(route(`${routePrefix}.store`), opts);
  };

  const submitAssign = e => {
    e?.preventDefault();
    if (!assignForm.data.user_id || !assignForm.data.roles.length) return;
    assignForm.post(route(`${routePrefix}.assign-user`), {
      preserveScroll: true,
      onSuccess: () => { toast.success('Role assigned.'); setAssignOpen(false); assignForm.reset(); },
      onError:   () => toast.error('Failed to assign role.'),
    });
  };

  const deleteRole = role => {
    if (!confirm(`Delete the “${role.name}” role?`)) return;
    router.delete(route(`${routePrefix}.destroy`, role.id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Role deleted.'),
      onError:   () => toast.error('Failed to delete role.'),
    });
  };

  // ── Derived data ──
  const baseRoles = useMemo(
    () => (can_manage_super_admin ? (roles ?? []) : (roles ?? []).filter(r => r.name !== PROTECTED_HIDDEN)),
    [roles, can_manage_super_admin],
  );

  const counts = useMemo(() => ({
    total:     statistics?.total     ?? baseRoles.length,
    protected: statistics?.protected ?? baseRoles.filter(r => r.is_protected).length,
    custom:    statistics?.custom    ?? baseRoles.filter(r => !r.is_protected).length,
    assigns:   statistics?.assigns   ?? baseRoles.reduce((s, r) => s + (r.users_count ?? 0), 0),
  }), [baseRoles, statistics]);

  const scopeOptions = useMemo(() => {
    const set = [...new Set(baseRoles.map(r => r.scope).filter(Boolean))];
    return [{ value: '', label: 'All scopes' }, ...set.map(s => ({ value: s, label: s }))];
  }, [baseRoles]);

  const q = search.trim().toLowerCase();
  const filteredRoles = baseRoles.filter(r => {
    if (tab === 'custom' && r.is_protected) return false;
    if (tab === 'protected' && !r.is_protected) return false;
    if (scopeFilter && r.scope !== scopeFilter) return false;
    if (q && !(`${r.name} ${r.description ?? ''}`.toLowerCase().includes(q))) return false;
    return true;
  });

  const hasActiveFilter = !!(q || scopeFilter || tab !== 'all');
  const resetFilters = () => { setSearch(''); setScopeFilter(''); setTab('all'); };

  const handleApplySavedView = config => {
    if (config.filters) { setSearch(config.filters.search || ''); setScopeFilter(config.filters.scope || ''); }
  };
  const getCurrentFilters = () => ({ filters: { search, scope: scopeFilter }, sort: null, columns: null });

  const columns = [
    {
      key: 'name', label: 'Role', width: '20%',
      render: row => (
        <HStack gap={2} align="center">
          <Text size="sm" weight={500}>{row.name}</Text>
          {row.is_protected && <Badge intent="warning" size="sm">Protected</Badge>}
        </HStack>
      ),
    },
    { key: 'description', label: 'Description', width: '26%', render: row => row.description || <Text tone="tertiary" size="sm">—</Text> },
    { key: 'users_count', label: 'Users', width: '9%', render: row => row.users_count ?? 0 },
    {
      key: 'module_access_count', label: 'Access', width: '12%',
      render: row => (
        <Badge intent="indigo" size="sm">
          {row.module_access_count ?? 0} grant{(row.module_access_count ?? 0) === 1 ? '' : 's'}
        </Badge>
      ),
    },
    { key: 'priority', label: 'Priority', width: '9%', render: row => row.priority ?? 0 },
    { key: 'scope', label: 'Scope', width: '10%', render: row => <Badge intent="neutral" size="sm">{row.scope || 'platform'}</Badge> },
    {
      key: 'actions', label: '', width: '120px', align: 'right',
      render: row => {
        const menuItems = [
          canConfigure && { label: 'Configure access', onClick: () => setAccessRole(row) },
          canAssign && { label: 'Assign user', onClick: () => openAssign(row) },
          canDelete && !row.is_protected && { divider: true },
          canDelete && !row.is_protected && { label: 'Delete', danger: true, onClick: () => deleteRole(row) },
        ].filter(Boolean);

        return (
          <HStack gap={1} justify="end" align="center">
            {canEdit && !row.is_protected && (
              <Button intent="soft" size="sm" onClick={() => openEdit(row)}>Edit</Button>
            )}
            {menuItems.length > 0 && (
              <Menu
                align="end"
                trigger={
                  <Button intent="ghost" size="sm" aria-label="More actions">
                    <EllipsisHorizontalIcon className="aeos-icon-sm" />
                  </Button>
                }
                items={menuItems}
              />
            )}
          </HStack>
        );
      },
    },
  ];

  if (error) {
    return (
      <IndexPageLayout
        title="Roles & Access"
        breadcrumb={[{ label: 'Dashboard', href: route(dashboardRoute) }, { label: 'Roles & Access' }]}
      >
        <Card><CardContent><Text tone="secondary">Error loading roles: {error.message}</Text></CardContent></Card>
      </IndexPageLayout>
    );
  }

  return (
    <>
      <IndexPageLayout
        title="Roles & Access"
        breadcrumb={[
          { label: 'Dashboard', href: route(dashboardRoute) },
          { label: 'Roles & Access' },
        ]}
        description="Manage roles, assign them to users, and grant each role access to modules."
        tabs={
          <Tabs
            value={tab}
            tabs={[
              { value: 'all',       label: 'All roles', count: counts.total },
              { value: 'custom',    label: 'Custom',    count: counts.custom },
              { value: 'protected', label: 'Protected', count: counts.protected },
            ]}
            onChange={setTab}
          />
        }
        actions={
          canCreate && (
            <HStack gap={2}>
              {canAssign && (
                <Button intent="ghost" onClick={() => openAssign(null)}>Assign user</Button>
              )}
              <Button intent="primary" onClick={openCreate}>Create role</Button>
            </HStack>
          )
        }
        kpis={[
          <Stat key="total"     title="Total Roles"  value={counts.total}     icon="users"  />,
          <Stat key="custom"    title="Custom"       value={counts.custom}    icon="pencil" iconTone="indigo" />,
          <Stat key="protected" title="Protected"    value={counts.protected} icon="star"   iconTone="amber" />,
          <Stat key="assigns"   title="Assignments"  value={counts.assigns}   icon="user"   iconTone="success" />,
        ]}
        filters={
          <HStack gap={3} align="end" wrap>
            <Input
              placeholder="Search name or description…"
              value={search}
              onChange={e => setSearch(e.target.value)}
            />
            <Select value={scopeFilter} onChange={e => setScopeFilter(e.target.value)} options={scopeOptions} />
            <Button intent="ghost" onClick={resetFilters}>Reset</Button>
            <SavedViewsDropdown
              moduleCode={moduleCode}
              route={route(`${routePrefix}.index`)}
              currentFilters={getCurrentFilters()}
              onApply={handleApplySavedView}
            />
          </HStack>
        }
        table={
          !tableLoading && filteredRoles.length === 0 ? (
            hasActiveFilter ? (
              <EmptyState
                icon="inbox"
                title="No roles match your filters"
                description="Try adjusting your search, scope, or tab."
                action={<Button intent="ghost" onClick={resetFilters}>Reset filters</Button>}
              />
            ) : (
              <EmptyState
                icon="users"
                title="No roles yet"
                description="Create your first role, then grant it access to the modules its members need."
                action={canCreate && <Button intent="primary" onClick={openCreate}>Create role</Button>}
              />
            )
          ) : (
            <DataTable columns={columns} rows={filteredRoles} loading={tableLoading} />
          )
        }
      />

      {/* Create / Edit role drawer */}
      <Drawer
        open={roleDrawer}
        onClose={() => { setRoleDrawer(false); setEditingRole(null); }}
        title={editingRole ? 'Edit role' : 'Create role'}
        width={460}
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => { setRoleDrawer(false); setEditingRole(null); }}>Cancel</Button>
            <Button intent="primary" onClick={submitRole} loading={roleForm.processing}>
              {editingRole ? 'Save changes' : 'Create role'}
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submitRole}>
          <VStack gap={4}>
            <VStack gap={1}>
              <Input
                label="Role name"
                value={roleForm.data.name}
                onChange={e => roleForm.setData('name', e.target.value)}
                error={!!roleForm.errors.name}
                autoFocus
                required
              />
              {roleForm.errors.name && <Text size="xs" tone="danger">{roleForm.errors.name}</Text>}
            </VStack>
            <Input
              label="Description"
              value={roleForm.data.description}
              onChange={e => roleForm.setData('description', e.target.value)}
            />
            <Input
              label="Default dashboard"
              value={roleForm.data.default_dashboard}
              onChange={e => roleForm.setData('default_dashboard', e.target.value)}
              placeholder="e.g. core.dashboard"
            />
            <Input
              label="Priority"
              type="number"
              value={roleForm.data.priority}
              onChange={e => roleForm.setData('priority', parseInt(e.target.value) || 0)}
            />
          </VStack>
        </form>
      </Drawer>

      {/* Assign-user drawer */}
      <Drawer
        open={assignOpen}
        onClose={() => setAssignOpen(false)}
        title="Assign role to user"
        width={460}
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setAssignOpen(false)}>Cancel</Button>
            <Button intent="primary" onClick={submitAssign} loading={assignForm.processing}
              disabled={!assignForm.data.user_id || !assignForm.data.roles.length}>
              Assign role
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Text size="sm" tone="secondary">Pick a role and the user who should receive it.</Text>
          <Select
            label="Role"
            value={assignForm.data.roles[0] ?? ''}
            onChange={e => assignForm.setData('roles', e.target.value ? [e.target.value] : [])}
            options={[
              { value: '', label: 'Choose a role…' },
              ...baseRoles.map(r => ({ value: String(r.id), label: r.name })),
            ]}
          />
          <Select
            label="User"
            value={assignForm.data.user_id}
            onChange={e => assignForm.setData('user_id', e.target.value)}
            options={[
              { value: '', label: 'Choose a user…' },
              ...(users ?? []).map(u => ({ value: String(u.id), label: `${u.name} (${u.email})` })),
            ]}
          />
        </VStack>
      </Drawer>

      {/* Module-access drawer (wide) */}
      <AccessDrawer
        role={accessRole}
        modules={modules}
        accessScopes={accessScopes}
        canConfigure={canConfigure}
        moduleAccessRoutePrefix={moduleAccessRoutePrefix}
        onClose={() => setAccessRole(null)}
      />
    </>
  );
}

RolesIndex.layout = page => (
  <App title="Roles & Access" railTitle="Roles & access" rail={
    <RolesRail
      roles={page.props.roles}
      routePrefix={page.props.routePrefix}
      scope={page.props.scope}
    />
  }>{page}</App>
);
