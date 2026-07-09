import { useEffect, useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody, AreaSpark, Donut,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../../Platform/Admin/Products/products.css';
import '../../../Platform/Admin/Billing/P2/subscriptions.css';
import '../../../Platform/Admin/Users/P2/users.css';

/* ---------------- shared bits ---------------- */
const svg = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const Glyph = {
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  invite: svg(<><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 6-10 7L2 6" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  roles: svg(<><path d="M12 2 3 7l9 5 9-5-9-5Z" /><path d="M3 12l9 5 9-5" /></>),
};
const check = svg(<path d="M20 6 9 17l-5-5" />);
const initials = (n) => (n || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const roleClass = (r) => (/Super/.test(r) ? 'us-rchip--super' : /Admin/.test(r) ? 'us-rchip--admin' : '');
const STATUS_LABEL = { active: 'Active', inactive: 'Inactive', locked: 'Locked' };
const ago = (iso) => { if (!iso) return 'Never'; const h = (Date.now() - new Date(iso).getTime()) / 3.6e6; if (h < 1) return 'just now'; if (h < 24) return `${Math.round(h)}h ago`; return `${Math.round(h / 24)}d ago`; };
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const confirmDo = (msg, fn) => { if (window.confirm(msg)) fn(); };

/* ---------------- rail (command-mode context section) ---------------- */
function Rail({ stats, label }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">{label}</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Total</span><b>{s.total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Active</span><b>{s.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>With a role</span><b>{(s.total ?? 0) - (s.no_role ?? 0)}</b></div>
          <div className="pc-rail__row"><span>Administrators</span><b>{s.admins ?? 0}</b></div>
          <div className="pc-rail__row"><span>2FA enabled</span><b>{s.tfa_pct ?? 0}%</b></div>
          <div className="pc-rail__row"><span>Pending invites</span><b>{s.pending ?? 0}</b></div>
        </div>
      </div>
    </div>
  );
}

/* ---------------- editor modal ---------------- */
function EditorModal({ user, roles, routePrefix, onClose }) {
  const isNew = !user;
  const roleIdByName = useMemo(() => Object.fromEntries((roles ?? []).map((r) => [r.name, r.id])), [roles]);
  const genPw = `Aa1!${Math.random().toString(36).slice(2, 10)}`;
  const form = useForm({
    name: user?.name ?? '', email: user?.email ?? '',
    password: isNew ? genPw : '', password_confirmation: isNew ? genPw : '',
    role_ids: isNew ? [] : (user.roles ?? []).map((n) => roleIdByName[n]).filter(Boolean),
  });
  const toggleRole = (id) => form.setData('role_ids', form.data.role_ids.includes(id) ? form.data.role_ids.filter((x) => x !== id) : [...form.data.role_ids, id]);
  const submit = (e) => {
    e.preventDefault();
    const opts = { preserveScroll: true, onSuccess: onClose };
    if (isNew) form.post(route(`${routePrefix}.store`), opts);
    else form.transform((d) => { const o = { ...d }; if (!o.password) { delete o.password; delete o.password_confirmation; } return o; }).put(route(`${routePrefix}.update`, user.id), opts);
  };
  const err = (k) => form.errors[k] && <span className="pc-field__err">{form.errors[k]}</span>;
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{isNew ? 'New member' : `Edit ${user.name}`}</h2>
        <div className="pc-modal__sub">{isNew ? 'Create a member account and assign roles.' : `${user.email} · audit-logged`}</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="us-grid2">
            <div className="pc-field"><label className="pc-field__label" htmlFor="u-name">Full name</label>
              <input id="u-name" className="pc-input" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoFocus />{err('name')}</div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="u-email">Email</label>
              <input id="u-email" type="email" className="pc-input" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />{err('email')}</div>
          </div>
          <div className="pc-field"><label className="pc-field__label" htmlFor="u-pw">{isNew ? 'Temporary password' : 'New password (blank = keep)'}</label>
            <input id="u-pw" type="text" className="pc-input" value={form.data.password} onChange={(e) => { form.setData('password', e.target.value); form.setData('password_confirmation', e.target.value); }} placeholder="••••••••" />{err('password')}</div>
          <div className="us-sectitle">Roles</div>
          <div className="us-rolepick">
            {(roles ?? []).map((r) => <label key={r.id}><input type="checkbox" checked={form.data.role_ids.includes(r.id)} onChange={() => toggleRole(r.id)} /> {r.name}</label>)}
          </div>
          <div className="pc-modal__actions"><span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing || !form.data.name.trim() || !form.data.email.trim()}>{form.processing ? 'Saving…' : (isNew ? 'Create member' : 'Save changes')}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- invite modal ---------------- */
function InviteModal({ roles, routePrefix, onClose }) {
  const form = useForm({ email: '', roles: [] });
  const toggle = (id) => form.setData('roles', form.data.roles.includes(id) ? form.data.roles.filter((x) => x !== id) : [...form.data.roles, id]);
  const submit = (e) => { e.preventDefault(); form.post(route(`${routePrefix}.invitations.store`), { preserveScroll: true, onSuccess: onClose }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Invite member</h2>
        <div className="pc-modal__sub">Send an invitation email with an accept link. The invitee sets their own password.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label" htmlFor="i-email">Email</label>
            <input id="i-email" type="email" className="pc-input" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} autoFocus />
            {form.errors.email && <span className="pc-field__err">{form.errors.email}</span>}</div>
          <div className="us-sectitle">Roles</div>
          <div className="us-rolepick">{(roles ?? []).map((r) => <label key={r.id}><input type="checkbox" checked={form.data.roles.includes(r.id)} onChange={() => toggle(r.id)} /> {r.name}</label>)}</div>
          <div className="pc-modal__actions"><span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing || !form.data.email.trim()}>{form.processing ? 'Sending…' : 'Send invite'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function DetailDrawer({ user, onClose, actions }) {
  const [tab, setTab] = useState('profile');
  const ctx = useCtxMenu();
  if (!user) return null;
  const tabs = [{ id: 'profile', label: 'Profile' }, { id: 'roles', label: `Roles · ${(user.roles ?? []).length}` }];
  const moreItems = [
    ...(actions.canEdit ? [{ label: user.status === 'active' ? 'Deactivate' : 'Activate', onClick: () => actions.toggleStatus(user) }] : []),
    ...(actions.canDelete ? ['sep', { label: 'Delete member', danger: true, onClick: () => actions.remove(user) }] : []),
  ];
  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Member — ${user.name}`} tabs={tabs} activeTab={tab} onTab={setTab}
      head={
        <>
          <div className="sc-dr-top">
            <div className="us-av">{user.avatar_url ? <img src={user.avatar_url} alt="" /> : initials(user.name)}</div>
            <div><div className="sc-dr-title">{user.name}</div><div className="sc-dr-code">{user.email}</div></div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{STATUS_LABEL[user.status] ?? user.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">2FA</div><div className="v">{user.tfa ? 'On' : 'Off'}</div></div>
            <div className="sc-dr-kpi"><div className="l">Logins</div><div className="v">{user.login_count}</div></div>
          </div>
          <div className="sc-dr-acts">
            {actions.canEdit && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.edit(user)}>Edit</button>}
            {actions.canImpersonate && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.impersonate(user)}>Impersonate</button>}
            {moreItems.length > 0 && <button type="button" className="pc-btn pc-btn--sm" onClick={(e) => ctx.open(e.currentTarget, moreItems)}>More ▾</button>}
          </div>
        </>
      }
    >
      {tab === 'profile' && (
        <div>
          <div className="pc-drow"><span className="pc-drow__k">Email</span><span className="pc-drow__v">{user.email}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Roles</span><span className="pc-drow__v">{(user.roles ?? []).join(', ') || '— none'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Two-factor</span><span className="pc-drow__v">{user.tfa ? 'Enabled' : 'Not enabled'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Last login</span><span className="pc-drow__v">{ago(user.last_login_at)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Total logins</span><span className="pc-drow__v">{user.login_count}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Joined</span><span className="pc-drow__v">{fmtDate(user.created_at)}</span></div>
        </div>
      )}
      {tab === 'roles' && (
        <div>
          <div className="sc-dr-sec">Assigned roles</div>
          {(user.roles ?? []).length === 0 ? <div className="wb-empty">No roles assigned — this member has no RBAC access.</div>
            : <div className="us-roles">{user.roles.map((r) => <span key={r} className={`us-rchip ${roleClass(r)}`}>{r}</span>)}</div>}
          {actions.canEdit && <div className="qfoot" style={{ marginTop: 'var(--aeos-space-3)' }}><button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.edit(user)}>Manage roles</button></div>}
        </div>
      )}
      {ctx.element}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function UsersIndex({
  members, memberStats, roleDist, sparks, roles, invitations,
  routePrefix, hrmacNamespace, scope, capabilities,
}) {
  routePrefix = routePrefix ?? 'core.users';
  hrmacNamespace = hrmacNamespace ?? 'core.user_management';
  scope = scope ?? 'tenant';
  capabilities = capabilities ?? { impersonation: false, invitations: scope !== 'platform' };
  const toast = useToast();
  const s = memberStats ?? {};
  const sp = sparks ?? {};
  const list = useMemo(() => members ?? [], [members]);
  const invites = Array.isArray(invitations) ? invitations : (invitations?.data ?? []);

  const canCreate = useHRMAC(`${hrmacNamespace}.users.create`);
  const canEdit = useHRMAC(`${hrmacNamespace}.users.edit`);
  const canDelete = useHRMAC(`${hrmacNamespace}.users.delete`);
  const canImpersonateGate = useHRMAC(`${hrmacNamespace}.users.impersonate`);
  const canImpersonate = !!capabilities.impersonation && canImpersonateGate;
  const canInvite = !!capabilities.invitations && canCreate;

  const [editor, setEditor] = useState(undefined);
  const [inviting, setInviting] = useState(false);
  const [drawer, setDrawer] = useState(null);
  const ctx = useCtxMenu();

  const toggleStatus = (u) => router.put(route(`${routePrefix}.toggle-status`, u.id), { active: u.status !== 'active' }, { preserveScroll: true, onSuccess: () => toast.success(`${u.name} ${u.status === 'active' ? 'deactivated' : 'activated'}.`) });
  const impersonate = (u) => router.post(route(`${routePrefix}.impersonate`, u.id), {}, { onError: () => toast.error('Could not impersonate.') });
  const remove = (u) => confirmDo(`Delete ${u.name}? This cannot be undone.`, () => router.delete(route(`${routePrefix}.destroy`, u.id), { preserveScroll: true, onSuccess: () => toast.success(`${u.name} deleted.`) }));
  const resendInvite = (id) => router.post(route(`${routePrefix}.invitations.resend`, id), {}, { preserveScroll: true, onSuccess: () => toast.success('Invitation resent.') });
  const cancelInvite = (id) => confirmDo('Cancel this invitation?', () => router.delete(route(`${routePrefix}.invitations.cancel`, id), { preserveScroll: true, onSuccess: () => toast.success('Invitation cancelled.') }));
  const actions = { canEdit, canDelete, canImpersonate, edit: setEditor, toggleStatus, impersonate, remove };

  const wb = useWorkbench({
    rows: list,
    getId: (r) => r.id,
    searchText: (r) => `${r.name} ${r.email} ${(r.roles ?? []).join(' ')}`,
    views: [
      { id: 'all', label: 'All members' },
      { id: 'active', label: 'Active', test: (r) => r.status === 'active' },
      { id: 'admins', label: 'Admins', test: (r) => (r.roles ?? []).some((x) => /Admin/.test(x)) },
      { id: 'norole', label: 'No role', test: (r) => (r.roles ?? []).length === 0 },
      { id: 'inactive', label: 'Inactive', test: (r) => r.status === 'inactive' },
    ],
    facets: {
      role: { value: 'all', test: (r, v) => (v === '__none' ? (r.roles ?? []).length === 0 : (r.roles ?? []).includes(v)) },
      status: { value: 'all', test: (r, v) => r.status === v },
    },
    sortKey: 'name',
    sortVal: (r, k) => (k === 'login_count' ? (r[k] ?? 0) : String(r[k] ?? '')),
    perPage: 20,
    storageKey: `users.${scope}`,
  });

  // Command-mode context rail triggers create/invite via window events so the
  // rail stays decoupled from this page's local state.
  useEffect(() => {
    const create = () => setEditor(null);
    const invite = () => setInviting(true);
    window.addEventListener('aeos:open-create-user', create);
    window.addEventListener('aeos:open-invite', invite);
    return () => { window.removeEventListener('aeos:open-create-user', create); window.removeEventListener('aeos:open-invite', invite); };
  }, []);

  const kpis = [
    { label: 'Members', value: s.total ?? 0, delta: `${s.active ?? 0} active · ${s.inactive ?? 0} inactive`, spark: sp.members, color: 'var(--aeos-primary)' },
    { label: 'With a role', value: (s.total ?? 0) - (s.no_role ?? 0), delta: `${s.no_role ?? 0} unassigned`, warn: (s.no_role ?? 0) > 0, color: 'var(--aeos-success)' },
    { label: 'Administrators', value: s.admins ?? 0, delta: 'elevated access', color: 'var(--aeos-primary)' },
    { label: '2FA enabled', value: `${s.tfa_pct ?? 0}%`, delta: `${s.tfa_on ?? 0} of ${s.total ?? 0}`, warn: (s.tfa_pct ?? 0) < 50, spark: sp.members, color: 'var(--aeos-success)' },
    { label: 'Pending invites', value: s.pending ?? 0, delta: 'awaiting acceptance', color: 'var(--aeos-warning)' },
    { label: 'Roles', value: (roles ?? []).length, delta: 'assignable', color: 'var(--aeos-primary)' },
  ];

  const dist = roleDist ?? [];
  const distMax = Math.max(1, ...dist.map((d) => d.count), s.no_role ?? 0);
  const recent = useMemo(() => [...list].filter((u) => u.last_login_at).sort((a, b) => new Date(b.last_login_at) - new Date(a.last_login_at)).slice(0, 5), [list]);
  const attn = useMemo(() => list.filter((u) => u.status !== 'active' || (u.roles ?? []).length === 0).slice(0, 300), [list]);
  const admins = useMemo(() => list.filter((u) => (u.roles ?? []).some((x) => /Admin/.test(x))), [list]);

  const rowMenu = (u) => [
    ...(canEdit ? [{ label: 'Edit member', onClick: () => setEditor(u) }] : []),
    ...(canImpersonate ? [{ label: 'Impersonate', onClick: () => impersonate(u) }] : []),
    ...(canEdit ? ['sep', { label: u.status === 'active' ? 'Deactivate' : 'Activate', onClick: () => toggleStatus(u) }] : []),
    ...(canDelete ? [{ label: 'Delete member', danger: true, onClick: () => remove(u) }] : []),
  ];

  const columns = [
    { key: 'name', label: 'Member', sortable: true, render: (u) => (
      <div className="pc-mrow"><div className="us-av">{u.avatar_url ? <img src={u.avatar_url} alt="" /> : initials(u.name)}</div>
        <div><div className="us-uname">{u.name}</div><div className="us-email">{u.email}</div></div></div>
    ) },
    { key: 'roles', label: 'Roles', render: (u) => ((u.roles ?? []).length ? <div className="us-roles">{u.roles.map((r) => <span key={r} className={`us-rchip ${roleClass(r)}`}>{r}</span>)}</div> : <span className="pc-free">—</span>) },
    { key: 'tfa', label: '2FA', hideSm: true, render: (u) => (u.tfa ? <span className="us-tfa us-tfa--on">🔒 On</span> : <span className="us-tfa us-tfa--off">Off</span>) },
    { key: 'status', label: 'Status', render: (u) => <span className={`pc-chip pc-chip--${u.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[u.status] ?? u.status}</span> },
    { key: 'last_login_at', label: 'Last login', hideSm: true, render: (u) => <span className="sc-kind">{ago(u.last_login_at)}</span> },
    { key: 'created_at', label: 'Joined', align: 'r', hideSm: true, render: (u) => <span className="sc-kind">{fmtDate(u.created_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (u) => <button type="button" className="wb-kebab" aria-label={`Actions for ${u.name}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(u))}>⋯</button> },
  ];

  const bulkIds = () => wb.selectedRows.map((r) => r.id);
  const bulkToggle = (active) => router.post(route(`${routePrefix}.bulk.toggle-status`), { user_ids: bulkIds(), active }, { preserveScroll: true });
  const canBulkAssign = canEdit && (() => { try { return route().has(`${routePrefix}.bulk.assign-roles`); } catch { return false; } })();
  const bulkAssign = (roleId) => { if (!roleId) return; router.post(route(`${routePrefix}.bulk.assign-roles`), { user_ids: bulkIds(), roles: [Number(roleId)] }, { preserveScroll: true, onSuccess: () => toast.success('Role assigned to selection.') }); };

  return (
    <div className="pc us">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> {scope === 'platform' ? 'Platform' : 'Workspace'} · Access &amp; Security</div>
          <h1 className="pc-title">Users</h1>
          <div className="pc-sub">Every {scope === 'platform' ? 'platform staff' : 'workspace'} member — roles, status and login activity in one operating view, with invite, create, edit, role assignment, activate/deactivate, impersonate and delete.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => window.print()}>{Glyph.export}<span>Export</span></button>
          {canInvite && <button type="button" className="pc-btn" onClick={() => setInviting(true)}>{Glyph.invite}<span>Invite</span></button>}
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setEditor(null)}>{Glyph.plus}<span>New member</span></button>}
        </div>
      </div>

      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody><div className="pc-kpi">
            <div className="pc-kpi__label">{c.label}</div>
            <div className="pc-kpi__value">{c.value}</div>
            <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}${c.warn ? ' pc-kpi__delta--warn' : ''}`}>{c.delta}</div>
            {Array.isArray(c.spark) && c.spark.length > 1 && <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color ?? 'var(--aeos-primary)'} /></div>}
          </div></CardBody></Card>
        ))}
      </div>

      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Role distribution</h2><div className="pc-panel-h__sub">Members per role — click to filter</div></div><span className="sc-badge sc-badge--ok">{(roles ?? []).length} roles</span></div>
          <div className="us-mix">
            {dist.map((d, i) => (
              <button key={d.role} type="button" className={`us-mixrow${wb.facetValues.role === d.role ? ' is-on' : ''}`} onClick={() => wb.setFacet('role', wb.facetValues.role === d.role ? 'all' : d.role)}>
                <span className="us-mixrow__cap">{d.role}</span>
                <span className="us-mixrow__track"><span className={`us-mixrow__fill us-mix-${i % 5}`} style={{ width: `${Math.max(d.count / distMax * 100, 4)}%` }} /></span>
                <span className="us-mixrow__n"><b>{d.count}</b></span>
              </button>
            ))}
            {(s.no_role ?? 0) > 0 && (
              <button type="button" className={`us-mixrow${wb.facetValues.role === '__none' ? ' is-on' : ''}`} onClick={() => wb.setFacet('role', wb.facetValues.role === '__none' ? 'all' : '__none')}>
                <span className="us-mixrow__cap">No role</span>
                <span className="us-mixrow__track"><span className="us-mixrow__fill us-mix-4" style={{ width: `${Math.max((s.no_role ?? 0) / distMax * 100, 4)}%` }} /></span>
                <span className="us-mixrow__n"><b>{s.no_role}</b></span>
              </button>
            )}
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">2FA coverage</h2><div className="pc-panel-h__sub">Two-factor posture</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={[{ color: 'var(--aeos-success)', value: s.tfa_on ?? 0 }, { color: 'var(--aeos-warning)', value: (s.total ?? 0) - (s.tfa_on ?? 0) }]} centerValue={`${s.tfa_pct ?? 0}%`} centerLabel="2FA on" size={112} />
            <div className="sc-dl">
              <span className="li"><span className="d" style={{ background: 'var(--aeos-success)' }} />Enabled<b>{s.tfa_on ?? 0}</b></span>
              <span className="li"><span className="d" style={{ background: 'var(--aeos-warning)' }} />Not enabled<b>{(s.total ?? 0) - (s.tfa_on ?? 0)}</b></span>
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Recent logins</h2><div className="pc-panel-h__sub">Most recently active</div></div></div>
          {recent.length === 0 && <div className="wb-empty">No logins recorded yet.</div>}
          {recent.map((u) => (
            <div key={u.id} className="sc-qitem"><div className="us-av us-av--sm">{initials(u.name)}</div>
              <div className="sc-qitem__who"><b>{u.name}</b><span>{(u.roles ?? [])[0] ?? '—'}</span></div>
              <span className="sc-qitem__when">{ago(u.last_login_at)}</span></div>
          ))}
        </CardBody></Card>
      </div>

      <div className="sc-queues">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Needs attention</h2><div className="pc-panel-h__sub">Inactive or no role</div></div><span className={`sc-badge ${attn.length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{attn.length}</span></div>
          {attn.length === 0 && <div className="wb-empty">Everyone is active with a role.</div>}
          {attn.slice(0, 4).map((u) => (
            <div key={u.id} className="sc-qitem"><div className="us-av us-av--sm">{initials(u.name)}</div>
              <div className="sc-qitem__who"><b>{u.name}</b><span>{u.email}</span></div>
              {u.status !== 'active' ? <span className="pc-chip pc-chip--inactive"><span className="pc-chip__dot" />Inactive</span> : <span className="sc-badge sc-badge--warn">No role</span>}
              {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setEditor(u)}>Fix</button>}
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('norole')}>Open no-role view →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Pending invites</h2><div className="pc-panel-h__sub">Awaiting acceptance</div></div><span className={`sc-badge ${invites.length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{invites.length}</span></div>
          {invites.length === 0 && <div className="wb-empty">No pending invitations.{canInvite ? ' Invite a member to get started.' : ''}</div>}
          {invites.slice(0, 4).map((iv) => (
            <div key={iv.id} className="sc-qitem"><div className="us-av us-av--sm" style={{ background: 'var(--aeos-bg-subtle)', color: 'var(--aeos-text-muted)', borderColor: 'var(--aeos-border)' }}>✉</div>
              <div className="sc-qitem__who"><b>{iv.email}</b><span>{(iv.roles ?? []).join(', ') || 'no role'}</span></div>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => resendInvite(iv.id)}>Resend</button>
              <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => cancelInvite(iv.id)}>✕</button>
            </div>
          ))}
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Administrators</h2><div className="pc-panel-h__sub">Elevated access</div></div><span className="sc-badge sc-badge--ok">{admins.length}</span></div>
          {admins.length === 0 && <div className="wb-empty">No administrators.</div>}
          {admins.slice(0, 4).map((u) => (
            <div key={u.id} className="sc-qitem"><div className="us-av us-av--sm">{initials(u.name)}</div>
              <div className="sc-qitem__who"><b>{u.name}</b><span>{u.email}</span></div>
              <span className={`us-rchip ${roleClass((u.roles ?? [])[0])}`}>{(u.roles ?? [])[0]}</span></div>
          ))}
        </CardBody></Card>
      </div>

      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search name or email…" ariaLabel="Search members" />
          <select className="pc-input sc-statusfilter" value={wb.facetValues.role} onChange={(e) => wb.setFacet('role', e.target.value)} aria-label="Role filter">
            <option value="all">All roles</option>
            {(roles ?? []).map((r) => <option key={r.id} value={r.name}>{r.name}</option>)}
            <option value="__none">No role</option>
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
            <option value="all">All statuses</option>
            {['active', 'inactive', 'locked'].map((k) => <option key={k} value={k}>{STATUS_LABEL[k]}</option>)}
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>
        <WbViews wb={wb} />
        <WbBulkBar wb={wb}>
          {canBulkAssign && (
            <select className="pc-input pc-input--sm" defaultValue="" onChange={(e) => { bulkAssign(e.target.value); e.target.value = ''; }} aria-label="Assign role to selection">
              <option value="" disabled>Assign role…</option>
              {(roles ?? []).map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
            </select>
          )}
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkToggle(true)}>Activate</button>}
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkToggle(false)}>Deactivate</button>}
          {canDelete && <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => confirmDo(`Delete ${wb.selection.size} selected member(s)?`, () => router.post(route(`${routePrefix}.bulk.delete`), { user_ids: bulkIds() }, { preserveScroll: true }))}>Delete</button>}
        </WbBulkBar>
        <WbTable wb={wb} columns={columns} selectable onRowClick={(u) => setDrawer(u)}
          rowAriaLabel={(u) => `${u.name}, ${STATUS_LABEL[u.status] ?? u.status}`}
          empty={<>No members match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>}
        />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {ctx.element}
      {drawer && <DetailDrawer user={list.find((u) => u.id === drawer.id) ?? drawer} onClose={() => setDrawer(null)}
        actions={{ ...actions, edit: (u) => { setDrawer(null); setEditor(u); }, remove: (u) => { setDrawer(null); remove(u); } }} />}
      {editor !== undefined && <EditorModal user={editor} roles={roles} routePrefix={routePrefix} onClose={() => setEditor(undefined)} />}
      {inviting && <InviteModal roles={roles} routePrefix={routePrefix} onClose={() => setInviting(false)} />}
    </div>
  );
}

UsersIndex.layout = (page) => (
  <App title="Users" railTitle="Members" rail={<Rail stats={page.props.memberStats} label={page.props.scope === 'platform' ? 'Staff' : 'Members'} />}>
    {page}
  </App>
);
