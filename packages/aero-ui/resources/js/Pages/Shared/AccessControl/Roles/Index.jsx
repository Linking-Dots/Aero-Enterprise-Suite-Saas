import { useEffect, useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody, AreaSpark, Donut,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbColumns, WbTable, WbFooter,
} from '@aero/ui';

import RolesRail from './RolesRail.jsx';
import AccessDrawer from './AccessDrawer.jsx';

import '../../../Platform/Admin/Products/products.css';
import '../../../Platform/Admin/Billing/P2/subscriptions.css';
import './roles.css';

/* ---------------- shared bits ---------------- */
const PROTECTED_HIDDEN = 'Super Administrator';
const svg = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const Glyph = {
  shield: svg(<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  assign: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 11h-6M19 8v6" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
};
const roleIco = <span className="rl-ico">{Glyph.shield}</span>;

/* ---------------- page ---------------- */
export default function RolesIndex({
  roles, users, can_manage_super_admin, modules, accessScopes, statistics, subModules, coverage, error,
  routePrefix, moduleAccessRoutePrefix, hrmacNamespace, scope, dashboardRoute,
}) {
  routePrefix = routePrefix ?? 'core.roles';
  moduleAccessRoutePrefix = moduleAccessRoutePrefix ?? 'core.modules';
  hrmacNamespace = hrmacNamespace ?? 'core.roles_permissions';
  scope = scope ?? 'tenant';

  const toast = useToast();
  const canCreate = useHRMAC(`${hrmacNamespace}.roles.create`);
  const canEdit = useHRMAC(`${hrmacNamespace}.roles.edit`);
  const canDelete = useHRMAC(`${hrmacNamespace}.roles.delete`);
  const canAssign = useHRMAC(`${hrmacNamespace}.roles.assign`);
  const canConfigure = useHRMAC(`${hrmacNamespace}.module_access.configure`);
  const ctx = useCtxMenu();

  const subs = useMemo(() => subModules ?? [], [subModules]);
  const cov = coverage ?? {};
  const totalSubs = Math.max(1, subs.length);
  const covFor = (id) => cov[id] ?? { full: [], partial: [] };
  const reach = (id) => covFor(id).full.length + covFor(id).partial.length;

  const baseRoles = useMemo(
    () => (can_manage_super_admin ? (roles ?? []) : (roles ?? []).filter((r) => r.name !== PROTECTED_HIDDEN)),
    [roles, can_manage_super_admin],
  );

  /* ---- create/edit/assign + access drawers (logic preserved from the shared page) ---- */
  const [roleModal, setRoleModal] = useState(false);
  const [editingRole, setEditingRole] = useState(null);
  const [assignOpen, setAssignOpen] = useState(false);
  const [accessRole, setAccessRole] = useState(null);

  const roleForm = useForm({ name: '', description: '', default_dashboard: '', priority: 0 });
  const assignForm = useForm({ user_id: '', roles: [] });

  const openCreate = () => { setEditingRole(null); roleForm.reset(); roleForm.clearErrors(); setRoleModal(true); };
  const openEdit = (role) => {
    setEditingRole(role);
    roleForm.setData({ name: role.name || '', description: role.description || '', default_dashboard: role.default_dashboard || '', priority: role.priority || 0 });
    roleForm.clearErrors(); setRoleModal(true);
  };
  const openAssign = (role) => { assignForm.setData({ user_id: '', roles: role ? [String(role.id)] : [] }); assignForm.clearErrors(); setAssignOpen(true); };

  const submitRole = (e) => {
    e?.preventDefault();
    const opts = {
      preserveScroll: true,
      onSuccess: () => { toast.success(editingRole ? 'Role updated.' : 'Role created.'); setRoleModal(false); roleForm.reset(); setEditingRole(null); },
      onError: () => toast.error(editingRole ? 'Failed to update role.' : 'Failed to create role.'),
    };
    editingRole ? roleForm.put(route(`${routePrefix}.update`, editingRole.id), opts) : roleForm.post(route(`${routePrefix}.store`), opts);
  };
  const submitAssign = (e) => {
    e?.preventDefault();
    if (!assignForm.data.user_id || !assignForm.data.roles.length) return;
    assignForm.post(route(`${routePrefix}.assign-user`), {
      preserveScroll: true,
      onSuccess: () => { toast.success('Role assigned.'); setAssignOpen(false); assignForm.reset(); },
      onError: () => toast.error('Failed to assign role.'),
    });
  };
  const deleteRole = (role) => {
    if (!window.confirm(`Delete the “${role.name}” role?`)) return;
    router.delete(route(`${routePrefix}.destroy`, role.id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Role deleted.'),
      onError: () => toast.error('Failed to delete role.'),
    });
  };

  // Rail events + ?role= deep-link (preserved).
  useEffect(() => {
    const onCreate = () => canCreate && openCreate();
    const onAssign = () => canAssign && openAssign(null);
    const onSaved = () => router.reload({ only: ['roles', 'coverage', 'statistics'] });
    window.addEventListener('aeos:open-create-role', onCreate);
    window.addEventListener('aeos:open-assign-role', onAssign);
    window.addEventListener('aeos:access-saved', onSaved);
    return () => { window.removeEventListener('aeos:open-create-role', onCreate); window.removeEventListener('aeos:open-assign-role', onAssign); window.removeEventListener('aeos:access-saved', onSaved); };
  }, [canCreate, canAssign]); // eslint-disable-line react-hooks/exhaustive-deps
  useEffect(() => {
    const id = new URLSearchParams(window.location.search).get('role');
    if (id && roles) { const r = roles.find((x) => String(x.id) === String(id)); if (r) setAccessRole(r); }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  /* ---- coverage-matrix column grouping ----
     Primary: group by the SAME nav sections the sidebar uses (order + labels +
     membership), so access and navigation are consistent and easy to hunt.
     Fallback (no section data): core sub-modules flat, single product flat, 2+
     products grouped — matching the nav taxonomy rule. */
  const groups = useMemo(() => {
    if (!subs.length) return [];
    if (subs.some((s) => s.section)) {
      const by = new Map();
      subs.forEach((s) => {
        const key = s.section || '__other';
        if (!by.has(key)) by.set(key, { label: s.sectionLabel || 'Other', order: s.sectionOrder ?? 900, core: key === '__core', subs: [] });
        by.get(key).subs.push(s);
      });
      return [...by.values()].sort((a, b) => a.order - b.order);
    }
    const core = subs.filter((s) => s.moduleIsCore);
    const nonCore = subs.filter((s) => !s.moduleIsCore);
    const nonCoreMods = [...new Set(nonCore.map((s) => s.module))];
    const out = [];
    if (core.length) out.push({ label: 'Core', core: true, subs: core });
    if (nonCoreMods.length <= 1) {
      if (nonCore.length) out.push({ label: nonCoreMods[0] || 'Modules', subs: nonCore });
    } else {
      nonCoreMods.forEach((m) => out.push({ label: m, subs: nonCore.filter((s) => s.module === m) }));
    }
    return out;
  }, [subs]);
  const orderedSubs = useMemo(() => groups.flatMap((g) => g.subs), [groups]);

  /* ---- stats ---- */
  const counts = {
    total: baseRoles.length,
    protected: baseRoles.filter((r) => r.is_protected).length,
    custom: baseRoles.filter((r) => !r.is_protected).length,
    assigns: baseRoles.reduce((s, r) => s + (r.users_count ?? 0), 0),
    unused: baseRoles.filter((r) => (r.users_count ?? 0) === 0).length,
    avgReach: baseRoles.length ? Math.round(baseRoles.reduce((s, r) => s + reach(r.id), 0) / baseRoles.length) : 0,
  };
  const kpis = [
    { label: 'Total roles', value: counts.total, delta: `${counts.custom} custom · ${counts.protected} protected` },
    { label: 'Assignments', value: counts.assigns, delta: 'staff → role links', up: true },
    { label: 'Avg reach', value: counts.avgReach, delta: `of ${totalSubs} areas`, },
    { label: 'Protected', value: counts.protected, delta: 'system-guarded' },
    { label: 'Unused', value: counts.unused, delta: 'no members', warn: counts.unused > 0 },
    { label: 'Areas', value: totalSubs, delta: `${scope === 'platform' ? 'platform' : 'tenant'} surface` },
  ];

  const memberMax = Math.max(1, ...baseRoles.map((r) => r.users_count ?? 0));
  const membersSorted = useMemo(() => [...baseRoles].sort((a, b) => (b.users_count ?? 0) - (a.users_count ?? 0)), [baseRoles]);
  const depth = useMemo(() => {
    let full = 0; let partial = 0;
    baseRoles.forEach((r) => { full += covFor(r.id).full.length; partial += covFor(r.id).partial.length; });
    return { full, partial };
  }, [baseRoles]); // eslint-disable-line react-hooks/exhaustive-deps

  /* ---- workbench ---- */
  const wb = useWorkbench({
    rows: baseRoles,
    getId: (r) => r.id,
    searchText: (r) => `${r.name} ${r.description ?? ''}`,
    views: [
      { id: 'all', label: 'All roles' },
      { id: 'custom', label: 'Custom', test: (r) => !r.is_protected },
      { id: 'protected', label: 'Protected', test: (r) => r.is_protected },
      { id: 'broad', label: 'Broad access', test: (r) => reach(r.id) >= totalSubs * 0.5 },
      { id: 'unused', label: 'Unused', test: (r) => (r.users_count ?? 0) === 0 },
    ],
    facets: { type: { value: 'all', test: (r, v) => (v === 'protected' ? r.is_protected : !r.is_protected) } },
    sortKey: 'priority',
    sortVal: (r, k) => (k === 'users_count' || k === 'priority') ? (r[k] ?? 0) : k === 'reach' ? reach(r.id) : String(r[k] ?? ''),
    perPage: 20,
    storageKey: `roles.${scope}`,
  });

  const rowMenu = (r) => [
    ...(canConfigure ? [{ label: 'Configure access', onClick: () => setAccessRole(r) }] : []),
    ...(canAssign ? [{ label: 'Assign user', onClick: () => openAssign(r) }] : []),
    ...(canEdit && !r.is_protected ? [{ label: 'Edit role', onClick: () => openEdit(r) }] : []),
    ...(canDelete && !r.is_protected ? ['sep', { label: 'Delete role', danger: true, onClick: () => deleteRole(r) }] : []),
  ];

  const columns = [
    {
      key: 'name', label: 'Role', sortable: true,
      render: (r) => (
        <div className="pc-mrow">{roleIco}<div><div className="rl-name">{r.name}</div>{r.description && <div className="rl-desc">{r.description}</div>}</div></div>
      ),
    },
    { key: 'users_count', label: 'Members', align: 'r', sortable: true, render: (r) => <b>{r.users_count ?? 0}</b> },
    {
      key: 'reach', label: 'Reach', align: 'r', sortable: true,
      render: (r) => (
        <div className="rl-reach" style={{ justifyContent: 'flex-end' }}>
          <span className="rl-reach__bar"><i style={{ width: `${Math.min(reach(r.id) / totalSubs * 100, 100)}%` }} /></span>
          <span className="rl-reach__n">{reach(r.id)}</span>
        </div>
      ),
    },
    { key: 'module_access_count', label: 'Grants', hideSm: true, render: (r) => <span className={`rl-grants${(r.module_access_count ?? 0) ? '' : ' rl-grants--0'}`}>{r.module_access_count ?? 0}</span> },
    { key: 'priority', label: 'Priority', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{r.priority ?? 0}</span> },
    { key: 'type', label: 'Type', render: (r) => (r.is_protected ? <span className="pc-chip pc-chip--protected"><span className="pc-chip__dot" />Protected</span> : <span className="pc-chip pc-chip--custom"><span className="pc-chip__dot" />Custom</span>) },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.name}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button> },
  ];

  if (error) {
    return <div className="pc"><Card><CardBody>Error loading roles: {error.message}</CardBody></Card></div>;
  }

  return (
    <div className="pc rl">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> {scope === 'platform' ? 'Platform' : 'Workspace'} · Access &amp; Security</div>
          <h1 className="pc-title">Roles &amp; Access</h1>
          <div className="pc-sub">Every role, who holds it, and exactly what it can reach — a live coverage matrix across all {scope === 'platform' ? 'platform' : 'workspace'} areas, with create, edit, delete, assign-users and cascading per-module grant control.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => window.print()}>{Glyph.export}<span>Export</span></button>
          {canAssign && <button type="button" className="pc-btn" onClick={() => openAssign(null)}>{Glyph.assign}<span>Assign user</span></button>}
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={openCreate}>{Glyph.plus}<span>Create role</span></button>}
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
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* coverage matrix — hero */}
      <Card><CardBody>
        <div className="pc-panel-h">
          <div><h2 className="pc-panel-h__title">Coverage matrix</h2><div className="pc-panel-h__sub">Roles × {scope === 'platform' ? 'platform' : 'workspace'} areas — click a cell to configure that role’s access</div></div>
          <span className="sc-badge sc-badge--ok">{baseRoles.length} roles · {totalSubs} areas</span>
        </div>
        {orderedSubs.length === 0 ? <div className="wb-empty">No modules in scope.</div> : (
          <div className="rl-mx-wrap">
            <div className="rl-mx">
              <div className="rl-mx-groups" style={{ display: 'flex' }}>
                <div className="rl-mx-lead" />
                {groups.map((g) => <div key={g.label} className={`rl-mx-grp${g.core ? ' rl-mx-grp--core' : ''}`} style={{ width: g.subs.length * 22 }}>{g.label} · {g.subs.length}</div>)}
              </div>
              {baseRoles.map((r) => {
                const c = covFor(r.id);
                const full = new Set(c.full); const partial = new Set(c.partial);
                return (
                  <div className="rl-mx-row" key={r.id} style={{ display: 'flex' }}>
                    <div className="rl-mx-role">{roleIco}<b>{r.name}</b></div>
                    {orderedSubs.map((sub) => {
                      const state = full.has(sub.id) ? 'full' : partial.has(sub.id) ? 'partial' : 'none';
                      return (
                        <div key={sub.id} className={`rl-cell rl-g-${state}`}>
                          <button type="button" title={`${sub.module} › ${sub.name}: ${state}`} aria-label={`${r.name} — ${sub.name}: ${state}`} onClick={() => canConfigure && setAccessRole(r)} />
                        </div>
                      );
                    })}
                  </div>
                );
              })}
            </div>
          </div>
        )}
        <div className="rl-mx-legend">
          <span className="li"><span className="sw" style={{ background: 'var(--aeos-primary)' }} />Full (module/area grant — cascades)</span>
          <span className="li"><span className="sw" style={{ background: 'color-mix(in srgb, var(--aeos-warning) 55%, transparent)' }} />Partial (some components)</span>
          <span className="li"><span className="sw" style={{ background: 'var(--aeos-bg-subtle)', border: '1px solid var(--aeos-border-subtle)' }} />None</span>
        </div>
      </CardBody></Card>

      {/* analytics band */}
      <div className="sc-band" style={{ gridTemplateColumns: 'minmax(0,1.4fr) minmax(0,1fr)' }}>
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Members by role</h2><div className="pc-panel-h__sub">Assigned staff — click to configure</div></div><span className="sc-badge sc-badge--ok">{counts.assigns}</span></div>
          <div className="rl-mix">
            {membersSorted.map((r, i) => (
              <button key={r.id} type="button" className="rl-mixrow" onClick={() => canConfigure && setAccessRole(r)}>
                <span className="rl-mixrow__cap">{r.name}</span>
                <span className="rl-mixrow__track"><span className={`rl-mixrow__fill rl-mix-${i % 5}`} style={{ width: `${Math.max((r.users_count ?? 0) / memberMax * 100, (r.users_count ?? 0) ? 6 : 0)}%` }} /></span>
                <span className="rl-mixrow__n"><b>{r.users_count ?? 0}</b> · {reach(r.id)}★</span>
              </button>
            ))}
          </div>
        </CardBody></Card>
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Grant depth</h2><div className="pc-panel-h__sub">Full vs partial area grants</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={[{ color: 'var(--aeos-primary)', value: depth.full }, { color: 'var(--aeos-warning)', value: depth.partial }]} centerValue={depth.full + depth.partial} centerLabel="grants" size={112} />
            <div className="sc-dl">
              <span className="li"><span className="d" style={{ background: 'var(--aeos-primary)' }} />Full<b>{depth.full}</b></span>
              <span className="li"><span className="d" style={{ background: 'var(--aeos-warning)' }} />Partial<b>{depth.partial}</b></span>
            </div>
          </div>
        </CardBody></Card>
      </div>

      {/* workbench */}
      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search role name or description…" ariaLabel="Search roles" />
          <select className="pc-input sc-statusfilter" value={wb.facetValues.type} onChange={(e) => wb.setFacet('type', e.target.value)} aria-label="Type filter">
            <option value="all">All types</option>
            <option value="custom">Custom</option>
            <option value="protected">Protected</option>
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>
        <WbViews wb={wb} />
        <WbTable wb={wb} columns={columns} onRowClick={(r) => (canConfigure ? setAccessRole(r) : null)}
          rowAriaLabel={(r) => `${r.name}, ${r.is_protected ? 'protected' : 'custom'}`}
          empty={<>No roles match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>}
        />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {/* create / edit role modal */}
      {roleModal && (
        <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && setRoleModal(false)}>
          <div className="pc-modal" role="dialog" aria-modal="true">
            <h2 className="pc-modal__title">{editingRole ? `Edit ${editingRole.name}` : 'Create role'}</h2>
            <div className="pc-modal__sub">{editingRole ? 'Update the role, then configure its access.' : 'Define a role, then grant it access in the matrix.'}</div>
            <form className="pc-form" onSubmit={submitRole}>
              <div className="rl-grid2">
                <div className="pc-field"><label className="pc-field__label" htmlFor="r-name">Role name</label>
                  <input id="r-name" className="pc-input" value={roleForm.data.name} onChange={(e) => roleForm.setData('name', e.target.value)} autoFocus required />
                  {roleForm.errors.name && <span className="pc-field__err">{roleForm.errors.name}</span>}</div>
                <div className="pc-field"><label className="pc-field__label" htmlFor="r-prio">Priority</label>
                  <input id="r-prio" type="number" className="pc-input" value={roleForm.data.priority} onChange={(e) => roleForm.setData('priority', parseInt(e.target.value, 10) || 0)} /></div>
              </div>
              <div className="pc-field"><label className="pc-field__label" htmlFor="r-desc">Description</label>
                <input id="r-desc" className="pc-input" value={roleForm.data.description} onChange={(e) => roleForm.setData('description', e.target.value)} placeholder="What this role is for" /></div>
              <div className="pc-field"><label className="pc-field__label" htmlFor="r-dash">Default dashboard</label>
                <input id="r-dash" className="pc-input" value={roleForm.data.default_dashboard} onChange={(e) => roleForm.setData('default_dashboard', e.target.value)} placeholder="e.g. core.dashboard" /></div>
              <div className="pc-modal__actions">
                <span className="pc-spacer" />
                <button type="button" className="pc-btn" onClick={() => setRoleModal(false)}>Cancel</button>
                <button type="submit" className="pc-btn pc-btn--primary" disabled={roleForm.processing || !roleForm.data.name.trim()}>{roleForm.processing ? 'Saving…' : (editingRole ? 'Save changes' : 'Create role')}</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* assign-user modal */}
      {assignOpen && (
        <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && setAssignOpen(false)}>
          <div className="pc-modal" role="dialog" aria-modal="true">
            <h2 className="pc-modal__title">Assign role to user</h2>
            <div className="pc-modal__sub">Pick a role and the user who should receive it.</div>
            <form className="pc-form" onSubmit={submitAssign}>
              <div className="pc-field"><label className="pc-field__label" htmlFor="a-role">Role</label>
                <select id="a-role" className="pc-input" value={assignForm.data.roles[0] ?? ''} onChange={(e) => assignForm.setData('roles', e.target.value ? [e.target.value] : [])}>
                  <option value="">Choose a role…</option>
                  {baseRoles.map((r) => <option key={r.id} value={String(r.id)}>{r.name}</option>)}
                </select></div>
              <div className="pc-field"><label className="pc-field__label" htmlFor="a-user">User</label>
                <select id="a-user" className="pc-input" value={assignForm.data.user_id} onChange={(e) => assignForm.setData('user_id', e.target.value)}>
                  <option value="">Choose a user…</option>
                  {(users ?? []).map((u) => <option key={u.id} value={String(u.id)}>{u.name} ({u.email})</option>)}
                </select></div>
              <div className="pc-modal__actions">
                <span className="pc-spacer" />
                <button type="button" className="pc-btn" onClick={() => setAssignOpen(false)}>Cancel</button>
                <button type="submit" className="pc-btn pc-btn--primary" disabled={assignForm.processing || !assignForm.data.user_id || !assignForm.data.roles.length}>{assignForm.processing ? 'Assigning…' : 'Assign role'}</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* module-access drawer (reused, cascading tree editor) */}
      <AccessDrawer
        role={accessRole}
        modules={modules}
        accessScopes={accessScopes}
        canConfigure={canConfigure}
        moduleAccessRoutePrefix={moduleAccessRoutePrefix}
        onClose={() => setAccessRole(null)}
      />

      {ctx.element}
    </div>
  );
}

RolesIndex.layout = (page) => (
  <App title="Roles & Access" railTitle="Roles & access" rail={
    <RolesRail roles={page.props.roles} routePrefix={page.props.routePrefix} scope={page.props.scope} />
  }>{page}</App>
);
