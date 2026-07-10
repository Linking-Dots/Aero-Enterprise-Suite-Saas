/**
 * Audit Logs command center — activity / model changes / access / security /
 * queue in one operating view. Overhauls the old IndexPageLayout page onto the
 * pc-* command-centre bar. Backend (AuditLogController) unchanged and fully
 * reused: server-paginated tab data (?tab=), filters, CSV export, queue
 * retry/flush — every operation HRMAC-gated. Adds a per-event detail drawer.
 */
import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardBody, WbDrawer, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';

import '../../Platform/Admin/Products/products.css';
import './audit.css';

const ic = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const I = {
  export: ic(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  trash: ic(<><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" /></>),
  retry: ic(<><path d="M3 12a9 9 0 1 0 3-6.7L3 8" /><path d="M3 3v5h5" /></>),
  doc: ic(<><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6" /></>),
};

const SECURITY_EVENTS = [['', 'All security events'], ['login_failed', 'Login failed'], ['login', 'Login success'], ['logout', 'Logout'], ['password_changed', 'Password changed'], ['mfa_failed', 'MFA failed'], ['mfa_success', 'MFA success'], ['suspicious', 'Suspicious activity'], ['impersonated', 'Impersonation'], ['permission_denied', 'Permission denied'], ['token_revoked', 'Token revoked']];
const BUSINESS_EVENTS = [['', 'All event types'], ['created', 'Created'], ['updated', 'Updated'], ['deleted', 'Deleted'], ['exported', 'Exported'], ['login', 'Login'], ['logout', 'Logout'], ['login_failed', 'Login failed'], ['impersonated', 'Impersonated']];

const severity = (ev) => {
  const s = String(ev || '').toLowerCase();
  if (/(fail|denied|deny|delet|destroy|suspicious|revoke|block|error|lock|breach)/.test(s)) return 'danger';
  if (/(updat|export|password|impersonat|chang|disable|reset|warn)/.test(s)) return 'warning';
  if (/(creat|success|login|grant|restore|enable|verif)/.test(s)) return 'success';
  return 'info';
};
const initials = (n) => (n || 'SY').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtTime = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch { return s; } };
const truncate = (s, n) => (s ? (String(s).length > n ? String(s).slice(0, n) + '…' : String(s)) : '—');
const prettyJson = (v) => { if (v == null || v === '') return null; try { return JSON.stringify(typeof v === 'string' ? JSON.parse(v) : v, null, 2); } catch { return String(v); } };

function Actor({ name, email }) {
  return (
    <div className="au-actor">
      <span className="au-av">{initials(name || email)}</span>
      <div><div className="au-actor__n">{name || 'System'}</div>{email && <div className="au-actor__e">{email}</div>}</div>
    </div>
  );
}
const Ev = ({ ev }) => <span className={`au-ev au-ev--${severity(ev)}`}>{ev || '—'}</span>;

/* ---------------- rail ---------------- */
function Rail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail au">
      <div>
        <div className="pc-panel-h__title">Audit</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Events today</span><b>{s.business_events_today ?? 0}</b></div>
          <div className="pc-rail__row"><span>Total events</span><b>{s.business_events_total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Model changes</span><b>{s.model_changes_today ?? 0}</b></div>
          <div className="pc-rail__row"><span>Sensitive access</span><b>{s.sensitive_accesses_today ?? 0}</b></div>
          <div className="pc-rail__row"><span>Active users</span><b>{s.active_users_today ?? 0}</b></div>
        </div>
      </div>
    </div>
  );
}

/* ================= page ================= */
export default function AuditLogsIndex({ stats, tab: initialTab, logs, filters }) {
  const toast = useToast();
  const canViewSecurity = useHRMAC('core.audit_logs.security_logs.view');
  const canViewQueue = useHRMAC('core.audit_logs.queue_monitor.view');
  const canExportBiz = useHRMAC('core.audit_logs.activity_logs.export');
  const canExportSecPerm = useHRMAC('core.audit_logs.security_logs.export');
  const canRetry = useHRMAC('core.audit_logs.queue_monitor.retry');
  const canFlush = useHRMAC('core.audit_logs.queue_monitor.flush');

  const [tab, setTab] = useState(initialTab || 'business');
  const [search, setSearch] = useState(filters?.search ?? '');
  const [eventType, setEvent] = useState(filters?.event_type ?? '');
  const [dateFrom, setFrom] = useState(filters?.date_from ?? '');
  const [dateTo, setTo] = useState(filters?.date_to ?? '');
  const [loading, setLoading] = useState(false);
  const [retrying, setRetrying] = useState(null);
  const [flushing, setFlushing] = useState(false);
  const [detail, setDetail] = useState(null);

  useEffect(() => { setTab(initialTab || 'business'); }, [initialTab]);
  useEffect(() => {
    const s = router.on('start', () => setLoading(true));
    const f = router.on('finish', () => setLoading(false));
    return () => { s(); f(); };
  }, []);

  const isQueue = tab === 'queues';
  const isSecurity = tab === 'security';

  const visit = (params, only = ['logs', 'filters', 'tab', 'stats']) => router.get(route('core.audit-logs.index'), params, { preserveState: true, preserveScroll: true, only });
  const currentFilters = () => { const p = {}; if (search) p.search = search; if (eventType) p.event_type = eventType; if (dateFrom) p.date_from = dateFrom; if (dateTo) p.date_to = dateTo; return p; };
  const switchTab = (next) => { setTab(next); setSearch(''); setEvent(''); setFrom(''); setTo(''); visit({ tab: next }); };
  const applyFilters = () => visit({ tab, ...currentFilters(), page: 1 });
  const resetFilters = () => { setSearch(''); setEvent(''); setFrom(''); setTo(''); visit({ tab }); };
  const goPage = (page) => visit({ tab, ...currentFilters(), page });
  const exportLogs = () => { const p = new URLSearchParams(currentFilters()).toString(); window.open(route('core.audit-logs.export') + (p ? `?${p}` : ''), '_blank'); };
  const retryJob = (id) => { setRetrying(id); router.post(route('core.audit-logs.queues.retry', id), {}, { preserveScroll: true, onSuccess: () => toast.success('Job queued for retry.'), onError: () => toast.error('Retry failed.'), onFinish: () => setRetrying(null) }); };
  const flushAll = () => { if (!window.confirm('Permanently delete ALL failed jobs? This cannot be undone.')) return; setFlushing(true); router.post(route('core.audit-logs.queues.flush'), {}, { preserveScroll: true, onSuccess: () => toast.success('Failed queue flushed.'), onError: () => toast.error('Flush failed.'), onFinish: () => setFlushing(false) }); };

  const tabs = [
    { id: 'business', label: 'Activity' },
    { id: 'model', label: 'Model changes' },
    { id: 'access', label: 'Access' },
    ...(canViewSecurity ? [{ id: 'security', label: 'Security' }] : []),
    ...(canViewQueue ? [{ id: 'queues', label: 'Queue' }] : []),
  ];

  const kpis = [
    { label: 'Events today', value: stats?.business_events_today ?? 0 },
    { label: 'Total events', value: stats?.business_events_total ?? 0 },
    { label: 'Model changes today', value: stats?.model_changes_today ?? 0 },
    { label: 'Sensitive access today', value: stats?.sensitive_accesses_today ?? 0, warn: (stats?.sensitive_accesses_today ?? 0) > 0 },
    { label: 'Active users today', value: stats?.active_users_today ?? 0 },
  ];

  const rows = logs?.data ?? [];
  const showExport = (tab === 'business' && canExportBiz) || (isSecurity && canViewSecurity && canExportSecPerm);

  // per-tab columns → [{ label, align, cell(row) }]
  const columnsFor = (t) => {
    if (t === 'queues') return [
      { label: 'ID', cell: (r) => <span className="au-mono">{r.id}</span> },
      { label: 'Queue', cell: (r) => <span className="au-ev au-ev--info">{r.queue || 'default'}</span> },
      { label: 'Job', cell: (r) => <span className="au-mono">{truncate(typeof r.payload === 'string' ? r.payload : JSON.stringify(r.payload ?? {}), 60)}</span> },
      { label: 'Exception', cell: (r) => <span className="au-sub">{truncate(r.exception, 70)}</span> },
      { label: 'Failed at', cell: (r) => <span className="au-time">{fmtTime(r.failed_at)}</span> },
      { label: '', align: 'r', cell: (r) => canRetry && <button type="button" className="pc-btn pc-btn--sm" disabled={retrying === r.id} onClick={(e) => { e.stopPropagation(); retryJob(r.id); }}>{I.retry}<span>{retrying === r.id ? '…' : 'Retry'}</span></button> },
    ];
    if (t === 'model') return [
      { label: 'Actor', cell: (r) => <Actor name={r.actor_name} email={r.actor_email} /> },
      { label: 'Event', cell: (r) => <Ev ev={r.event_type} /> },
      { label: 'Description', cell: (r) => <span className="au-sub">{truncate(r.description, 60)}</span> },
      { label: 'Subject', cell: (r) => <span className="au-mono">{r.subject_type ? `${String(r.subject_type).split('\\').pop()}#${r.subject_id}` : '—'}</span> },
      { label: 'Time', align: 'r', cell: (r) => <span className="au-time">{fmtTime(r.created_at)}</span> },
    ];
    if (t === 'access') return [
      { label: 'Accessor', cell: (r) => <Actor name={r.accessor_name} email={r.accessor_email} /> },
      { label: 'Resource', cell: (r) => <span className="au-ev au-ev--info">{r.resource_type || '—'}</span> },
      { label: 'Subject', cell: (r) => <span className="au-sub">{truncate(r.subject_label, 50)}</span> },
      { label: 'IP', cell: (r) => <span className="au-mono">{r.accessor_ip || '—'}</span> },
      { label: 'Time', align: 'r', cell: (r) => <span className="au-time">{fmtTime(r.created_at)}</span> },
    ];
    if (t === 'security') return [
      { label: 'Actor', cell: (r) => <Actor name={r.actor_name} email={r.actor_email} /> },
      { label: 'Event', cell: (r) => <Ev ev={r.event_type} /> },
      { label: 'IP', cell: (r) => <span className="au-mono">{r.actor_ip || '—'}</span> },
      { label: 'Device', cell: (r) => <span className="au-sub">{truncate(r.actor_user_agent, 44)}</span> },
      { label: 'Time', align: 'r', cell: (r) => <span className="au-time">{fmtTime(r.created_at)}</span> },
    ];
    return [
      { label: 'Actor', cell: (r) => <Actor name={r.actor_name} email={r.actor_email} /> },
      { label: 'Event', cell: (r) => <Ev ev={r.event_type} /> },
      { label: 'Action', cell: (r) => <span className="au-sub">{r.action || r.description || '—'}</span> },
      { label: 'Subject', cell: (r) => <span className="au-sub">{truncate(r.subject_label, 40)}</span> },
      { label: 'IP', cell: (r) => <span className="au-mono">{r.actor_ip || '—'}</span> },
      { label: 'Time', align: 'r', cell: (r) => <span className="au-time">{fmtTime(r.created_at)}</span> },
    ];
  };
  const columns = columnsFor(tab);

  return (
    <div className="pc au">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Workspace · Monitoring &amp; Health</div>
          <h1 className="pc-title">Audit &amp; Activity Logs</h1>
          <div className="pc-sub">Business events, model changes, sensitive-data access, security events and the failed-job queue — searchable, filterable, exportable.</div>
        </div>
        <div className="pc-actions">
          {isQueue && canFlush && <button type="button" className="pc-btn pc-btn--danger" onClick={flushAll} disabled={flushing}>{I.trash}<span>{flushing ? 'Flushing…' : 'Flush all'}</span></button>}
          {showExport && <button type="button" className="pc-btn" onClick={exportLogs}>{I.export}<span>Export CSV</span></button>}
        </div>
      </div>

      <div className="au-kpis">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi__label">{c.label}</div>
            <div className="pc-kpi__value">{c.value}</div>
            <div className={`pc-kpi__delta${c.warn ? ' pc-kpi__delta--warn' : ''}`}>{c.warn ? 'PII exposure logged' : 'last 24h'}</div>
          </CardBody></Card>
        ))}
      </div>

      <div className="au-tabs" role="tablist">
        {tabs.map((t) => (
          <button key={t.id} type="button" role="tab" aria-selected={tab === t.id} className={`au-tab${tab === t.id ? ' is-active' : ''}`} onClick={() => switchTab(t.id)}>{t.label}</button>
        ))}
      </div>

      <Card><CardBody>
        {!isQueue && (
          <div className="au-filter">
            <label className="pc-field au-filter__search"><span className="pc-field__label">Search</span>
              <input className="pc-input" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} placeholder="Actor, subject, description…" /></label>
            <label className="pc-field"><span className="pc-field__label">Event type</span>
              <select className="pc-input" value={eventType} onChange={(e) => setEvent(e.target.value)}>
                {(isSecurity ? SECURITY_EVENTS : BUSINESS_EVENTS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select></label>
            <label className="pc-field"><span className="pc-field__label">From</span><input type="date" className="pc-input" value={dateFrom} onChange={(e) => setFrom(e.target.value)} /></label>
            <label className="pc-field"><span className="pc-field__label">To</span><input type="date" className="pc-input" value={dateTo} onChange={(e) => setTo(e.target.value)} /></label>
            <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={applyFilters}>Filter</button>
            <button type="button" className="pc-btn pc-btn--sm" onClick={resetFilters}>Reset</button>
          </div>
        )}

        {rows.length === 0 ? (
          <div className="au-empty"><div className="au-empty__ico">{I.doc}</div>
            {isQueue ? 'Queue is clean — no failed jobs.' : (search || eventType || dateFrom || dateTo) ? 'No events match these filters.' : 'No events recorded yet for this view.'}
          </div>
        ) : (
          <div className="au-tablewrap">
            <table className="au-table">
              <thead><tr>{columns.map((c, i) => <th key={i} className={c.align === 'r' ? 'au-r' : undefined}>{c.label}</th>)}</tr></thead>
              <tbody>
                {rows.map((r, ri) => (
                  <tr key={r.id ?? ri} onClick={() => setDetail({ ...r, __tab: tab })}>
                    {columns.map((c, ci) => <td key={ci} className={c.align === 'r' ? 'au-r' : undefined}>{c.cell(r)}</td>)}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {logs?.last_page > 1 && (
          <div className="au-pager">
            <span>Page {logs.current_page} of {logs.last_page} · {logs.total} total</span>
            <span className="au-pager__btns">
              <button type="button" className="pc-btn pc-btn--sm" disabled={logs.current_page <= 1 || loading} onClick={() => goPage(logs.current_page - 1)}>Prev</button>
              <button type="button" className="pc-btn pc-btn--sm" disabled={logs.current_page >= logs.last_page || loading} onClick={() => goPage(logs.current_page + 1)}>Next</button>
            </span>
          </div>
        )}
      </CardBody></Card>

      {detail && <DetailDrawer row={detail} onClose={() => setDetail(null)} canRetry={canRetry} onRetry={(id) => { retryJob(id); setDetail(null); }} />}
    </div>
  );
}

/* ---------------- detail drawer ---------------- */
function DetailDrawer({ row, onClose, canRetry, onRetry }) {
  const isQueue = row.__tab === 'queues';
  const meta = prettyJson(row.metadata ?? row.properties ?? row.payload);
  const fields = isQueue
    ? [['Job ID', row.id], ['Queue', row.queue || 'default'], ['Connection', row.connection], ['Failed at', fmtTime(row.failed_at)]]
    : [
        ['Event', row.event_type], ['Actor', row.actor_name || 'System'], ['Email', row.actor_email],
        ['Action', row.action], ['Subject', row.subject_label || (row.subject_type ? `${String(row.subject_type).split('\\').pop()}#${row.subject_id}` : null)],
        ['IP address', row.actor_ip || row.accessor_ip], ['Device', row.actor_user_agent], ['Resource', row.resource_type], ['When', fmtTime(row.created_at)],
      ];
  return (
    <WbDrawer open onClose={onClose} ariaLabel="Event detail"
      head={
        <>
          <div className="au-dr-top">
            <div><div className="au-dr-title">{isQueue ? `Failed job #${row.id}` : (row.event_type || 'Event')}</div><div className="au-dr-code">{isQueue ? (row.queue || 'default') : (row.actor_name || 'System')}</div></div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          {!isQueue && <div className="au-dr-ev"><span className={`au-ev au-ev--${severity(row.event_type)}`}>{row.event_type}</span></div>}
          {isQueue && canRetry && <div className="au-dr-acts"><button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => onRetry(row.id)}>Retry job</button></div>}
        </>
      }
    >
      {row.description && <div className="au-dr-desc">{row.description}</div>}
      {fields.filter(([, v]) => v != null && v !== '').map(([k, v]) => (
        <div className="pc-drow" key={k}><span className="pc-drow__k">{k}</span><span className="pc-drow__v au-break">{v}</span></div>
      ))}
      {isQueue && row.exception && <><div className="au-dr-sec">Exception</div><pre className="au-json">{row.exception}</pre></>}
      {meta && <><div className="au-dr-sec">{isQueue ? 'Payload' : 'Metadata'}</div><pre className="au-json">{meta}</pre></>}
    </WbDrawer>
  );
}

AuditLogsIndex.layout = (page) => (
  <App title="Audit & Activity Logs" railTitle="Audit" rail={<Rail stats={page.props.stats} />}>{page}</App>
);
