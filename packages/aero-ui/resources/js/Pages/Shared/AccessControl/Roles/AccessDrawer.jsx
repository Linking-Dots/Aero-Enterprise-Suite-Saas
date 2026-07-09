/**
 * AccessDrawer — per-role module-access editor (the "Configure access" drawer),
 * shared across tenant + platform. Nav-consistent: sub-modules are grouped under
 * the SAME sections as the sidebar (with section icons) and access cascades with
 * clear inheritance — a granted module/area covers everything beneath it.
 *
 * HRMAC contract (RoleModuleAccessService):
 *   GET  <prefix>.role-access.show  -> { access: { explicit_modules:[id],
 *                                        sub_modules:[id], components:[id],
 *                                        actions:[{id,scope}] } }
 *   POST <prefix>.role-access.sync  <- { modules:[id], sub_modules:[id],
 *                                        components:[id], actions:[{id,scope}] }
 * Only ACTIONS carry a data scope (all/own/team/department).
 */
import { useState, useEffect, useCallback, useMemo } from 'react';
import { Drawer, Button, useToast } from '@aero/ui';
import { ChevronRightIcon, ChevronDownIcon } from '@heroicons/react/24/outline';
import './roles.css';

const xsrfToken = () => decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '');

/* section-key → glyph (mirrors the nav section icons) */
const P = (d) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{d}</svg>;
const SECTION_ICON = {
  ov: P(<><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></>),
  tn: P(<><path d="M3 21h18M6 21V7l6-4 6 4v14" /><path d="M10 9h4M10 13h4M10 17h4" /></>),
  rv: P(<><circle cx="12" cy="12" r="9" /><path d="M12 7v10M9.5 9.5h4a1.5 1.5 0 0 1 0 3h-3a1.5 1.5 0 0 0 0 3h4" /></>),
  gr: P(<><path d="M3 11v2a1 1 0 0 0 1 1h3l4 4V6L7 10H4a1 1 0 0 0-1 1Z" /><path d="M15 8a5 5 0 0 1 0 8" /></>),
  access: P(<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />),
  cf: P(<><circle cx="12" cy="12" r="3" /><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.3 1a7 7 0 0 0-1.7-1l-.3-2.5h-4l-.3 2.5a7 7 0 0 0-1.7 1l-2.3-1-2 3.5 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.3-1a7 7 0 0 0 1.7 1l.3 2.5h4l.3-2.5a7 7 0 0 0 1.7-1l2.3 1 2-3.5-2-1.5c.1-.3.1-.7.1-1Z" /></>),
  op: P(<path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" />),
  cs: P(<><circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="3.5" /><path d="M4.9 4.9 9 9M15 15l4.1 4.1M15 9l4.1-4.1M9 15l-4.1 4.1" /></>),
  __core: P(<><path d="m12 3 9 5-9 5-9-5 9-5Z" /><path d="m3 13 9 5 9-5" /></>),
  __other: P(<><circle cx="5" cy="12" r="1.6" /><circle cx="12" cy="12" r="1.6" /><circle cx="19" cy="12" r="1.6" /></>),
};
const sectionGlyph = (key) => SECTION_ICON[key] ?? SECTION_ICON.__other;
const Chevron = ({ open }) => (open ? <ChevronDownIcon aria-hidden="true" /> : <ChevronRightIcon aria-hidden="true" />);
const initials = (n) => (n || '?').replace(/[^A-Za-z0-9 ]/g, '').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();

export default function AccessDrawer({ role, modules = [], accessScopes, canConfigure, moduleAccessRoutePrefix, onClose }) {
  moduleAccessRoutePrefix = moduleAccessRoutePrefix ?? 'core.modules';
  const toast = useToast();
  const open = !!role;
  const isProtected = !!role?.is_protected;

  const [mods, setMods] = useState(new Set());
  const [subs, setSubs] = useState(new Set());
  const [comps, setComps] = useState(new Set());
  const [actions, setActions] = useState(new Map());
  const [expanded, setExpanded] = useState(new Set());
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  const scopeOptions = (accessScopes?.length ? accessScopes : ['all', 'own', 'team', 'department'])
    .map((s) => (typeof s === 'string' ? { value: s, label: s[0].toUpperCase() + s.slice(1) } : { value: s.value ?? s, label: s.label ?? s }));
  const defaultScope = scopeOptions[0]?.value ?? 'all';

  const fetchAccess = useCallback(async (roleId) => {
    if (!roleId) return;
    setLoading(true);
    try {
      const res = await fetch(route(`${moduleAccessRoutePrefix}.role-access.show`, roleId), { headers: { Accept: 'application/json' } });
      if (!res.ok) { toast.error('Failed to load role access'); return; }
      const a = (await res.json()).access || {};
      setMods(new Set((a.explicit_modules || []).map(Number)));
      setSubs(new Set((a.sub_modules || []).map(Number)));
      setComps(new Set((a.components || []).map(Number)));
      setActions(new Map((a.actions || []).map((x) => [Number(x.id), x.scope || defaultScope])));
    } catch { toast.error('Network error loading access'); } finally { setLoading(false); }
  }, [defaultScope, toast, moduleAccessRoutePrefix]);

  useEffect(() => {
    if (open && !isProtected) fetchAccess(role.id);
    if (!open) { setSearch(''); setExpanded(new Set()); }
  }, [open, isProtected, role?.id, fetchAccess]);

  /* flatten sub-modules with parent-module + section, grouped by nav section */
  const sections = useMemo(() => {
    const flat = [];
    modules.forEach((m) => (m.sub_modules || []).forEach((s) => flat.push({ ...s, moduleId: m.id, moduleName: m.name })));
    const q = search.trim().toLowerCase();
    const matches = (n) => !q || (n || '').toLowerCase().includes(q);
    const keep = (s) => {
      if (matches(s.name)) return s;
      const cs = (s.components || []).map((c) => {
        const as = (c.actions || []).filter((a) => matches(a.name));
        return (matches(c.name) || as.length) ? { ...c, actions: matches(c.name) ? c.actions : as } : null;
      }).filter(Boolean);
      return cs.length ? { ...s, components: cs } : null;
    };
    const filtered = q ? flat.map(keep).filter(Boolean) : flat;
    const by = new Map();
    filtered.forEach((s) => {
      const key = s.section || '__other';
      if (!by.has(key)) by.set(key, { key, label: s.sectionLabel || 'Other', order: s.sectionOrder ?? 900, subs: [] });
      by.get(key).subs.push(s);
    });
    return [...by.values()].sort((a, b) => a.order - b.order);
  }, [modules, search]);

  const allModuleIds = useMemo(() => modules.map((m) => m.id), [modules]);
  const fullAll = allModuleIds.length > 0 && allModuleIds.every((id) => mods.has(id));

  /* inheritance helpers */
  const subCovered = (s) => mods.has(s.moduleId);
  const compCovered = (s, c) => subCovered(s) || subs.has(s.id);
  const actCovered = (s, c) => compCovered(s, c) || comps.has(c.id);
  const subState = (s) => {
    if (subCovered(s)) return 'inherit';
    if (subs.has(s.id)) return 'full';
    const some = (s.components || []).some((c) => comps.has(c.id) || (c.actions || []).some((a) => actions.has(a.id)));
    return some ? 'partial' : 'none';
  };

  const clone = { set: (s) => new Set(s), map: (m) => new Map(m) };
  const toggleFullAll = () => {
    if (fullAll) { setMods(new Set()); }
    else { setMods(new Set(allModuleIds)); setSubs(new Set()); setComps(new Set()); setActions(new Map()); }
  };
  const toggleSub = (s) => {
    const n = clone.set(subs);
    if (n.has(s.id)) n.delete(s.id);
    else {
      n.add(s.id);
      const c2 = clone.set(comps); const a2 = clone.map(actions);
      (s.components || []).forEach((c) => { c2.delete(c.id); (c.actions || []).forEach((a) => a2.delete(a.id)); });
      setComps(c2); setActions(a2);
    }
    setSubs(n);
  };
  const toggleComp = (s, c) => {
    const n = clone.set(comps);
    if (n.has(c.id)) n.delete(c.id);
    else { n.add(c.id); const a2 = clone.map(actions); (c.actions || []).forEach((a) => a2.delete(a.id)); setActions(a2); }
    setComps(n);
  };
  const toggleAction = (a) => { const n = clone.map(actions); n.has(a.id) ? n.delete(a.id) : n.set(a.id, defaultScope); setActions(n); };
  const setActionScope = (id, scope) => setActions((p) => new Map(p).set(id, scope));
  const toggleExpand = (k) => setExpanded((p) => { const n = new Set(p); n.has(k) ? n.delete(k) : n.add(k); return n; });
  const grantSection = (sec) => { const n = clone.set(subs); sec.subs.forEach((s) => { if (!subCovered(s)) n.add(s.id); }); setSubs(n); };
  const expandAll = () => setExpanded(new Set(sections.flatMap((sec) => sec.subs.map((s) => `s${s.id}`))));
  const collapseAll = () => setExpanded(new Set());

  const handleSave = async () => {
    if (!role) return;
    setSaving(true);
    const payload = { modules: [...mods], sub_modules: [...subs], components: [...comps], actions: [...actions].map(([id, scope]) => ({ id, scope })) };
    try {
      const res = await fetch(route(`${moduleAccessRoutePrefix}.role-access.sync`, role.id), {
        method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() }, body: JSON.stringify(payload),
      });
      if (res.ok) { toast.success('Role access updated.'); window.dispatchEvent(new CustomEvent('aeos:access-saved')); onClose(); }
      else { const e = await res.json().catch(() => ({})); toast.error(e.error || e.message || 'Failed to save access'); }
    } catch { toast.error('Network error saving access'); } finally { setSaving(false); }
  };

  const grantTotal = mods.size + subs.size + comps.size;
  const rw = !isProtected && canConfigure;

  return (
    <Drawer open={open} onClose={onClose} width={760} title={role ? `Access — ${role.name}` : 'Access'}
      footer={rw && (
        <div className="pc-modal__actions" style={{ margin: 0 }}>
          <span className="pc-spacer" />
          <button type="button" className="pc-btn" onClick={() => fetchAccess(role.id)} disabled={loading || saving}>Reset</button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={handleSave} disabled={loading || saving}>{saving ? 'Saving…' : 'Save changes'}</button>
        </div>
      )}
    >
      {isProtected ? (
        <div className="rbac-protected">
          {P(<path d="M20 6 9 17l-5-5" />)}
          <div style={{ fontWeight: 640, marginBottom: 4 }}>Protected role</div>
          <div style={{ fontSize: '.85rem', color: 'var(--aeos-text-muted)' }}>Protected roles have full system access and cannot be edited.</div>
        </div>
      ) : (
        <div className="rbac">
          <div className="rbac-sum">
            <div className="rbac-sum__meta"><b>{grantTotal}</b> area grant{grantTotal === 1 ? '' : 's'} · <b>{actions.size}</b> action{actions.size === 1 ? '' : 's'}</div>
            <label className="rbac-full"><input type="checkbox" checked={fullAll} onChange={toggleFullAll} disabled={!rw} /> Full access to all areas</label>
          </div>

          <div className="rbac-toolbar">
            <div className="rbac-search">{P(<><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" /></>)}
              <input placeholder="Search areas, components, actions…" value={search} onChange={(e) => setSearch(e.target.value)} />
            </div>
            <button type="button" className="rbac-xbtn" onClick={expandAll}>Expand all</button>
            <button type="button" className="rbac-xbtn" onClick={collapseAll}>Collapse all</button>
          </div>

          {loading ? (
            <div className="wb-empty">Loading access…</div>
          ) : fullAll ? (
            <div className="callout" style={{ background: 'var(--aeos-primary-tint)', border: '1px solid var(--aeos-primary-border)', color: 'var(--aeos-primary)', fontSize: '.8rem', borderRadius: '10px', padding: '12px 14px' }}>
              This role has <b>full access to every area</b> (a module-level grant that cascades). Untick “Full access” above to grant areas individually.
            </div>
          ) : sections.length === 0 ? (
            <div className="wb-empty">{search ? 'No areas match your search.' : 'No areas available to grant.'}</div>
          ) : (
            <div className="rbac-tree">
              {sections.map((sec) => (
                <div className="rbac-sec" key={sec.key}>
                  <div className="rbac-sec__h">
                    <span className="rbac-sec__ico">{sectionGlyph(sec.key)}</span>
                    <span className="rbac-sec__t">{sec.label}</span>
                    <span className="rbac-sec__n">{sec.subs.length}</span>
                    {rw && <button type="button" className="rbac-sec__all" onClick={() => grantSection(sec)}>Grant all</button>}
                  </div>
                  {sec.subs.map((s) => {
                    const sOpen = expanded.has(`s${s.id}`) || !!search.trim();
                    const st = subState(s);
                    const covered = subCovered(s);
                    return (
                      <div key={s.id}>
                        <div className="rbac-row rbac-row--sub">
                          <button type="button" className="rbac-chev" onClick={() => toggleExpand(`s${s.id}`)} aria-label="Toggle">{<Chevron open={sOpen} />}</button>
                          <input type="checkbox" className="rbac-cb" checked={covered || subs.has(s.id)} disabled={!rw || covered} onChange={() => toggleSub(s)} aria-label={s.name} />
                          <span className="rbac-node-ico">{initials(s.name)}</span>
                          <span className="rbac-lbl">{s.name}</span>
                          {st === 'inherit' && <span className="rbac-state rbac-state--inherit">Inherited</span>}
                          {st === 'full' && <span className="rbac-state rbac-state--full">Full</span>}
                          {st === 'partial' && <span className="rbac-state rbac-state--partial">Partial</span>}
                        </div>
                        {sOpen && (s.components || []).map((c) => {
                          const cOpen = expanded.has(`c${c.id}`) || !!search.trim();
                          const cCov = compCovered(s, c);
                          return (
                            <div key={c.id}>
                              <div className="rbac-row rbac-row--comp">
                                <button type="button" className="rbac-chev" onClick={() => toggleExpand(`c${c.id}`)} aria-label="Toggle">{<Chevron open={cOpen} />}</button>
                                <input type="checkbox" className="rbac-cb" checked={cCov || comps.has(c.id)} disabled={!rw || cCov} onChange={() => toggleComp(s, c)} aria-label={c.name} />
                                <span className="rbac-lbl">{c.name}</span>
                                {cCov && <span className="rbac-inherit-note">inherited</span>}
                              </div>
                              {cOpen && (c.actions || []).map((a) => {
                                const aCov = actCovered(s, c);
                                const granted = actions.has(a.id);
                                return (
                                  <div key={a.id} className="rbac-row rbac-row--action">
                                    <input type="checkbox" className="rbac-cb" checked={aCov || granted} disabled={!rw || aCov} onChange={() => toggleAction(a)} aria-label={a.name} />
                                    <span className="rbac-lbl">{a.name}</span>
                                    {aCov ? <span className="rbac-inherit-note">inherited</span>
                                      : granted ? (
                                        <select className="rbac-scope" value={actions.get(a.id)} onChange={(e) => setActionScope(a.id, e.target.value)} disabled={!rw} aria-label={`Scope for ${a.name}`}>
                                          {scopeOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                        </select>
                                      ) : null}
                                  </div>
                                );
                              })}
                              {cOpen && (!c.actions || c.actions.length === 0) && <div className="rbac-empty">No actions</div>}
                            </div>
                          );
                        })}
                        {sOpen && (!s.components || s.components.length === 0) && <div className="rbac-empty">No components</div>}
                      </div>
                    );
                  })}
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </Drawer>
  );
}
