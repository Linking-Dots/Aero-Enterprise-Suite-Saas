import { useEffect, useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaSpark, Donut,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './users.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  invite: svg(<><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 6-10 7L2 6" /></>),
  roles: svg(<><path d="M12 2 3 7l9 5 9-5-9-5Z" /><path d="M3 12l9 5 9-5" /></>),
};
const check = svg(<path d="M20 6 9 17l-5-5" />);
const deviceIco = svg(<><rect x="2" y="4" width="20" height="12" rx="2" /><path d="M8 20h8M12 16v4" /></>);

const initials = (n) => (n || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const roleClass = (r) => (/Super/.test(r) ? 'us-rchip--super' : /Admin/.test(r) ? 'us-rchip--admin' : '');
const STATUS_LABEL = { active: 'Active', inactive: 'Inactive', locked: 'Locked' };
const ago = (iso) => {
  if (!iso) return 'Never';
  const h = (Date.now() - new Date(iso).getTime()) / 3.6e6;
  if (h < 1) return 'just now';
  if (h < 24) return `${Math.round(h)}h ago`;
  return `${Math.round(h / 24)}d ago`;
};
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };

const post = (url, data = {}, opts = {}) => router.post(url, data, { preserveScroll: true, ...opts });
const confirmPost = (msg, url, data = {}) => { if (window.confirm(msg)) post(url, data); };

/* role → descriptive capability lines (UI copy; the ROLE assignment itself is the real access grant) */
const ROLE_CAPS = {
  'Super Platform Admin': ['Full platform control', 'Manage all tenants', 'Manage staff & roles', 'Billing, plans & secrets'],
  'Platform Admin': ['Manage tenants', 'Onboarding & provisioning', 'View billing', 'View audit logs'],
  'Support Admin': ['View tenants', 'Impersonate tenants', 'Support tickets'],
  'Billing Manager': ['Manage plans & invoices', 'Process refunds', 'View subscriptions'],
  Auditor: ['Read-only everything', 'Export reports', 'View audit logs'],
};

