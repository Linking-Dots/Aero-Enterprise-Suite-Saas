import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, Donut,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './leads.css';

/* ---------------- glyphs ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  news: svg(<><path d="M4 4h16v16H4z" /><path d="M8 8h8M8 12h8M8 16h5" /></>),
  target: svg(<><circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="4" /></>),
};

/* ---------------- domain maps ---------------- */
const STATUS = {
  new: { label: 'New', color: 'var(--aeos-primary)', cls: 'ld-s-new' },
  contacted: { label: 'Contacted', color: 'var(--aeos-warning)', cls: 'ld-s-contact' },
  qualified: { label: 'Qualified', color: '#a78bfa', cls: 'ld-s-qual' },
  converted: { label: 'Converted', color: 'var(--aeos-success)', cls: 'ld-s-conv' },
  lost: { label: 'Lost', color: 'var(--aeos-danger)', cls: 'ld-s-lost' },
};
const STAGES = ['new', 'contacted', 'qualified', 'converted', 'lost'];
const SOURCE_COLORS = ['var(--aeos-primary)', 'var(--aeos-success)', 'var(--aeos-warning)', '#a78bfa', '#22d3ee', '#fb7185', 'var(--aeos-text-secondary)'];
const INTEREST = { high: 'High', medium: 'Medium', low: 'Low' };

const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const scoreCls = (s) => (s >= 70 ? 'ld-score--hi' : s >= 40 ? 'ld-score--md' : 'ld-score--lo');
const fmtDate = (s) => {
  if (!s) return '—';
  try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; }
};
const fmtDateShort = (s) => {
  if (!s) return '—';
  try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; }
};
const ago = (s) => {
  if (!s) return '—';
  const d = (Date.now() - new Date(s).getTime()) / 1000;
  if (d < 3600) return `${Math.max(1, Math.round(d / 60))}m ago`;
  if (d < 86400) return `${Math.round(d / 3600)}h ago`;
  if (d < 86400 * 30) return `${Math.round(d / 86400)}d ago`;
  return fmtDateShort(s);
};

/* ---------------- api helper (JSON endpoints) ---------------- */
const xsrf = () => decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
const api = (method, url, body) => fetch(url, {
  method,
  credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
  body: body ? JSON.stringify(body) : undefined,
}).then((r) => (r.ok
  ? r.json().catch(() => ({}))
  : r.json().then((j) => Promise.reject(new Error(j.message || `HTTP ${r.status}`)), () => Promise.reject(new Error(`HTTP ${r.status}`)))));
const reload = () => router.reload({ only: ['overview'] });

