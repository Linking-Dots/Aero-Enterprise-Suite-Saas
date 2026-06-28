import { useState, useEffect, useRef } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack, Box,
  Text,
  Input,
  Select,
  useToast,
  Card, CardContent,
  useHRMAC,
  SavedViewsDropdown,
  Stat,
  Avatar,
  Menu,
  Tabs,
  Drawer,
  EmptyState,
} from '@aero/ui';
import { EllipsisHorizontalIcon } from '@heroicons/react/24/outline';
import App from '@/Pages/App.jsx';
import UsersRail from './UsersRail.jsx';

export default function UsersIndex({ users, roles, invitations, filters, stats }) {
  const toast = useToast();
  const canCreate     = useHRMAC('core.user_management.users.create');
  const canEdit       = useHRMAC('core.user_management.users.edit');
  const canDelete     = useHRMAC('core.user_management.users.delete');
  const canView       = useHRMAC('core.user_management.users.view');
  const canActivate   = useHRMAC('core.user_management.users.activate');
  const canImpersonate = useHRMAC('core.user_management.users.impersonate');
  const canBulkDelete = useHRMAC('core.user_management.users.bulk_delete');
  const canResend     = useHRMAC('core.user_management.user_invitations.resend');
  const canCancel     = useHRMAC('core.user_management.user_invitations.cancel');

  const [selectedIds, setSelectedIds] = useState([]);
  const [search, setSearch]   = useState(filters?.search  || '');
  const [status, setStatus]   = useState(filters?.status  || '');
  const [role,   setRole]     = useState(filters?.role    || '');

  // Tabs cover two datasets: the user list (all/active/inactive filter the same
  // table) and pending invitations (a different view). `view` switches datasets.
  const [view, setView] = useState('users');
  const invites = Array.isArray(invitations) ? invitations : (invitations?.data ?? []);

  // Show the table skeleton while any partial reload (tab/filter/page) is in
  // flight, so the user gets feedback instead of a frozen stale table.
  const [tableLoading, setTableLoading] = useState(false);
  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const resendInvite = id => {
    router.post(route('core.users.invitations.resend', id), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success('Invitation resent.'),
      onError:   () => toast.error('Failed to resend invitation.'),
    });
  };

  const cancelInvite = id => {
    if (!confirm('Cancel this invitation?')) return;
    router.delete(route('core.users.invitations.cancel', id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Invitation cancelled.'),
      onError:   () => toast.error('Failed to cancel invitation.'),
    });
  };

  // Invite-first: primary action sends an email invitation (invitee sets their own
  // password). Direct create remains as a secondary admin-provisioning path.
  const [inviteOpen, setInviteOpen] = useState(false);
  const inviteForm = useForm({ email: '', roles: [] });

  const submitInvite = e => {
    e.preventDefault();
    inviteForm.post(route('core.users.invitations.store'), {
      preserveScroll: true,
      onSuccess: () => { toast.success('Invitation sent.'); inviteForm.reset(); setInviteOpen(false); },
      onError:   () => toast.error('Could not send invitation.'),
    });
  };

  // The command-shell context rail (UsersRail) triggers the invite drawer via a
  // window event, so the rail stays decoupled from this page's local state.
  useEffect(() => {
    const open = () => setInviteOpen(true);
    window.addEventListener('aeos:open-invite', open);
    return () => window.removeEventListener('aeos:open-invite', open);
  }, []);

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
      key: 'name', label: 'User', width: '24%',
      render: row => (
        <HStack gap={3} align="center">
          <Avatar name={row.name} size={32} />
          <VStack gap={0}>
            <Text size="sm" weight={500}>{row.name}</Text>
            {row.user_name && <Text size="xs" tone="tertiary" mono>@{row.user_name}</Text>}
          </VStack>
        </HStack>
      ),
    },
    { key: 'email', label: 'Email', width: '20%', render: row => row.email },
    {
      key: 'roles', label: 'Role', width: '16%',
      render: row => (
        <HStack gap={1} wrap>
          {row.roles?.length
            ? row.roles.map(r => <Badge key={r.id} intent="indigo" size="sm">{r.name}</Badge>)
            : <Text tone="tertiary" size="sm">—</Text>}
        </HStack>
      ),
    },
    {
      key: 'status', label: 'Status', width: '12%',
      render: row => (
        <Badge intent={row.deleted_at ? 'warning' : 'success'} dot>
          {row.deleted_at ? 'Inactive' : 'Active'}
        </Badge>
      ),
    },
    { key: 'created_at', label: 'Joined', width: '12%', render: row => new Date(row.created_at).toLocaleDateString() },
    {
      key: 'actions', label: '', width: '120px', align: 'right',
      // Primary action (Edit) stays inline; everything else collapses into a single
      // overflow menu so rows stay scannable instead of a wall of 5 buttons.
      render: row => {
        const isActive = !row.deleted_at; // active = not trashed (SoftDeletes)
        const menuItems = [
          canView && { label: 'View profile', onClick: () => router.visit(route('core.users.show', row.id)) },
          canActivate && (isActive
            ? { label: 'Deactivate', onClick: () => toggleUserStatus(row.id, false) }
            : { label: 'Activate',   onClick: () => toggleUserStatus(row.id, true) }),
          canImpersonate && { label: 'Impersonate', onClick: () => impersonateUser(row.id) },
          canDelete && { divider: true },
          canDelete && { label: 'Delete', danger: true, onClick: () => deleteUser(row.id) },
        ].filter(Boolean);

        return (
          <HStack gap={1} justify="end" align="center">
            {canEdit && (
              <Button intent="soft" size="sm" onClick={() => router.visit(route('core.users.edit', row.id))}>
                Edit
              </Button>
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

  const invitationColumns = [
    {
      key: 'email', label: 'Invitee', width: '30%',
      render: row => (
        <HStack gap={3} align="center">
          <Avatar name={row.email} size={32} />
          <VStack gap={0}>
            <Text size="sm" weight={500}>{row.email}</Text>
            {row.inviter?.name && (
              <Text size="xs" tone="tertiary">Invited by {row.inviter.name}</Text>
            )}
          </VStack>
        </HStack>
      ),
    },
    {
      key: 'roles', label: 'Role', width: '18%',
      render: row => (
        <HStack gap={1} wrap>
          {row.roles?.length
            ? row.roles.map((r, i) => <Badge key={i} intent="indigo" size="sm">{r}</Badge>)
            : <Text tone="tertiary" size="sm">—</Text>}
        </HStack>
      ),
    },
    {
      key: 'status', label: 'Status', width: '16%',
      render: row => {
        const expired = row.expires_at && new Date(row.expires_at) < new Date();
        return <Badge intent={expired ? 'warning' : 'cyan'} dot>{expired ? 'Expired' : 'Pending'}</Badge>;
      },
    },
    { key: 'sent', label: 'Sent', width: '14%', render: row => new Date(row.created_at).toLocaleDateString() },
    {
      key: 'actions', label: '', width: '160px', align: 'right',
      render: row => (
        <HStack gap={1} justify="end">
          {canResend && (
            <Button intent="soft"  size="sm" onClick={() => resendInvite(row.id)}>Resend</Button>
          )}
          {canCancel && (
            <Button intent="ghost" size="sm" onClick={() => cancelInvite(row.id)}>Cancel</Button>
          )}
        </HStack>
      ),
    },
  ];

  const showingInvites = view === 'invitations';
  const hasActiveFilter = !!(search || status || role);
  const userRows = users?.data ?? [];

  return (
    <>
    <IndexPageLayout
      title="Users"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Users' },
      ]}
      description="Manage user accounts, roles, and permissions."
      tabs={
        <Tabs
          value={showingInvites ? 'invitations' : (status || 'all')}
          tabs={[
            { value: 'all',         label: 'All users',           count: stats?.total },
            { value: 'active',      label: 'Active',              count: stats?.active },
            { value: 'inactive',    label: 'Deactivated',         count: stats?.inactive },
            { value: 'invitations', label: 'Pending invitations', count: stats?.pending },
          ]}
          onChange={v => {
            // Tabs switch content in place — never navigate. The first three filter
            // the user table; "invitations" swaps to the pending-invites dataset.
            if (v === 'invitations') { setView('invitations'); return; }
            setView('users');
            const newStatus = v === 'all' ? '' : v;
            setStatus(newStatus);
            router.get(route('core.users.index'), { search, status: newStatus, role }, {
              preserveState: true, preserveScroll: true, only: ['users', 'filters'],
            });
          }}
        />
      }
      actions={
        canCreate && (
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => router.visit(route('core.users.create'))}>
              Create user
            </Button>
            <Button intent="primary" onClick={() => setInviteOpen(true)}>
              Invite user
            </Button>
          </HStack>
        )
      }
      kpis={[
        <Stat key="total"    title="Total Users" value={stats?.total    ?? 0} icon="users"  />,
        <Stat key="active"   title="Active"      value={stats?.active   ?? 0} icon="users"  iconTone="success" />,
        <Stat key="inactive" title="Inactive"    value={stats?.inactive ?? 0} icon="users"  iconTone="amber" />,
      ]}
      filters={
        showingInvites ? null : (
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
        )
      }
      table={
        showingInvites ? (
          !tableLoading && invites.length === 0 ? (
            <EmptyState
              icon="inbox"
              title="No pending invitations"
              description="Invite teammates by email — they set their own password and join with the role you choose."
              action={canCreate && (
                <Button intent="primary" onClick={() => setInviteOpen(true)}>Invite user</Button>
              )}
            />
          ) : (
            <DataTable columns={invitationColumns} rows={invites} loading={tableLoading} />
          )
        ) : (
        <VStack gap={3}>
          {selectedIds.length > 0 && (
            <HStack gap={2} align="center" className="aeos-table-bulkbar">
              <Text size="sm" weight={600}>{selectedIds.length} selected</Text>
              {canActivate && (
                <Button intent="soft"  size="sm" onClick={() => handleBulkToggle(true)}>Activate</Button>
              )}
              {canActivate && (
                <Button intent="ghost" size="sm" onClick={() => handleBulkToggle(false)}>Deactivate</Button>
              )}
              {canBulkDelete && (
                <Button intent="danger" size="sm" onClick={handleBulkDelete}>Delete</Button>
              )}
              <Box grow />
              <Button intent="ghost" size="sm" onClick={() => setSelectedIds([])}>Clear</Button>
            </HStack>
          )}
          {!tableLoading && userRows.length === 0 ? (
            hasActiveFilter ? (
              <EmptyState
                icon="users"
                title="No users match your filters"
                description="Try adjusting your search, status, or role filters."
                action={<Button intent="ghost" onClick={resetFilters}>Reset filters</Button>}
              />
            ) : (
              <EmptyState
                icon="users"
                title="No users yet"
                description="Invite your first teammate by email, or create an account directly."
                action={canCreate && (
                  <Button intent="primary" onClick={() => setInviteOpen(true)}>Invite user</Button>
                )}
              />
            )
          ) : (
            <DataTable columns={columns} rows={userRows} loading={tableLoading} />
          )}
        </VStack>
        )
      }
      pagination={
        !showingInvites && users?.last_page > 1 && (
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

    <Drawer
      open={inviteOpen}
      onClose={() => setInviteOpen(false)}
      title="Invite user"
      width={460}
      footer={
        <HStack gap={2} justify="end">
          <Button intent="ghost" onClick={() => setInviteOpen(false)}>Cancel</Button>
          <Button intent="primary" onClick={submitInvite} loading={inviteForm.processing}>
            Send invitation
          </Button>
        </HStack>
      }
    >
      <VStack gap={4}>
        <Text size="sm" tone="secondary">
          We'll email an invitation link. The invitee sets their own password and joins
          with the role you pick — no temporary credentials to share.
        </Text>

        <VStack gap={1}>
          <Text as="label" size="sm" weight={600} htmlFor="invite-email">Email address</Text>
          <Input
            id="invite-email"
            type="email"
            placeholder="teammate@company.com"
            value={inviteForm.data.email}
            onChange={e => inviteForm.setData('email', e.target.value)}
            onKeyDown={e => e.key === 'Enter' && submitInvite(e)}
            error={!!inviteForm.errors.email}
            autoFocus
          />
          {inviteForm.errors.email && (
            <Text size="xs" tone="danger">{inviteForm.errors.email}</Text>
          )}
        </VStack>

        <VStack gap={1}>
          <Text as="label" size="sm" weight={600} htmlFor="invite-role">Role</Text>
          <Select
            id="invite-role"
            value={inviteForm.data.roles[0] ?? ''}
            onChange={e => inviteForm.setData('roles', e.target.value ? [e.target.value] : [])}
            options={[
              { value: '', label: 'No role (assign later)' },
              ...(roles ?? []).map(r => ({ value: r.name, label: r.name })),
            ]}
          />
          <Text size="xs" tone="tertiary">Roles control what the user can access. You can change this anytime.</Text>
        </VStack>
      </VStack>
    </Drawer>
    </>
  );
}

UsersIndex.layout = page => (
  <App title="Users" railTitle="User management" rail={<UsersRail />}>{page}</App>
);
