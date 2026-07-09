import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, Donut,
  useWorkbench,
  WbToolbar, WbSearch, WbViews, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './auditlog.css';

const svg = (p) => (<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>);
const Glyph = {
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5M4 21h16" /></>),
  dash: svg(<><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z" /></>),
  access: svg(<><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" /><circle cx="12" cy="12" r="3" /></>),
};
const CAT_LABEL = { auth: 'Authentication', billing: 'Billing', security: 'Access/Security', tenants: 'Tenants', other: 'Other' };
const SEV_LABEL = { info: 'Info', warn: 'Warning', crit: 'Critical' };
const CAT_COLOR = { auth: 'var(--aeos-primary)', billing: 'var(--aeos-tertiary, var(--aeos-primary))', security: 'var(--aeos-danger)', tenants: 'var(--aeos-success)', other: 'var(--aeos-text-muted)' };
const initials = (n) => (n || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(String(s).replace(' ', 'T')).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch { return s; } };
const jsonStr = (v) => { try { return typeof v === 'string' ? v : JSON.stringify(v, null, 2); } catch { return String(v); } };

/* ---------------- rail ---------------- */
function AuditRail({ kpis }) {
  const k = kpis ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Activity</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Events (30d)</span><b>{k.events_30d ?? 0}</b></div>
          <div className="pc-rail__row"><span>Today</span><b>{k.today ?? 0}</b></div>
          <div className="pc-rail__row"><span>Actors</span><b>{k.actors ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Security</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Security events</span><b>{k.security ?? 0}</b></div>
          <div className="pc-rail__row"><span>Impersonations</span><b>{k.impersonations ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/access-logs')}>{Glyph.access}<span>Access logs</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/dashboard')}>{Glyph.dash}<span>Dashboard</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function DetailDrawer({ row, onClose }) {
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    setDetail(null); setFailed(false);
    const ac = new AbortController();
    fetch(`/audit-logs/${row.id}`, { headers: { Accept: 'application/json' }, signal: ac.signal })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status))))).then(setDetail)
      .catch((e) => { if (e.name !== 'AbortError') setFailed(true); });
    return () => ac.abort();
  }, [row.id]);
  const d = detail ?? row;
  const changed = detail?.changed_fields;
  const before = detail?.before_state;
  const after = detail?.after_state;
  const hasDiff = (before && Object.keys(before).length) || (after && Object.keys(after).length);

  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Audit event — ${row.event_type}`}
      head={<>
        <div className="sc-dr-top">
          <span className={`al-sev al-sev--${row.severity}`} style={{ width: 12, height: 12 }} />
          <div><div className="sc-dr-title al-evt" style={{ fontSize: 'var(--aeos-text-base)' }}>{row.event_type}</div><div className="sc-dr-code">{fmtDate(row.at)}</div></div>
          <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
        </div>
        <div className="sc-dr-kpis">
          <div className="sc-dr-kpi"><div className="l">Category</div><div className="v">{CAT_LABEL[row.category] ?? row.category}</div></div>
          <div className="sc-dr-kpi"><div className="l">Severity</div><div className="v">{SEV_LABEL[row.severity] ?? row.severity}</div></div>
          <div className="sc-dr-kpi"><div className="l">Actor</div><div className="v">{row.actor}</div></div>
        </div>
      </>}
    >
      <div className="pc-drow"><span className="pc-drow__k">Action</span><span className="pc-drow__v">{d.action ?? '—'}</span></div>
      <div className="pc-drow"><span className="pc-drow__k">Subject</span><span className="pc-drow__v">{d.subject ?? '—'}{d.subject_id ? ` · #${d.subject_id}` : ''}</span></div>
      <div className="pc-drow"><span className="pc-drow__k">Description</span><span className="pc-drow__v">{d.description ?? '—'}</span></div>
      <div className="pc-drow"><span className="pc-drow__k">IP address</span><span className="pc-drow__v al-mono">{d.actor_ip ?? '—'}</span></div>
      {detail?.http_method && <div className="pc-drow"><span className="pc-drow__k">Request</span><span className="pc-drow__v al-mono">{detail.http_method} {detail.url}</span></div>}
      {detail?.session_id && <div className="pc-drow"><span className="pc-drow__k">Session</span><span className="pc-drow__v al-mono">{String(detail.session_id).slice(0, 10)}…</span></div>}
      {detail?.user_agent && <div className="pc-drow"><span className="pc-drow__k">User agent</span><span className="pc-drow__v al-mono" style={{ fontSize: 10 }}>{detail.user_agent}</span></div>}

      {hasDiff && (
        <>
          <div className="sc-dr-sec">Changed state</div>
          <div className="al-dr-diff">
            {Object.entries(before || {}).map(([kk, v]) => <div className="del" key={`b-${kk}`}>- {kk}: {jsonStr(v)}</div>)}
            {Object.entries(after || {}).map(([kk, v]) => <div className="add" key={`a-${kk}`}>+ {kk}: {jsonStr(v)}</div>)}
          </div>
        </>
      )}
      {!hasDiff && Array.isArray(changed) && changed.length > 0 && (
        <>
          <div className="sc-dr-sec">Changed fields</div>
          <div className="al-dr-json">{changed.join(', ')}</div>
        </>
      )}
      {detail?.metadata && (
        <>
          <div className="sc-dr-sec">Metadata</div>
          <div className="al-dr-json">{jsonStr(detail.metadata)}</div>
        </>
      )}
      {failed && <div className="pc-sub" style={{ marginTop: 'var(--aeos-space-3)' }}>Could not load the full record — summary above is still accurate.</div>}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function AuditLog({ overview }) {
  const o = overview ?? {};
  const k = o.kpis ?? {};
  const trend = o.trend ?? { labels: [], counts: [] };
  const categories = o.categories ?? [];
  const topActors = o.top_actors ?? [];
  const eventTypes = o.event_types ?? [];
  const stream = useMemo(() => o.stream ?? [], [o.stream]);
  const [drawerRow, setDrawerRow] = useState(null);

  const kpis = [
    { label: 'Events (30d)', value: k.events_30d ?? 0, delta: `${k.total ?? 0} all-time` },
    { label: 'Today', value: k.today ?? 0, delta: 'so far' },
    { label: 'Actors', value: k.actors ?? 0, delta: 'distinct' },
    { label: 'Event types', value: k.event_types ?? 0, delta: 'across 5 categories' },
    { label: 'Security events', value: k.security ?? 0, delta: 'failed / impersonation / suspend', cls: (k.security ?? 0) > 0 ? 'warn' : 'up' },
    { label: 'Impersonations', value: k.impersonations ?? 0, delta: 'admin → tenant sessions', cls: (k.impersonations ?? 0) > 0 ? 'warn' : '' },
  ];

  const catTotal = categories.reduce((a, c) => a + c.count, 0);
  const actorMax = Math.max(1, ...topActors.map((a) => a.count));

  const columns = [
    { key: 'severity', label: '', width: 22, render: (r) => <span className={`al-sev al-sev--${r.severity}`} /> },
    { key: 'event_type', label: 'Event', sortable: true, render: (r) => (
      <div className="al-evtcell"><span className="al-evt">{r.event_type}</span><span className={`al-cat al-cat--${r.category}`}>{CAT_LABEL[r.category] ?? r.category}</span></div>) },
    { key: 'actor', label: 'Actor', sortable: true, render: (r) => <span>{r.actor}</span> },
    { key: 'subject', label: 'Subject', hideSm: true, render: (r) => <span className="al-mono">{r.subject}</span> },
    { key: 'description', label: 'Description', hideSm: true, render: (r) => <span className="al-desc">{r.description}</span> },
    { key: 'at', label: 'When', align: 'r', sortable: true, render: (r) => <span className="sc-kind">{fmtDate(r.at)}</span> },
  ];

  const wb = useWorkbench({
    rows: stream,
    getId: (r) => r.id,
    searchText: (r) => `${r.event_type} ${r.actor} ${r.subject} ${r.description}`,
    views: [
      { id: 'all', label: 'All events' },
      { id: 'security', label: 'Security', test: (r) => r.severity !== 'info' },
      { id: 'auth', label: 'Authentication', test: (r) => r.category === 'auth' },
      { id: 'billing', label: 'Billing', test: (r) => r.category === 'billing' },
      { id: 'tenants', label: 'Tenants', test: (r) => r.category === 'tenants' },
    ],
    facets: {
      category: { value: 'all', test: (r, v) => r.category === v },
      actor: { value: 'all', test: (r, v) => r.actor === v },
      severity: { value: 'all', test: (r, v) => r.severity === v },
      event_type: { value: 'all', test: (r, v) => r.event_type === v },
    },
    sortKey: 'at', sortVal: (r, key) => String(r[key] ?? ''),
    perPage: 15, storageKey: 'platform.auditlog',
  });

  const actorOptions = useMemo(() => [...new Set(stream.map((r) => r.actor))].filter(Boolean).sort(), [stream]);

  return (
    <div className="pc alx">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Audit Logs</div>
          <h1 className="pc-title">Audit Logs</h1>
          <div className="pc-sub">A tamper-evident record of every privileged action — who did what, to which record, from where, and exactly what changed. Filter, investigate and export for compliance.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => { window.location.href = route('platform.admin.audit-logs.export'); }}>{Glyph.export}<span>Export CSV</span></button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody><div className="pc-kpi">
            <div className="pc-kpi__label">{c.label}</div>
            <div className="pc-kpi__value">{c.value}</div>
            <div className={`pc-kpi__delta${c.cls === 'up' ? ' pc-kpi__delta--up' : ''}${c.cls === 'warn' ? ' pc-kpi__delta--warn' : ''}`}>{c.delta}</div>
          </div></CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="al-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Activity</h2><div className="pc-panel-h__sub">Events per day — 14 days</div></div></div>
          <AreaTrend series={[{ key: 'events', label: 'Events', color: 'var(--aeos-primary)', values: trend.counts }]} labels={trend.labels} height={190} ariaLabel="Audit events per day" empty="No events in this window." />
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">By category</h2><div className="pc-panel-h__sub">Click to filter the stream</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={categories.map((c) => ({ color: CAT_COLOR[c.key] ?? 'var(--aeos-text-muted)', value: c.count }))} centerValue={`${catTotal}`} centerLabel="events" size={116} />
            <div className="sc-dl">
              {categories.map((c) => (
                <button key={c.key} type="button" className="li" onClick={() => wb.setFacet('category', wb.facetValues.category === c.key ? 'all' : c.key)}>
                  <span className="d" style={{ background: CAT_COLOR[c.key] ?? 'var(--aeos-text-muted)' }} />{c.label}<b>{c.count}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Top actors</h2><div className="pc-panel-h__sub">Most active this period</div></div></div>
          <div className="al-lb">
            {topActors.map((a) => (
              <button key={a.actor} type="button" className="al-lbrow" style={{ background: 'none', border: 0, borderBottom: 'var(--aeos-border-width) solid var(--aeos-border-subtle)', cursor: 'pointer', textAlign: 'left', width: '100%' }} onClick={() => wb.setFacet('actor', wb.facetValues.actor === a.actor ? 'all' : a.actor)}>
                <span className="sc-av">{initials(a.actor)}</span>
                <span className="al-lb__n">{a.actor}</span>
                <span className="al-lb__bar"><i style={{ width: `${(a.count / actorMax) * 100}%` }} /></span>
                <span className="al-lb__v">{a.count}</span>
              </button>
            ))}
          </div>
        </CardBody></Card>
      </div>

      {/* audit stream */}
      <Card><CardBody>
        <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Audit stream</h2><div className="pc-panel-h__sub">Newest first — {stream.length} events</div></div></div>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search event, actor, subject or description…" ariaLabel="Search audit log" />
          <select className="pc-input sc-statusfilter" value={wb.facetValues.category} onChange={(e) => wb.setFacet('category', e.target.value)} aria-label="Category filter">
            <option value="all">All categories</option>
            {Object.entries(CAT_LABEL).map(([kk, v]) => <option key={kk} value={kk}>{v}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.actor} onChange={(e) => wb.setFacet('actor', e.target.value)} aria-label="Actor filter">
            <option value="all">All actors</option>
            {actorOptions.map((a) => <option key={a} value={a}>{a}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.severity} onChange={(e) => wb.setFacet('severity', e.target.value)} aria-label="Severity filter">
            <option value="all">All severity</option>
            <option value="crit">Critical</option><option value="warn">Warning</option><option value="info">Info</option>
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.event_type} onChange={(e) => wb.setFacet('event_type', e.target.value)} aria-label="Event type filter">
            <option value="all">Any event type</option>
            {eventTypes.map((e) => <option key={e} value={e}>{e}</option>)}
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>
        <WbViews wb={wb} />
        <WbTable wb={wb} columns={columns} onRowClick={setDrawerRow}
          rowAriaLabel={(r) => `${r.event_type} by ${r.actor}`}
          empty={<>No events match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>} />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {drawerRow && <DetailDrawer row={drawerRow} onClose={() => setDrawerRow(null)} />}
    </div>
  );
}

AuditLog.layout = (page) => (
  <App title="Audit Logs" railTitle="Audit Logs" rail={<AuditRail kpis={page.props.overview?.kpis} />}>
    {page}
  </App>
);