/* ---------------- rail ---------------- */
function UsersRail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Staff</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Total staff</span><b>{s.total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Active</span><b>{s.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>2FA coverage</span><b>{s.tfa_pct ?? 0}%</b></div>
          <div className="pc-rail__row"><span>Administrators</span><b>{s.admins ?? 0}</b></div>
          <div className="pc-rail__row"><span>Needs attention</span><b>{s.needs_attention ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/roles')}>{Glyph.roles}<span>Roles &amp; Access</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- editor modal (create / edit) ---------------- */
function EditorModal({ user, roles, onClose }) {
  const isNew = !user;
  const roleIdByName = useMemo(() => Object.fromEntries((roles ?? []).map((r) => [r.name, r.id])), [roles]);
  const initialRoleIds = isNew ? [] : (user.roles ?? []).map((n) => roleIdByName[n]).filter(Boolean);
  const genPw = `Aa1!${Math.random().toString(36).slice(2, 10)}`;

  const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
    password: isNew ? genPw : '',
    password_confirmation: isNew ? genPw : '',
    role_ids: initialRoleIds,
  });

  const toggleRole = (id) => form.setData('role_ids', form.data.role_ids.includes(id) ? form.data.role_ids.filter((x) => x !== id) : [...form.data.role_ids, id]);

  const submit = (e) => {
    e.preventDefault();
    const opts = { preserveScroll: true, onSuccess: onClose };
    if (isNew) form.post('/users', opts);
    else form.transform((d) => { const o = { ...d }; if (!o.password) { delete o.password; delete o.password_confirmation; } return o; }).put(`/users/${user.id}`, opts);
  };
  const err = (k) => form.errors[k] && <span className="pc-field__err">{form.errors[k]}</span>;

  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{isNew ? 'New staff user' : `Edit ${user.name}`}</h2>
        <div className="pc-modal__sub">{isNew ? 'Create a platform staff account and assign roles.' : `${user.email} · audit-logged`}</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="us-grid2">
            <div className="pc-field"><label className="pc-field__label" htmlFor="u-name">Full name</label>
              <input id="u-name" className="pc-input" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="e.g. Sarah Chen" autoFocus />{err('name')}</div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="u-email">Email</label>
              <input id="u-email" type="email" className="pc-input" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} placeholder="name@aeos365.com" />{err('email')}</div>
          </div>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="u-pw">{isNew ? 'Temporary password' : 'New password (leave blank to keep)'}</label>
            <input id="u-pw" type="text" className="pc-input" value={form.data.password} onChange={(e) => { form.setData('password', e.target.value); form.setData('password_confirmation', e.target.value); }} placeholder="••••••••" />{err('password')}
          </div>
          <div className="us-sectitle">Roles</div>
          <div className="us-rolepick">
            {(roles ?? []).map((r) => (
              <label key={r.id}><input type="checkbox" checked={form.data.role_ids.includes(r.id)} onChange={() => toggleRole(r.id)} /> {r.name}</label>
            ))}
          </div>
          {err('role_ids')}
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing || !form.data.name.trim() || !form.data.email.trim()}>{form.processing ? 'Saving…' : (isNew ? 'Create user' : 'Save changes')}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- invite modal ---------------- */
function InviteModal({ roles, onClose }) {
  const form = useForm({ email: '', roles: [] });
  const toggle = (id) => form.setData('roles', form.data.roles.includes(id) ? form.data.roles.filter((x) => x !== id) : [...form.data.roles, id]);
  const submit = (e) => { e.preventDefault(); form.post('/users/invite', { preserveScroll: true, onSuccess: onClose }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Invite staff</h2>
        <div className="pc-modal__sub">Send an invitation email with an accept link. The recipient sets their own password.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label" htmlFor="i-email">Email</label>
            <input id="i-email" type="email" className="pc-input" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} placeholder="name@aeos365.com" autoFocus />
            {form.errors.email && <span className="pc-field__err">{form.errors.email}</span>}</div>
          <div className="us-sectitle">Roles</div>
          <div className="us-rolepick">
            {(roles ?? []).map((r) => <label key={r.id}><input type="checkbox" checked={form.data.roles.includes(r.id)} onChange={() => toggle(r.id)} /> {r.name}</label>)}
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing || !form.data.email.trim()}>{form.processing ? 'Sending…' : 'Send invite'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function DetailDrawer({ user, initialTab, onClose, actions }) {
  const [tab, setTab] = useState(initialTab || 'profile');
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  const ctx = useCtxMenu();

  useEffect(() => {
    setTab(initialTab || 'profile'); setDetail(null); setFailed(false);
    if (!user) return undefined;
    const ac = new AbortController();
    fetch(`/users/${user.id}/detail`, { headers: { Accept: 'application/json' }, signal: ac.signal })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
      .then(setDetail)
      .catch((e) => { if (e.name !== 'AbortError') setFailed(true); });
    return () => ac.abort();
  }, [user?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!user) return null;
  const perms = [...new Set((user.roles ?? []).flatMap((r) => ROLE_CAPS[r] ?? []))];
  const locked = user.status === 'locked';
  const tabs = [
    { id: 'profile', label: 'Profile' },
    { id: 'roles', label: 'Roles & permissions' },
    { id: 'sessions', label: `Sessions${detail ? ` · ${detail.sessions.length}` : ''}` },
    { id: 'activity', label: 'Activity' },
  ];
  const moreItems = [
    ...(actions.canEdit ? [{ label: user.status === 'active' ? 'Deactivate' : 'Activate', onClick: () => actions.toggleStatus(user) }] : []),
    ...(actions.canEdit ? [{ label: locked ? 'Unlock account' : 'Lock account', onClick: () => actions.lock(user) }] : []),
    ...(actions.canEdit ? [{ label: 'Force password reset', onClick: () => actions.forceReset(user) }, { label: 'Revoke all sessions', onClick: () => actions.revoke(user) }, { label: user.tfa ? 'Reset 2FA' : 'Require 2FA', onClick: () => actions.reset2fa(user) }] : []),
    ...(actions.canDelete ? ['sep', { label: 'Delete user', danger: true, onClick: () => actions.remove(user) }] : []),
  ];

  return (
    <WbDrawer open onClose={onClose} ariaLabel={`User detail — ${user.name}`} tabs={tabs} activeTab={tab} onTab={setTab}
      head={
        <>
          <div className="sc-dr-top">
            <div className="us-av">{user.avatar_url ? <img src={user.avatar_url} alt="" /> : initials(user.name)}</div>
            <div>
              <div className="sc-dr-title">{user.name}</div>
              <div className="sc-dr-code">{user.email}</div>
            </div>
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
          <div className="pc-drow"><span className="pc-drow__k">Roles</span><span className="pc-drow__v">{(user.roles ?? []).join(', ') || '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Two-factor</span><span className="pc-drow__v">{user.tfa ? 'Enabled' : 'Not enabled'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Last login</span><span className="pc-drow__v">{ago(user.last_login_at)}{user.last_login_ip ? ` · ${user.last_login_ip}` : ''}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Total logins</span><span className="pc-drow__v">{user.login_count}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Password reset</span><span className="pc-drow__v">{user.force_reset ? 'Required next sign-in' : 'Not required'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Joined</span><span className="pc-drow__v">{fmtDate(user.created_at)}</span></div>
          {user.locked_reason && <div className="pc-callout-danger" style={{ marginTop: 'var(--aeos-space-3)', fontSize: 'var(--aeos-text-xs)', color: 'var(--aeos-danger)', background: 'color-mix(in srgb, var(--aeos-danger) 8%, transparent)', border: '1px solid color-mix(in srgb, var(--aeos-danger) 28%, transparent)', borderRadius: '9px', padding: '10px 12px' }}>Locked — {user.locked_reason}</div>}
        </div>
      )}
      {tab === 'roles' && (
        <div>
          <div className="sc-dr-sec">Assigned roles</div>
          <div className="us-roles" style={{ marginBottom: 'var(--aeos-space-2)' }}>{(user.roles ?? []).map((r) => <span key={r} className={`us-rchip ${roleClass(r)}`}>{r}</span>)}</div>
          <div className="sc-dr-sec">Effective access ({perms.length})</div>
          {perms.length === 0 ? <div className="wb-empty">No roles assigned.</div> : perms.map((p, i) => <div className="us-perm" key={i}>{check}{p}</div>)}
        </div>
      )}
      {tab === 'sessions' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load sessions.' : 'Loading…'}</div>
          : detail.sessions.length === 0 ? <div className="wb-empty">No active sessions.</div>
            : (
              <>
                {detail.sessions.map((sn, i) => (
                  <div className="us-sess" key={i}>
                    <div className="us-sess__ico">{deviceIco}</div>
                    <div className="us-sess__m"><b>{sn.agent}</b><span>{sn.ip} · {ago(sn.last_active)}</span></div>
                  </div>
                ))}
                {actions.canEdit && <div className="qfoot" style={{ marginTop: 'var(--aeos-space-3)' }}><button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => actions.revoke(user)}>Revoke all sessions</button></div>}
              </>
            )
      )}
      {tab === 'activity' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load activity.' : 'Loading…'}</div>
          : detail.activity.length === 0 ? <div className="wb-empty">No recorded activity yet.</div>
            : (
              <ul className="sc-tl">
                {detail.activity.map((a, i) => <li key={i}>{a.detail || a.event}<span className="when">{fmtDate(a.at)}{a.actor ? ` · ${a.actor}` : ''}</span></li>)}
              </ul>
            )
      )}
      {ctx.element}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Index({ stats, users, roleDist, sparks, roles }) {
  const toast = useToast();
  const s = stats ?? {};
  const sp = sparks ?? {};
  const list = useMemo(() => users ?? [], [users]);

  const canCreate = useHRMAC('auth.user_management.users.create');
  const canEdit = useHRMAC('auth.user_management.users.edit');
  const canDelete = useHRMAC('auth.user_management.users.delete');
  const canImpersonate = useHRMAC('auth.user_management.users.impersonate');

  const [editor, setEditor] = useState(undefined); // undefined=closed, null=new, obj=edit
  const [inviting, setInviting] = useState(false);
  const [drawer, setDrawer] = useState(null);
  const ctx = useCtxMenu();

  const toggleStatus = (u) => router.patch(`/users/${u.id}/toggle-status`, {}, { preserveScroll: true, onSuccess: () => toast.success(`${u.name} ${u.status === 'active' ? 'deactivated' : 'activated'}.`) });
  const lock = (u) => post(`/users/${u.id}/lock`, {}, { onSuccess: () => toast.success(`${u.name} ${u.status === 'locked' ? 'unlocked' : 'locked'}.`) });
  const forceReset = (u) => post(`/users/${u.id}/force-reset`, {}, { onSuccess: () => toast.success(`Password reset required for ${u.name}.`) });
  const revoke = (u) => confirmPost(`Revoke all sessions for ${u.name}? They will be signed out everywhere.`, `/users/${u.id}/revoke-sessions`);
  const reset2fa = (u) => confirmPost(`${u.tfa ? 'Reset' : 'Require'} two-factor for ${u.name}?`, `/users/${u.id}/reset-2fa`);
  const impersonate = (u) => post(`/users/${u.id}/impersonate`, {}, { onError: () => toast.error('Could not impersonate.') });
  const remove = (u) => {
    if (!window.confirm(`Delete ${u.name}? This cannot be undone.`)) return;
    router.delete(`/users/${u.id}`, { preserveScroll: true, onSuccess: () => toast.success(`${u.name} deleted.`), onError: () => toast.error('Could not delete user.') });
  };
  const actions = { canEdit, canDelete, canImpersonate, edit: setEditor, toggleStatus, lock, forceReset, revoke, reset2fa, impersonate, remove };

  const wb = useWorkbench({
    rows: list,
    getId: (r) => r.id,
    searchText: (r) => `${r.name} ${r.email} ${(r.roles ?? []).join(' ')}`,
    views: [
      { id: 'all', label: 'All users' },
      { id: 'active', label: 'Active', test: (r) => r.status === 'active' },
      { id: 'admins', label: 'Admins', test: (r) => (r.roles ?? []).some((x) => /Super Platform Admin|Platform Admin/.test(x)) },
      { id: 'no2fa', label: 'No 2FA', test: (r) => !r.tfa },
      { id: 'locked', label: 'Locked', test: (r) => r.status === 'locked' },
      { id: 'inactive', label: 'Inactive', test: (r) => r.status === 'inactive' },
    ],
    facets: {
      role: { value: 'all', test: (r, v) => (r.roles ?? []).includes(v) },
      status: { value: 'all', test: (r, v) => r.status === v },
      tfa: { value: 'all', test: (r, v) => (v === 'on' ? r.tfa : !r.tfa) },
    },
    sortKey: 'name',
    sortVal: (r, k) => (k === 'login_count' ? (r[k] ?? 0) : k === 'last_login_at' ? String(r[k] ?? '') : String(r[k] ?? '')),
    perPage: 20,
    storageKey: 'platform.users',
  });

  const kpis = [
    { label: 'Total staff', value: s.total ?? 0, delta: `${s.active ?? 0} active · ${s.inactive ?? 0} inactive`, spark: sp.staff, color: 'var(--aeos-primary)' },
    { label: 'Active', value: s.active ?? 0, delta: `${s.locked ?? 0} locked`, up: true, spark: sp.staff, color: 'var(--aeos-success)' },
    { label: '2FA enabled', value: `${s.tfa_pct ?? 0}%`, delta: `${s.tfa_on ?? 0} of ${s.total ?? 0} staff`, warn: (s.tfa_pct ?? 0) < 80, spark: sp.tfa, color: 'var(--aeos-success)' },
    { label: 'Needs attention', value: s.needs_attention ?? 0, delta: 'no 2FA / locked / new', warn: (s.needs_attention ?? 0) > 0, color: 'var(--aeos-warning)' },
    { label: 'Roles', value: s.roles ?? 0, delta: `${s.admins ?? 0} administrators`, color: 'var(--aeos-primary)' },
    { label: 'Logins', value: (s.logins ?? 0).toLocaleString(), delta: 'all-time across staff', up: true, spark: sp.staff, color: 'var(--aeos-success)' },
  ];

  const dist = roleDist ?? [];
  const distMax = Math.max(1, ...dist.map((d) => d.count));
  const recent = useMemo(() => [...list].filter((u) => u.last_login_at).sort((a, b) => new Date(b.last_login_at) - new Date(a.last_login_at)).slice(0, 5), [list]);
  const attn = useMemo(() => list.filter((u) => !u.tfa || u.status !== 'active' || !u.last_login_at), [list]);
  const admins = useMemo(() => list.filter((u) => (u.roles ?? []).some((x) => /Super Platform Admin|Platform Admin/.test(x))), [list]);

  const rowMenu = (u) => {
    const locked = u.status === 'locked';
    return [
      ...(canEdit ? [{ label: 'Edit user', onClick: () => setEditor(u) }] : []),
      ...(canImpersonate ? [{ label: 'Impersonate', onClick: () => impersonate(u) }] : []),
      ...(canEdit ? ['sep', { label: u.status === 'active' ? 'Deactivate' : 'Activate', onClick: () => toggleStatus(u) }, { label: locked ? 'Unlock account' : 'Lock account', onClick: () => lock(u) }] : []),
      ...(canEdit ? [{ label: 'Force password reset', onClick: () => forceReset(u) }, { label: 'Revoke sessions', onClick: () => revoke(u) }, { label: u.tfa ? 'Reset 2FA' : 'Require 2FA', onClick: () => reset2fa(u) }] : []),
      ...(canDelete ? ['sep', { label: 'Delete user', danger: true, onClick: () => remove(u) }] : []),
    ];
  };

  const columns = [
    {
      key: 'name', label: 'User', sortable: true,
      render: (u) => (
        <div className="pc-mrow">
          <div className="us-av">{u.avatar_url ? <img src={u.avatar_url} alt="" /> : initials(u.name)}</div>
          <div><div className="us-uname">{u.name}</div><div className="us-email">{u.email}</div></div>
        </div>
      ),
    },
    { key: 'roles', label: 'Roles', render: (u) => <div className="us-roles">{(u.roles ?? []).map((r) => <span key={r} className={`us-rchip ${roleClass(r)}`}>{r}</span>)}</div> },
    { key: 'tfa', label: '2FA', hideSm: true, render: (u) => (u.tfa ? <span className="us-tfa us-tfa--on">🔒 On</span> : <span className="us-tfa us-tfa--off">Off</span>) },
    { key: 'status', label: 'Status', render: (u) => <span className={`pc-chip pc-chip--${u.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[u.status] ?? u.status}</span> },
    { key: 'last_login_at', label: 'Last login', hideSm: true, sortable: true, render: (u) => <span className="sc-kind">{ago(u.last_login_at)}</span> },
    { key: 'login_count', label: 'Logins', align: 'r', hideSm: true, sortable: true, render: (u) => <span className="us-logins">{u.login_count}</span> },
    { key: 'created_at', label: 'Joined', align: 'r', hideSm: true, render: (u) => <span className="sc-kind">{fmtDate(u.created_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (u) => <button type="button" className="wb-kebab" aria-label={`Actions for ${u.name}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(u))}>⋯</button> },
  ];

  const bulkIds = () => wb.selectedRows.map((r) => r.id);
  const exportSelectedCsv = () => {
    const header = 'name,email,roles,status,2fa,last_login,logins,joined';
    const lines = wb.selectedRows.map((r) => [r.name, r.email, (r.roles ?? []).join(' | '), r.status, r.tfa ? 'on' : 'off', r.last_login_at ?? '', r.login_count, r.created_at ?? ''].map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `platform-users-${new Date().toISOString().slice(0, 10)}.csv`; a.click(); URL.revokeObjectURL(a.href);
  };
  const bulkToggle = (activeState) => post('/users/bulk/toggle-status', { user_ids: bulkIds(), active: activeState });

  return (
    <div className="pc us">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Access &amp; Security</div>
          <h1 className="pc-title">Users</h1>
          <div className="pc-sub">Every platform staff account — roles, 2FA posture, sessions and login activity in one operating view, with invite, edit, role assignment, lock, impersonate, force-reset, session revoke and 2FA reset.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={(e) => ctx.open(e.currentTarget, [
            { label: 'Export CSV — all staff', onClick: () => { window.location.href = '/users/export'; } },
            { label: 'Print this view', onClick: () => window.print() },
          ])}>{Glyph.export}<span>Export</span></button>
          {canCreate && <button type="button" className="pc-btn" onClick={() => setInviting(true)}>{Glyph.invite}<span>Invite</span></button>}
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setEditor(null)}>{Glyph.plus}<span>New user</span></button>}
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}${c.warn ? ' pc-kpi__delta--warn' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color ?? 'var(--aeos-primary)'} /></div>}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Role distribution</h2><div className="pc-panel-h__sub">Staff per role — click to filter</div></div><span className="sc-badge sc-badge--ok">{s.roles ?? 0} roles</span></div>
          <div className="us-mix">
            {dist.map((d, i) => (
              <button key={d.role} type="button" className={`us-mixrow${wb.facetValues.role === d.role ? ' is-on' : ''}`} onClick={() => wb.setFacet('role', wb.facetValues.role === d.role ? 'all' : d.role)}>
                <span className="us-mixrow__cap">{d.role}</span>
                <span className="us-mixrow__track"><span className={`us-mixrow__fill us-mix-${i % 5}`} style={{ width: `${Math.max(d.count / distMax * 100, d.count ? 6 : 0)}%` }} /></span>
                <span className="us-mixrow__n"><b>{d.count}</b></span>
              </button>
            ))}
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">2FA coverage</h2><div className="pc-panel-h__sub">Two-factor posture — click to filter</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={[{ color: 'var(--aeos-success)', value: s.tfa_on ?? 0 }, { color: 'var(--aeos-warning)', value: (s.total ?? 0) - (s.tfa_on ?? 0) }]} centerValue={`${s.tfa_pct ?? 0}%`} centerLabel="2FA on" size={112} />
            <div className="sc-dl">
              <button type="button" className="li" onClick={() => wb.setFacet('tfa', wb.facetValues.tfa === 'on' ? 'all' : 'on')}><span className="d" style={{ background: 'var(--aeos-success)' }} />Enabled<b>{s.tfa_on ?? 0}</b></button>
              <button type="button" className="li" onClick={() => wb.setFacet('tfa', wb.facetValues.tfa === 'off' ? 'all' : 'off')}><span className="d" style={{ background: 'var(--aeos-warning)' }} />Not enabled<b>{(s.total ?? 0) - (s.tfa_on ?? 0)}</b></button>
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Recent logins</h2><div className="pc-panel-h__sub">Most recently active staff</div></div></div>
          {recent.length === 0 && <div className="wb-empty">No logins recorded yet.</div>}
          {recent.map((u) => (
            <div key={u.id} className="sc-qitem">
              <div className="us-av us-av--sm">{initials(u.name)}</div>
              <div className="sc-qitem__who"><b>{u.name}</b><span>{(u.roles ?? [])[0] ?? '—'}</span></div>
              <span className="sc-qitem__when">{ago(u.last_login_at)}</span>
            </div>
          ))}
        </CardBody></Card>
      </div>

      {/* queues */}
      <div className="sc-queues">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Needs attention</h2><div className="pc-panel-h__sub">No 2FA · locked · never logged in</div></div><span className={`sc-badge ${attn.length ? 'sc-badge--bad' : 'sc-badge--ok'}`}>{attn.length}</span></div>
          {attn.length === 0 && <div className="wb-empty">Everyone is secure and active.</div>}
          {attn.slice(0, 4).map((u) => (
            <div key={u.id} className="sc-qitem">
              <div className="us-av us-av--sm">{initials(u.name)}</div>
              <div className="sc-qitem__who"><b>{u.name}</b><span>{(u.roles ?? [])[0] ?? '—'}</span></div>
              {u.status === 'locked' ? <span className="pc-chip pc-chip--locked"><span className="pc-chip__dot" />Locked</span>
                : !u.tfa ? <span className="us-tfa us-tfa--off">No 2FA</span>
                  : !u.last_login_at ? <span className="sc-badge sc-badge--warn">New</span>
                    : <span className="pc-chip pc-chip--inactive"><span className="pc-chip__dot" />Inactive</span>}
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => setDrawer({ user: u, tab: 'profile' })}>Review</button>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('no2fa')}>Open no-2FA view →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Administrators</h2><div className="pc-panel-h__sub">Elevated platform access</div></div><span className="sc-badge sc-badge--ok">{admins.length}</span></div>
          {admins.slice(0, 4).map((u) => (
            <div key={u.id} className="sc-qitem">
              <div className="us-av us-av--sm">{initials(u.name)}</div>
              <div className="sc-qitem__who"><b>{u.name}</b><span>{u.email}</span></div>
              <span className={`us-rchip ${roleClass((u.roles ?? [])[0])}`}>{((u.roles ?? [])[0] ?? '').replace('Platform ', '')}</span>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('admins')}>Open admins view →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Recently joined</h2><div className="pc-panel-h__sub">Newest staff accounts</div></div></div>
          {[...list].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 4).map((u) => (
            <div key={u.id} className="sc-qitem">
              <div className="us-av us-av--sm">{initials(u.name)}</div>
              <div className="sc-qitem__who"><b>{u.name}</b><span>{(u.roles ?? [])[0] ?? '—'}</span></div>
              <span className="sc-qitem__when">{fmtDate(u.created_at)}</span>
            </div>
          ))}
        </CardBody></Card>
      </div>

      {/* workbench */}
      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search name or email…" ariaLabel="Search users" />
          <select className="pc-input sc-statusfilter" value={wb.facetValues.role} onChange={(e) => wb.setFacet('role', e.target.value)} aria-label="Role filter">
            <option value="all">All roles</option>
            {(roles ?? []).map((r) => <option key={r.id} value={r.name}>{r.name}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
            <option value="all">All statuses</option>
            {['active', 'inactive', 'locked'].map((k) => <option key={k} value={k}>{STATUS_LABEL[k]}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.tfa} onChange={(e) => wb.setFacet('tfa', e.target.value)} aria-label="2FA filter">
            <option value="all">Any 2FA</option>
            <option value="on">2FA on</option>
            <option value="off">2FA off</option>
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>

        <WbViews wb={wb} />

        <WbBulkBar wb={wb}>
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkToggle(true)}>Activate</button>}
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkToggle(false)}>Deactivate</button>}
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.selectedRows.forEach((u) => post(`/users/${u.id}/force-reset`))}>Force reset</button>}
          <button type="button" className="pc-btn pc-btn--sm" onClick={exportSelectedCsv}>Export selected</button>
          {canDelete && <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => confirmPost(`Delete ${wb.selection.size} selected user(s)?`, '/users/bulk/delete', { ids: bulkIds() })}>Delete</button>}
        </WbBulkBar>

        <WbTable wb={wb} columns={columns} selectable onRowClick={(u) => setDrawer({ user: u, tab: 'profile' })}
          rowAriaLabel={(u) => `${u.name}, ${STATUS_LABEL[u.status] ?? u.status}`}
          empty={<>No users match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>}
        />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawer && <DetailDrawer user={list.find((u) => u.id === drawer.user.id) ?? drawer.user} initialTab={drawer.tab} onClose={() => setDrawer(null)}
        actions={{ ...actions, edit: (u) => { setDrawer(null); setEditor(u); }, remove: (u) => { setDrawer(null); remove(u); } }} />}
      {editor !== undefined && <EditorModal user={editor} roles={roles} onClose={() => setEditor(undefined)} />}
      {inviting && <InviteModal roles={roles} onClose={() => setInviting(false)} />}
    </div>
  );
}

Index.layout = (page) => (
  <App title="Users" railTitle="Access & Security" rail={<UsersRail stats={page.props.stats} />}>
    {page}
  </App>
);