/* ---------------- rail ---------------- */
function LeadsRail({ overview }) {
  const o = overview ?? {};
  const s = o.stats ?? {};
  const list = o.leads ?? [];
  const unassigned = list.filter((l) => !l.assigned_to && l.status !== 'converted' && l.status !== 'lost').slice(0, 5);
  const hot = list.filter((l) => l.score >= 70 && ['new', 'contacted', 'qualified'].includes(l.status)).sort((a, b) => b.score - a.score).slice(0, 5);
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Pipeline</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Open leads</span><b>{s.open ?? 0}</b></div>
          <div className="pc-rail__row"><span>Qualified</span><b>{s.qualified ?? 0}</b></div>
          <div className="pc-rail__row"><span>Conversion</span><b>{s.conversion_rate ?? 0}%</b></div>
          <div className="pc-rail__row"><span>Unassigned</span><b>{s.unassigned ?? 0}</b></div>
          <div className="pc-rail__row"><span>Avg score</span><b>{s.avg_score ?? 0}</b></div>
        </div>
      </div>
      {hot.length > 0 && (
        <div>
          <div className="pc-panel-h__title">Hot leads</div>
          <div className="ld-railq">
            {hot.map((l) => (
              <div key={l.id} className="ld-railq__it">
                <span className="ld-railq__nm">{l.name || l.email}</span>
                <span className={`ld-score ${scoreCls(l.score)}`}>{l.score}</span>
              </div>
            ))}
          </div>
        </div>
      )}
      {unassigned.length > 0 && (
        <div>
          <div className="pc-panel-h__title">Unassigned</div>
          <div className="ld-railq">
            {unassigned.map((l) => (
              <div key={l.id} className="ld-railq__it">
                <span className="ld-railq__nm">{l.name || l.email}</span>
                <span className="ld-railq__sub">{STATUS[l.status]?.label}</span>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

/* ---------------- lead form modal (create / edit) ---------------- */
function LeadFormModal({ lead, sourceOptions, onClose, onDone }) {
  const editing = !!lead;
  const [data, setData] = useState(() => ({
    email: lead?.email ?? '',
    name: lead?.name ?? '',
    company_name: lead?.company ?? '',
    phone: lead?.phone ?? '',
    country: lead?.country ?? '',
    source: lead?.source ?? 'website',
    interest_level: lead?.interest ?? 'medium',
    notes: lead?.notes ?? '',
  }));
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState(null);
  const set = (k, v) => setData((d) => ({ ...d, [k]: v }));

  const submit = (e) => {
    e.preventDefault();
    if (!data.email.trim()) { setErr('Email is required.'); return; }
    setBusy(true); setErr(null);
    const req = editing ? api('PUT', `/leads/${lead.id}`, data) : api('POST', '/leads', data);
    req.then(() => onDone(editing ? 'Lead updated.' : 'Lead created.'))
      .catch((e2) => { setErr(e2.message); setBusy(false); });
  };

  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{editing ? `Edit ${lead.name || 'lead'}` : 'New lead'}</h2>
        <div className="pc-modal__sub">{editing ? 'Update the prospect’s details — the score recalculates automatically.' : 'Capture a prospect manually. The lead score is computed from the fields you provide.'}</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="ld-form-grid">
            <div className="pc-field"><label className="pc-field__label">Email *</label><input className="pc-input" type="email" value={data.email} onChange={(e) => set('email', e.target.value)} disabled={editing} placeholder="name@company.com" autoFocus /></div>
            <div className="pc-field"><label className="pc-field__label">Full name</label><input className="pc-input" value={data.name} onChange={(e) => set('name', e.target.value)} placeholder="Jane Doe" /></div>
            <div className="pc-field"><label className="pc-field__label">Company</label><input className="pc-input" value={data.company_name} onChange={(e) => set('company_name', e.target.value)} placeholder="Acme Ltd" /></div>
            <div className="pc-field"><label className="pc-field__label">Phone</label><input className="pc-input" value={data.phone} onChange={(e) => set('phone', e.target.value)} placeholder="+1 555 019" /></div>
            <div className="pc-field"><label className="pc-field__label">Country</label><input className="pc-input" value={data.country} onChange={(e) => set('country', e.target.value)} placeholder="US" /></div>
            <div className="pc-field"><label className="pc-field__label">Source</label>
              <select className="pc-input" value={data.source} onChange={(e) => set('source', e.target.value)}>
                {Object.entries(sourceOptions ?? {}).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
              </select>
            </div>
            <div className="pc-field"><label className="pc-field__label">Interest</label>
              <select className="pc-input" value={data.interest_level} onChange={(e) => set('interest_level', e.target.value)}>
                <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
              </select>
            </div>
          </div>
          <div className="pc-field"><label className="pc-field__label">Notes</label><textarea className="pc-input" value={data.notes} onChange={(e) => set('notes', e.target.value)} placeholder="Context, requirements, next steps…" /></div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Saving…' : (editing ? 'Save changes' : 'Create lead')}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- assign modal (single or bulk) ---------------- */
function AssignModal({ assignees, count, onClose, onPick }) {
  const [uid, setUid] = useState(assignees?.[0]?.id ?? '');
  const [busy, setBusy] = useState(false);
  const submit = (e) => { e.preventDefault(); if (!uid) return; setBusy(true); onPick(Number(uid)); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Assign {count > 1 ? `${count} leads` : 'lead'}</h2>
        <div className="pc-modal__sub">Route ownership to a member of the sales team.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label">Owner</label>
            <select className="pc-input" value={uid} onChange={(e) => setUid(e.target.value)} autoFocus>
              {(assignees ?? []).map((u) => <option key={u.id} value={u.id}>{u.name || u.email}</option>)}
            </select>
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !uid}>{busy ? 'Assigning…' : 'Assign'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- lost modal (reason) ---------------- */
function LostModal({ count, onClose, onConfirm }) {
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState(false);
  const submit = (e) => { e.preventDefault(); setBusy(true); onConfirm(reason.trim() || null); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Mark {count > 1 ? `${count} leads` : 'lead'} lost</h2>
        <div className="pc-modal__sub">Record why so the pipeline stays honest — it shows on the lead.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label">Reason</label>
            <textarea className="pc-input" value={reason} onChange={(e) => setReason(e.target.value)} placeholder="e.g. Chose a competitor / budget / no-fit" autoFocus />
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--danger" disabled={busy}>{busy ? 'Saving…' : 'Mark lost'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- pipeline board ---------------- */
function PipelineBoard({ list, canMove, onOpen, onMove, ctx, actions }) {
  const [dragId, setDragId] = useState(null);
  const [over, setOver] = useState(null);
  const byStage = useMemo(() => {
    const m = Object.fromEntries(STAGES.map((s) => [s, []]));
    list.forEach((l) => { (m[l.status] ?? (m[l.status] = [])).push(l); });
    return m;
  }, [list]);

  const drop = (stage) => {
    setOver(null);
    const id = dragId; setDragId(null);
    if (!id) return;
    const lead = list.find((l) => l.id === id);
    if (lead && lead.status !== stage) onMove(lead, stage);
  };

  const cardMenu = (l) => STAGES.filter((s) => s !== l.status).map((s) => ({
    label: `Move to ${STATUS[s].label}`, onClick: () => onMove(l, s),
  })).concat(['sep', { label: 'Assign…', onClick: () => actions.assign(l) }, { label: 'Delete', danger: true, onClick: () => actions.remove(l) }]);

  return (
    <div className="ld-board">
      {STAGES.map((stage) => (
        <div key={stage} className="ld-col">
          <div className="ld-col__h">
            <span className="ld-col__t"><span className="ld-dot" style={{ background: STATUS[stage].color }} />{STATUS[stage].label}</span>
            <span className="ld-col__c">{byStage[stage].length}</span>
          </div>
          <div
            className={`ld-lane${over === stage ? ' is-over' : ''}`}
            onDragOver={canMove ? (e) => { e.preventDefault(); setOver(stage); } : undefined}
            onDragLeave={() => setOver((o) => (o === stage ? null : o))}
            onDrop={canMove ? () => drop(stage) : undefined}
          >
            {byStage[stage].length === 0 && <div className="ld-lane__empty">—</div>}
            {byStage[stage].slice(0, 40).map((l) => (
              <div
                key={l.id}
                className="ld-card"
                draggable={canMove}
                onDragStart={() => setDragId(l.id)}
                onDragEnd={() => { setDragId(null); setOver(null); }}
                onClick={() => onOpen(l)}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => { if (e.key === 'Enter') onOpen(l); }}
              >
                <div className="ld-card__top">
                  <span className="ld-card__nm">{l.name || l.email}</span>
                  <button type="button" className="wb-kebab" aria-label="Card actions" onClick={(e) => { e.stopPropagation(); ctx.open(e.currentTarget, cardMenu(l)); }}>⋯</button>
                </div>
                <div className="ld-card__co">{l.company || '—'}</div>
                <div className="ld-card__meta">
                  <span className={`ld-score ${scoreCls(l.score)}`}>{l.score}</span>
                  <span className="ld-card__src">{l.source?.replace('_', ' ')}</span>
                  {l.assignee ? <span className="ld-av" title={l.assignee.name}>{initials(l.assignee.name)}</span> : <span className="ld-av ld-av--none" title="Unassigned">—</span>}
                </div>
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

/* ---------------- drawer ---------------- */
function LeadDrawer({ lead, onClose, actions, canMove, canAssign, canDelete }) {
  const [tab, setTab] = useState('overview');
  useEffect(() => setTab('overview'), [lead?.id]);
  if (!lead) return null;

  const timeline = [
    lead.converted_at && { t: 'Converted', at: lead.converted_at },
    lead.qualified_at && { t: 'Qualified', at: lead.qualified_at },
    lead.contacted_at && { t: 'Contacted', at: lead.contacted_at },
    lead.created_at && { t: 'Lead captured', at: lead.created_at },
  ].filter(Boolean);

  const nextStage = { new: 'contacted', contacted: 'qualified', qualified: 'converted' }[lead.status];

  const tabs = [{ id: 'overview', label: 'Overview' }, { id: 'activity', label: `Activity · ${timeline.length}` }];

  return (
    <WbDrawer
      open onClose={onClose} ariaLabel={`Lead — ${lead.name || lead.email}`}
      tabs={tabs} activeTab={tab} onTab={setTab}
      head={(
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(lead.name || lead.email)}</div>
            <div>
              <div className="sc-dr-title">{lead.name || '—'}</div>
              <div className="sc-dr-code">{lead.email}</div>
            </div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Stage</div><div className="v">{STATUS[lead.status]?.label ?? lead.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">Score</div><div className="v">{lead.score}</div></div>
            <div className="sc-dr-kpi"><div className="l">Owner</div><div className="v">{lead.assignee?.name?.split(' ')[0] ?? '—'}</div></div>
          </div>
          <div className="sc-dr-acts">
            {canMove && nextStage && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.move(lead, nextStage)}>Advance to {STATUS[nextStage].label}</button>}
            {canAssign && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.assign(lead)}>Assign</button>}
            {canMove && lead.status !== 'lost' && lead.status !== 'converted' && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.lost(lead)}>Mark lost</button>}
            {actions.canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.edit(lead)}>Edit</button>}
          </div>
        </>
      )}
    >
      {tab === 'overview' && (
        <div>
          <div className="ld-gauge">
            <Donut segments={[{ color: STATUS[lead.status]?.color ?? 'var(--aeos-primary)', value: lead.score }, { color: 'var(--aeos-border)', value: Math.max(0, 100 - lead.score) }]} centerValue={String(lead.score)} centerLabel="/ 100" size={92} thickness={10} />
            <div className="ld-gauge__txt">
              <div className={`ld-score ${scoreCls(lead.score)}`} style={{ fontSize: 13 }}>{lead.score >= 70 ? 'High value' : lead.score >= 40 ? 'Warm' : 'Cold'}</div>
              <div className="pc-drow__k" style={{ marginTop: 6 }}>Interest: {INTEREST[lead.interest] ?? '—'}</div>
            </div>
          </div>
          <div className="pc-drow"><span className="pc-drow__k">Company</span><span className="pc-drow__v">{lead.company ?? '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Phone</span><span className="pc-drow__v">{lead.phone ?? '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Country</span><span className="pc-drow__v">{lead.country ?? '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Source</span><span className="pc-drow__v">{lead.source?.replace('_', ' ')}{lead.source_detail ? ` · ${lead.source_detail}` : ''}</span></div>
          {Array.isArray(lead.interests) && lead.interests.length > 0 && (
            <div className="pc-drow"><span className="pc-drow__k">Interested in</span><span className="pc-drow__v"><span className="ld-tags">{lead.interests.map((t) => <span key={t} className="ld-tag">{t}</span>)}</span></span></div>
          )}
          {lead.utm && Object.keys(lead.utm).length > 0 && (
            <div className="pc-drow"><span className="pc-drow__k">UTM</span><span className="pc-drow__v" style={{ fontFamily: 'var(--aeos-font-mono)', fontSize: 11 }}>{[lead.utm.source, lead.utm.medium, lead.utm.campaign].filter(Boolean).join(' / ')}</span></div>
          )}
          <div className="pc-drow"><span className="pc-drow__k">Created</span><span className="pc-drow__v">{fmtDate(lead.created_at)}</span></div>
          {lead.lost_reason && <div className="tn-danger-note" style={{ marginTop: 'var(--aeos-space-3)' }}>Lost — {lead.lost_reason}</div>}
          {lead.notes && <><div className="sc-dr-sec">Notes</div><div className="ld-notes">{lead.notes}</div></>}
        </div>
      )}
      {tab === 'activity' && (
        timeline.length === 0 ? <div className="wb-empty">No recorded activity yet.</div>
          : <ul className="sc-tl">{timeline.map((e, i) => <li key={i}>{e.t}<span className="when">{fmtDate(e.at)}</span></li>)}</ul>
      )}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Leads({ overview }) {
  const toast = useToast();
  const o = overview ?? {};
  const list = useMemo(() => o.leads ?? [], [o.leads]);
  const s = o.stats ?? {};
  const sp = o.sparks ?? {};

  const canView = useHRMAC('lead-management.all-leads.view');
  const canCreate = useHRMAC('lead-management.all-leads.create');
  const canEdit = useHRMAC('lead-management.all-leads.update');
  const canDelete = useHRMAC('lead-management.all-leads.delete');
  const canAssign = useHRMAC('lead-management.all-leads.assign');
  const canMove = useHRMAC('lead-management.pipeline.move');

  const [drawer, setDrawer] = useState(null);
  const [formLead, setFormLead] = useState(undefined); // undefined=closed, null=new, obj=edit
  const [assignTarget, setAssignTarget] = useState(null); // {ids:[], count}
  const [lostTarget, setLostTarget] = useState(null); // {ids:[], count}
  const ctx = useCtxMenu();

  /* ------- actions (JSON endpoints → reload overview) ------- */
  const move = (l, status) => api('PUT', `/leads/${l.id}/status`, { status })
    .then(() => { toast.success(`${l.name || 'Lead'} → ${STATUS[status].label}.`); reload(); })
    .catch((e) => toast.error(e.message));
  const remove = (l) => { if (window.confirm(`Delete ${l.name || l.email}? This cannot be undone.`)) api('DELETE', `/leads/${l.id}`).then(() => { toast.success('Lead deleted.'); reload(); }).catch((e) => toast.error(e.message)); };
  const doAssign = (uid) => {
    const t = assignTarget; if (!t) return;
    const req = t.ids.length === 1
      ? api('POST', `/leads/${t.ids[0]}/assign`, { user_id: uid })
      : api('POST', '/leads/bulk', { action: 'assign', lead_ids: t.ids, user_id: uid });
    req.then(() => { setAssignTarget(null); toast.success('Assigned.'); reload(); }).catch((e) => { setAssignTarget(null); toast.error(e.message); });
  };
  const doLost = (reason) => {
    const t = lostTarget; if (!t) return;
    const req = t.ids.length === 1
      ? api('PUT', `/leads/${t.ids[0]}/status`, { status: 'lost', reason })
      : api('POST', '/leads/bulk', { action: 'lost', lead_ids: t.ids, reason });
    req.then(() => { setLostTarget(null); toast.success('Marked lost.'); reload(); }).catch((e) => { setLostTarget(null); toast.error(e.message); });
  };
  const bulkStage = (action) => {
    const ids = wb.selectedRows.map((r) => r.id);
    api('POST', '/leads/bulk', { action, lead_ids: ids }).then((j) => { toast.success(j.message || 'Done.'); wb.clearSelection(); reload(); }).catch((e) => toast.error(e.message));
  };
  const bulkDelete = () => {
    if (!window.confirm(`Delete ${wb.selection.size} selected lead(s)? This cannot be undone.`)) return;
    const ids = wb.selectedRows.map((r) => r.id);
    api('POST', '/leads/bulk', { action: 'delete', lead_ids: ids }).then((j) => { toast.success(j.message || 'Deleted.'); wb.clearSelection(); reload(); }).catch((e) => toast.error(e.message));
  };

  const actions = {
    move, remove, canEdit,
    assign: (l) => setAssignTarget({ ids: [l.id], count: 1 }),
    lost: (l) => setLostTarget({ ids: [l.id], count: 1 }),
    edit: (l) => { setDrawer(null); setFormLead(l); },
  };

  /* ------- workbench ------- */
  const wb = useWorkbench({
    rows: list,
    getId: (r) => r.id,
    searchText: (r) => `${r.name ?? ''} ${r.email} ${r.company ?? ''} ${r.source} ${r.status}`,
    views: [
      { id: 'all', label: 'All leads' },
      { id: 'open', label: 'Open', test: (r) => ['new', 'contacted', 'qualified'].includes(r.status) },
      { id: 'hot', label: 'Hot (≥70)', test: (r) => r.score >= 70 && ['new', 'contacted', 'qualified'].includes(r.status) },
      { id: 'unassigned', label: 'Unassigned', test: (r) => !r.assigned_to && r.status !== 'converted' && r.status !== 'lost' },
      { id: 'qualified', label: 'Qualified', test: (r) => r.status === 'qualified' },
      { id: 'converted', label: 'Converted', test: (r) => r.status === 'converted' },
      { id: 'lost', label: 'Lost', test: (r) => r.status === 'lost' },
    ],
    facets: {
      status: { value: 'all', test: (r, v) => r.status === v },
      source: { value: 'all', test: (r, v) => r.source === v },
      assignee: { value: 'all', test: (r, v) => (v === '__none' ? !r.assigned_to : String(r.assigned_to) === v) },
      interest: { value: 'all', test: (r, v) => r.interest === v },
    },
    sortKey: 'score',
    sortVal: (r, k) => (k === 'score' ? r.score : k === 'last_activity_at' || k === 'created_at' ? new Date(r[k] ?? 0).getTime() : String(r[k] ?? '')),
    perPage: 12,
    storageKey: 'platform.leads',
  });

  useEffect(() => { wb.toggleSort('score'); /* default desc by score */ }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const rowMenu = (r) => [
    ...(canView ? [{ label: 'Open', onClick: () => setDrawer(r) }] : []),
    ...(canMove && r.status === 'new' ? [{ label: 'Mark contacted', onClick: () => move(r, 'contacted') }] : []),
    ...(canMove && r.status === 'contacted' ? [{ label: 'Qualify', onClick: () => move(r, 'qualified') }] : []),
    ...(canMove && r.status === 'qualified' ? [{ label: 'Convert', onClick: () => move(r, 'converted') }] : []),
    ...(canAssign ? [{ label: 'Assign…', onClick: () => setAssignTarget({ ids: [r.id], count: 1 }) }] : []),
    ...(canEdit ? [{ label: 'Edit…', onClick: () => setFormLead(r) }] : []),
    ...(canMove && r.status !== 'lost' && r.status !== 'converted' ? [{ label: 'Mark lost…', onClick: () => setLostTarget({ ids: [r.id], count: 1 }) }] : []),
    ...(canDelete ? ['sep', { label: 'Delete', danger: true, onClick: () => remove(r) }] : []),
  ];

  const columns = [
    { key: 'name', label: 'Lead', sortable: true, render: (r) => (
      <div className="pc-mrow"><div className="sc-av">{initials(r.name || r.email)}</div><div><div className="pc-mname">{r.name || '—'}</div><div className="sc-kind">{r.email}</div></div></div>
    ) },
    { key: 'company', label: 'Company', hideSm: true, sortable: true, render: (r) => r.company || <span className="pc-free">—</span> },
    { key: 'source', label: 'Source', hideSm: true, render: (r) => <span className="ld-srcchip">{r.source?.replace('_', ' ')}</span> },
    { key: 'status', label: 'Stage', sortable: true, render: (r) => <span className={`pc-chip ${STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: STATUS[r.status]?.color }} />{STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'score', label: 'Score', align: 'r', sortable: true, render: (r) => <span className={`ld-score ${scoreCls(r.score)}`}>{r.score}</span> },
    { key: 'assignee', label: 'Owner', hideSm: true, render: (r) => (r.assignee ? <span className="ld-owner"><span className="ld-av">{initials(r.assignee.name)}</span>{r.assignee.name?.split(' ')[0]}</span> : <span className="pc-free">Unassigned</span>) },
    { key: 'last_activity_at', label: 'Activity', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{ago(r.last_activity_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.name || r.email}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button> },
  ];

  /* ------- KPIs ------- */
  const kpis = [
    { label: 'Total leads', value: s.total ?? 0, delta: `${s.open ?? 0} open in pipeline`, up: true, spark: sp.total, color: 'var(--aeos-primary)' },
    { label: 'New', value: s.new ?? 0, delta: 'awaiting first contact', spark: sp.new, color: 'var(--aeos-primary)' },
    { label: 'Qualified', value: s.qualified ?? 0, delta: 'sales-ready', up: true, spark: sp.qualified, color: '#a78bfa' },
    { label: 'Conversion rate', value: `${s.conversion_rate ?? 0}%`, delta: `${s.converted ?? 0} converted`, up: (s.conversion_rate ?? 0) > 0, spark: sp.converted, color: 'var(--aeos-success)' },
    { label: 'Avg score', value: s.avg_score ?? 0, delta: 'across all leads', spark: sp.total, color: 'var(--aeos-warning)' },
    { label: 'Hot leads', value: s.hot ?? 0, delta: `${s.unassigned ?? 0} unassigned`, up: (s.hot ?? 0) > 0, spark: sp.hot, color: 'var(--aeos-danger)' },
  ];

  /* ------- source donut + score bars + funnel ------- */
  const sources = o.sources ?? [];
  const scoreDist = o.scoreDist ?? [];
  const scoreMax = Math.max(1, ...scoreDist.map((b) => b.count));
  const funnel = o.funnel ?? [];
  const funMax = Math.max(1, ...funnel.map((f) => f.count));
  const trend = o.trend ?? {};

  const exportCsv = () => {
    const header = 'name,email,company,source,status,score,owner,country,created';
    const lines = list.map((r) => [r.name ?? '', r.email, r.company ?? '', r.source, r.status, r.score, r.assignee?.name ?? '', r.country ?? '', r.created_at ?? '']
      .map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `leads-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc ld">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Growth &amp; Marketing · Lead Management</div>
          <h1 className="pc-title">Leads</h1>
          <div className="pc-sub">The full prospect-to-tenant pipeline — capture, score, qualify, assign and convert. Drag cards across stages, work the queues, and act on every lead without leaving the console.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={(e) => ctx.open(e.currentTarget, [
            { label: 'Export CSV — all leads', onClick: exportCsv },
            { label: 'Print this view', onClick: () => window.print() },
          ])}>{Glyph.export}<span>Export</span></button>
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setFormLead(null)}>{Glyph.plus}<span>New lead</span></button>}
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color} /></div>}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* pipeline board */}
      <Card><CardBody>
        <div className="pc-panel-h">
          <div><h2 className="pc-panel-h__title">Pipeline</h2><div className="pc-panel-h__sub">{canMove ? 'Drag a card to move its stage · click to open' : 'Click a card to open'}</div></div>
          <span className="sc-badge sc-badge--ok">{s.open ?? 0} open</span>
        </div>
        <PipelineBoard list={list} canMove={canMove} onOpen={setDrawer} onMove={move} ctx={ctx} actions={actions} />
      </CardBody></Card>

      {/* analytics band */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Acquisition trend</h2><div className="pc-panel-h__sub">New vs converted — last 6 months</div></div></div>
          <AreaTrend
            series={[
              { key: 'created', label: 'New leads', color: 'var(--aeos-primary)', values: trend.created ?? [] },
              { key: 'converted', label: 'Converted', color: 'var(--aeos-success)', values: trend.converted ?? [] },
            ]}
            labels={trend.labels ?? []}
            height={190}
            ariaLabel="Lead acquisition trend"
          />
          <div className="tn-trend-foot">
            <span className="li"><span className="d" style={{ background: 'var(--aeos-primary)' }} />New <b>{(trend.created ?? []).reduce((a, b) => a + b, 0)}</b></span>
            <span className="li"><span className="d" style={{ background: 'var(--aeos-success)' }} />Converted <b>{(trend.converted ?? []).reduce((a, b) => a + b, 0)}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Lead sources</h2><div className="pc-panel-h__sub">Where prospects come from</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={sources.map((src, i) => ({ color: SOURCE_COLORS[i % SOURCE_COLORS.length], value: src.count }))} centerValue={String(s.total ?? 0)} centerLabel="leads" size={112} />
            <div className="sc-dl">
              {sources.map((src, i) => (
                <button key={src.source} type="button" className="li" onClick={() => wb.setFacet('source', wb.facetValues.source === src.source ? 'all' : src.source)}>
                  <span className="d" style={{ background: SOURCE_COLORS[i % SOURCE_COLORS.length] }} />{src.label}<b>{src.count}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Score &amp; funnel</h2><div className="pc-panel-h__sub">Distribution and conversion</div></div></div>
          <div className="ld-scorebars">
            {scoreDist.map((b) => (
              <div key={b.label} className="ld-scorebar">
                <span className="ld-scorebar__cap">{b.label}</span>
                <span className="ld-scorebar__track"><span className="ld-scorebar__fill" style={{ width: `${Math.max((b.count / scoreMax) * 100, 3)}%` }} /></span>
                <span className="ld-scorebar__n">{b.count}</span>
              </div>
            ))}
          </div>
          <div className="ld-funnel">
            {funnel.map((f) => (
              <div key={f.status} className="ld-funnel__row">
                <span className="ld-funnel__cap">{f.label}</span>
                <span className="ld-funnel__bar" style={{ width: `${Math.max((f.count / funMax) * 100, 6)}%`, background: STATUS[f.status]?.color }}>{f.count}</span>
              </div>
            ))}
          </div>
        </CardBody></Card>
      </div>

      {/* workbench */}
      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search name, email or company…" ariaLabel="Search leads" />
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Stage filter">
            <option value="all">All stages</option>
            {STAGES.map((k) => <option key={k} value={k}>{STATUS[k].label}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.source} onChange={(e) => wb.setFacet('source', e.target.value)} aria-label="Source filter">
            <option value="all">Any source</option>
            {Object.entries(o.sourceOptions ?? {}).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.assignee} onChange={(e) => wb.setFacet('assignee', e.target.value)} aria-label="Owner filter">
            <option value="all">Any owner</option>
            <option value="__none">Unassigned</option>
            {(o.assignees ?? []).map((u) => <option key={u.id} value={String(u.id)}>{u.name || u.email}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.interest} onChange={(e) => wb.setFacet('interest', e.target.value)} aria-label="Interest filter">
            <option value="all">Any interest</option>
            <option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option>
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>

        <WbViews wb={wb} />

        <WbBulkBar wb={wb}>
          {canAssign && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setAssignTarget({ ids: wb.selectedRows.map((r) => r.id), count: wb.selection.size })}>Assign…</button>}
          {canMove && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkStage('contacted')}>Mark contacted</button>}
          {canMove && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkStage('qualified')}>Qualify</button>}
          {canMove && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setLostTarget({ ids: wb.selectedRows.map((r) => r.id), count: wb.selection.size })}>Mark lost…</button>}
          <button type="button" className="pc-btn pc-btn--sm" onClick={exportCsv}>Export</button>
          {canDelete && <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={bulkDelete}>Delete</button>}
        </WbBulkBar>

        <WbTable
          wb={wb}
          columns={columns}
          selectable={canAssign || canMove || canDelete}
          onRowClick={setDrawer}
          rowAriaLabel={(r) => `${r.name || r.email}, ${STATUS[r.status]?.label}`}
          empty={<>No leads match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>}
        />
        <WbFooter wb={wb} perOptions={[12, 25, 50]} />
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawer && <LeadDrawer lead={list.find((l) => l.id === drawer.id) ?? drawer} onClose={() => setDrawer(null)} actions={{ ...actions, move: (l, st) => { move(l, st); }, assign: (l) => { setDrawer(null); setAssignTarget({ ids: [l.id], count: 1 }); }, lost: (l) => { setDrawer(null); setLostTarget({ ids: [l.id], count: 1 }); } }} canMove={canMove} canAssign={canAssign} canDelete={canDelete} />}
      {formLead !== undefined && <LeadFormModal lead={formLead} sourceOptions={o.sourceOptions} onClose={() => setFormLead(undefined)} onDone={(msg) => { setFormLead(undefined); toast.success(msg); reload(); }} />}
      {assignTarget && <AssignModal assignees={o.assignees} count={assignTarget.count} onClose={() => setAssignTarget(null)} onPick={doAssign} />}
      {lostTarget && <LostModal count={lostTarget.count} onClose={() => setLostTarget(null)} onConfirm={doLost} />}
    </div>
  );
}

Leads.layout = (page) => (
  <App title="Leads" railTitle="Growth &amp; Marketing" rail={<LeadsRail overview={page.props.overview} />}>
    {page}
  </App>
);
