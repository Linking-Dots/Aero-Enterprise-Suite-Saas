import { useEffect, useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, Donut,
  useWorkbench, useCtxMenu,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import './subscriptions.css';
import './invoices.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  subs: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
};

const STATUS_LABEL = { paid: 'Paid', open: 'Open', overdue: 'Overdue', draft: 'Draft', void: 'Void', refunded: 'Refunded' };
const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const fmtMoney2 = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const fmtK = (n) => (Math.abs(n) >= 1000 ? `$${(n / 1000).toFixed(1)}k` : fmtMoney(n));
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; }
};
const fmtDateShort = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; }
};

const post = (url, data = {}) => router.post(url, data, { preserveScroll: true });
const confirmPost = (msg, url, data = {}) => { if (window.confirm(msg)) post(url, data); };

/* ---------------- rail ---------------- */
function InvoicesRail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Collections</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Collected</span><b>{fmtMoney(s.collected)}</b></div>
          <div className="pc-rail__row"><span>Outstanding</span><b>{fmtMoney(s.outstanding)}</b></div>
          <div className="pc-rail__row"><span>Overdue</span><b>{fmtMoney(s.overdue_amt)}</b></div>
          <div className="pc-rail__row"><span>Collected rate</span><b>{s.paid_rate ?? 0}%</b></div>
          <div className="pc-rail__row"><span>Avg days to pay</span><b>{s.avg_days_to_pay ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/subscriptions')}>{Glyph.subs}<span>Subscriptions</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/dashboard')}>{Glyph.billing}<span>Billing</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- modals ---------------- */
function VoidModal({ invoice, onClose }) {
  const form = useForm({ reason: '' });
  const submit = (e) => { e.preventDefault(); form.post(`/billing/invoices/${invoice.id}/void`, { preserveScroll: true, onSuccess: onClose }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Void invoice</h2>
        <div className="pc-modal__sub">{invoice.number} · {invoice.tenant} · {fmtMoney2(invoice.total)}. Voiding cancels the amount due.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="v-reason">Reason</label>
            <textarea id="v-reason" className="pc-input" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder="Why is this invoice being voided?" />
            {form.errors.reason && <span className="pc-field__err">{form.errors.reason}</span>}
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Keep</button>
            <button type="submit" className="pc-btn pc-btn--danger" disabled={form.processing}>{form.processing ? 'Voiding…' : 'Void invoice'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

function RefundModal({ invoice, onClose }) {
  const form = useForm({ amount: Number(invoice.amount_paid || invoice.total || 0).toFixed(2), reason: '' });
  const submit = (e) => { e.preventDefault(); form.post(`/billing/invoices/${invoice.id}/refund`, { preserveScroll: true, onSuccess: onClose }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Refund invoice</h2>
        <div className="pc-modal__sub">{invoice.number} · {invoice.tenant}. Paid {fmtMoney2(invoice.amount_paid)}. Records a refund and, when full, marks the invoice refunded.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="r-amount">Amount</label>
            <input id="r-amount" type="number" step="0.01" min="0.01" max={invoice.amount_paid} className="pc-input" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
            {form.errors.amount && <span className="pc-field__err">{form.errors.amount}</span>}
          </div>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="r-reason">Reason</label>
            <textarea id="r-reason" className="pc-input" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder="Why is this being refunded?" />
            {form.errors.reason && <span className="pc-field__err">{form.errors.reason}</span>}
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--danger" disabled={form.processing}>{form.processing ? 'Refunding…' : 'Record refund'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

function GenerateModal({ subscriptions, onClose }) {
  const form = useForm({ subscription_id: '' });
  const submit = (e) => { e.preventDefault(); form.post('/billing/invoices/generate', { preserveScroll: true, onSuccess: onClose }); };
  const sub = (subscriptions ?? []).find((x) => String(x.id) === String(form.data.subscription_id));
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Generate invoice</h2>
        <div className="pc-modal__sub">Draft an invoice from an active subscription. Amount and billing period derive from the subscription's plan.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="g-sub">Subscription</label>
            <select id="g-sub" className="pc-input" value={form.data.subscription_id} onChange={(e) => form.setData('subscription_id', e.target.value)}>
              <option value="">Select a subscription…</option>
              {(subscriptions ?? []).map((x) => <option key={x.id} value={x.id}>{x.tenant} — {x.plan} ({fmtMoney(x.price)}/mo)</option>)}
            </select>
            {form.errors.subscription_id && <span className="pc-field__err">{form.errors.subscription_id}</span>}
          </div>
          {sub && (
            <div className="pc-ent">
              <div className="pc-ent__l">Summary</div>
              <div className="pc-sub">Draft invoice for {fmtMoney(sub.price)} to {sub.tenant}, due in 30 days. Audit-logged.</div>
            </div>
          )}
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing}>{form.processing ? 'Generating…' : 'Generate draft'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
const cur = (c) => (c ? String(c).toUpperCase() : 'USD');

function DetailDrawer({ row, onClose, onVoid, onRefund }) {
  const [tab, setTab] = useState('detail');
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  const ctx = useCtxMenu();

  useEffect(() => {
    setTab('detail');
    setDetail(null);
    setFailed(false);
    if (!row) return undefined;
    const ac = new AbortController();
    fetch(`/billing/invoices/detail/${row.id}`, { headers: { Accept: 'application/json' }, signal: ac.signal })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
      .then(setDetail)
      .catch((e) => { if (e.name !== 'AbortError') setFailed(true); });
    return () => ac.abort();
  }, [row?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!row) return null;
  const base = `/billing/invoices/${row.id}`;
  const unpaid = ['open', 'overdue', 'draft'].includes(row.status);

  const tabs = [
    { id: 'detail', label: 'Detail' },
    { id: 'items', label: `Line items${detail ? ` · ${detail.line_items.length}` : ''}` },
    { id: 'payments', label: 'Payments' },
    { id: 'activity', label: 'Activity' },
  ];

  const moreItems = [
    { label: 'Download PDF', onClick: () => window.open(`${base}/download`, '_blank') },
    ...(row.status === 'paid' ? [{ label: 'Refund…', onClick: () => onRefund(row) }] : []),
    ...(unpaid ? ['sep', { label: 'Void invoice…', danger: true, onClick: () => onVoid(row) }] : []),
  ];

  return (
    <WbDrawer
      open
      onClose={onClose}
      ariaLabel={`Invoice detail — ${row.number}`}
      tabs={tabs}
      activeTab={tab}
      onTab={setTab}
      head={
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(row.tenant)}</div>
            <div>
              <div className="sc-dr-title">{row.tenant}</div>
              <div className="sc-dr-code">{row.number}</div>
            </div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Total</div><div className="v">{fmtMoney2(row.total)}</div></div>
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{STATUS_LABEL[row.status] ?? row.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">{row.status === 'paid' ? 'Paid' : 'Due'}</div><div className="v">{row.status === 'paid' ? fmtDateShort(row.paid_at) : fmtDateShort(row.due_date)}</div></div>
          </div>
          <div className="sc-dr-acts">
            {unpaid && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => confirmPost(`Mark ${row.number} (${fmtMoney2(row.total)}) as paid?`, `${base}/mark-paid`)}>Mark paid</button>}
            {unpaid && <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Send ${row.number} to ${row.tenant}?`, `${base}/send`)}>Send</button>}
            {['open', 'overdue'].includes(row.status) && <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`${base}/remind`)}>Reminder</button>}
            <button type="button" className="pc-btn pc-btn--sm" onClick={(e) => ctx.open(e.currentTarget, moreItems)}>More ▾</button>
          </div>
        </>
      }
    >
      {tab === 'detail' && (
        <div>
          <div className="pc-drow"><span className="pc-drow__k">Reference</span><span className="pc-drow__v">{row.reference ?? row.number}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Billing period</span><span className="pc-drow__v">{fmtDateShort(row.period_start)} – {fmtDate(row.period_end)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Issued</span><span className="pc-drow__v">{fmtDate(row.created_at)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Due date</span><span className="pc-drow__v">{fmtDate(row.due_date)}{row.days_overdue > 0 ? ` · ${row.days_overdue}d overdue` : ''}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Subtotal</span><span className="pc-drow__v">{fmtMoney2(row.subtotal)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Discount</span><span className="pc-drow__v">−{fmtMoney2(row.discount)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Tax</span><span className="pc-drow__v">{fmtMoney2(row.tax)}{row.tax === 0 ? ' · no tax rules' : ''}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Total</span><span className="pc-drow__v pc-price">{fmtMoney2(row.total)} {cur(row.currency)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Amount paid</span><span className="pc-drow__v">{fmtMoney2(row.amount_paid)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Amount due</span><span className={`pc-drow__v${row.amount_due > 0 ? ' inv-due' : ''}`}>{fmtMoney2(row.amount_due)}</span></div>
          {row.method && <div className="pc-drow"><span className="pc-drow__k">Payment method</span><span className="pc-drow__v">{row.method}</span></div>}
        </div>
      )}
      {tab === 'items' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load line items.' : 'Loading…'}</div>
          : detail.line_items.length === 0 ? <div className="wb-empty">No line items on this invoice.</div>
            : (
              <>
                <table className="sc-minit">
                  <tbody>
                    {detail.line_items.map((l, i) => (
                      <tr key={i}>
                        <td><div className="pc-mname">{l.description}</div><div className="sc-kind">{l.type}</div></td>
                        <td className="sc-kind">{l.quantity} × {fmtMoney2(l.unit_price)}</td>
                        <td className="pc-r pc-price">{fmtMoney2(l.amount)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div className="inv-totrow"><span>Subtotal</span><span>{fmtMoney2(detail.totals.subtotal)}</span></div>
                {detail.totals.discount > 0 && <div className="inv-totrow"><span>Discount</span><span>−{fmtMoney2(detail.totals.discount)}</span></div>}
                <div className="inv-totrow"><span>Tax</span><span>{fmtMoney2(detail.totals.tax)}</span></div>
                <div className="inv-totrow inv-totrow--grand"><span>Total</span><span>{fmtMoney2(detail.totals.total)} {cur(detail.totals.currency)}</span></div>
              </>
            )
      )}
      {tab === 'payments' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load payments.' : 'Loading…'}</div>
          : detail.payments.length === 0 ? (
            <div className="wb-empty">
              No payment recorded yet.{row.amount_due > 0 ? <><br />Amount due <b className="inv-due">{fmtMoney2(row.amount_due)}</b>.</> : null}
            </div>
          ) : (
            <ul className="sc-tl">
              {detail.payments.map((p, i) => (
                <li key={i} className={p.kind === 'refund' ? 'inv-tl-refund' : 'inv-tl-pay'}>
                  {p.kind === 'refund' ? 'Refund' : 'Payment received'} — {fmtMoney2(Math.abs(p.amount))}
                  {p.method ? ` · ${p.method}` : ''}
                  <span className="when">{fmtDate(p.at)}</span>
                </li>
              ))}
            </ul>
          )
      )}
      {tab === 'activity' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load activity.' : 'Loading…'}</div>
          : detail.activity.length === 0 ? <div className="wb-empty">No recorded activity yet — lifecycle actions appear here as they happen.</div>
            : (
              <ul className="sc-tl">
                {detail.activity.map((a, i) => (
                  <li key={i}>
                    {a.detail || a.event}
                    <span className="when">{fmtDate(a.at)}{a.actor ? ` · ${a.actor}` : ''}</span>
                  </li>
                ))}
              </ul>
            )
      )}
      {ctx.element}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Index({ stats, invoices, aging, trend, sparks, subscriptions }) {
  const s = stats ?? {};
  const sp = sparks ?? {};
  const list = useMemo(() => invoices ?? [], [invoices]);

  const [voidInv, setVoidInv] = useState(null);
  const [refundInv, setRefundInv] = useState(null);
  const [generating, setGenerating] = useState(false);
  const [drawerRow, setDrawerRow] = useState(null);
  const ctx = useCtxMenu();

  const tenantOptions = useMemo(
    () => [...new Set(list.map((r) => r.tenant))].filter((t) => t && t !== '—').sort(),
    [list]
  );

  const wb = useWorkbench({
    rows: list,
    getId: (r) => r.id,
    searchText: (r) => `${r.tenant} ${r.number} ${r.reference ?? ''} ${r.status}`,
    views: [
      { id: 'all', label: 'All invoices' },
      { id: 'unpaid', label: 'Unpaid', test: (r) => ['open', 'overdue'].includes(r.status) },
      { id: 'overdue', label: 'Overdue', test: (r) => r.status === 'overdue' },
      { id: 'duesoon', label: 'Due ≤ 7 days', test: (r) => ['open', 'overdue'].includes(r.status) && r.due_date != null && (r.days_overdue > 0 || daysUntil(r.due_date) <= 7) },
      { id: 'paid', label: 'Paid', test: (r) => r.status === 'paid' },
    ],
    facets: {
      group: { value: 'all', test: (r, v) => (v === 'unpaid' ? ['open', 'overdue'].includes(r.status) : r.status === v) },
      status: { value: 'all', test: (r, v) => r.status === v },
      tenant: { value: 'all', test: (r, v) => r.tenant === v },
      aging: { value: 'all', test: (r, v) => agingBucket(r) === v },
      amount: { value: 'all', test: (r, v) => r.total >= Number(v) },
    },
    sortKey: 'created_at',
    sortVal: (r, k) => (k === 'total' || k === 'amount_due') ? r[k] : String(r[k] ?? ''),
    perPage: 12,
    storageKey: 'platform.invoices',
  });

  const total = Math.max(1, s.total ?? 1);
  const donutStatuses = [
    ['paid', 'var(--aeos-success)'],
    ['open', 'var(--aeos-primary)'],
    ['overdue', 'var(--aeos-danger)'],
    ['void', 'var(--aeos-text-muted)'],
  ];

  const kpis = [
    { label: 'Collected', value: fmtMoney(s.collected), delta: `${s.paid ?? 0} invoices paid`, up: true, spark: sp.collected, color: 'var(--aeos-success)' },
    { label: 'Outstanding', value: fmtMoney(s.outstanding), delta: `${s.open ?? 0} open · ${s.overdue ?? 0} overdue`, spark: sp.outstanding, color: 'var(--aeos-primary)' },
    { label: 'Overdue', value: fmtMoney(s.overdue_amt), delta: `${s.overdue ?? 0} invoices past due`, down: (s.overdue ?? 0) > 0, up: (s.overdue ?? 0) === 0, spark: sp.overdue, color: 'var(--aeos-danger)' },
    { label: 'Collected rate', value: `${s.paid_rate ?? 0}%`, delta: `${s.paid ?? 0} of ${s.total ?? 0}`, up: true, spark: sp.paid_rate, color: 'var(--aeos-success)' },
    { label: 'Avg days to pay', value: `${s.avg_days_to_pay ?? 0}d`, delta: 'issue → payment', up: (s.avg_days_to_pay ?? 0) <= 14 },
    { label: 'Tax collected', value: fmtMoney2(s.tax_total), delta: (s.tax_total ?? 0) === 0 ? 'no tax rules configured' : 'across all invoices' },
  ];

  const agingRows = aging ?? [];
  const agingMax = Math.max(1, ...agingRows.map((b) => b.amount));

  const overdueQueue = useMemo(() => list.filter((r) => r.status === 'overdue').sort((a, b) => b.days_overdue - a.days_overdue), [list]);
  const dueSoonQueue = useMemo(() => list.filter((r) => r.status === 'open' && r.due_date != null).sort((a, b) => String(a.due_date).localeCompare(String(b.due_date))), [list]);

  const rowMenu = (r) => {
    const base = `/billing/invoices/${r.id}`;
    const dl = { label: 'Download PDF', onClick: () => window.open(`${base}/download`, '_blank') };
    if (r.status === 'paid') {
      return [dl, { label: 'Refund…', onClick: () => setRefundInv(r) }];
    }
    if (r.status === 'void' || r.status === 'refunded') {
      return [dl];
    }
    // open / overdue / draft
    return [
      { label: 'Mark paid', onClick: () => confirmPost(`Mark ${r.number} (${fmtMoney2(r.total)}) as paid?`, `${base}/mark-paid`) },
      { label: 'Send', onClick: () => confirmPost(`Send ${r.number} to ${r.tenant}?`, `${base}/send`) },
      ...(['open', 'overdue'].includes(r.status) ? [{ label: 'Send reminder', onClick: () => post(`${base}/remind`) }] : []),
      dl,
      'sep',
      { label: 'Void invoice…', danger: true, onClick: () => setVoidInv(r) },
    ];
  };

  const dueCell = (r) => {
    if (r.status === 'paid') return <span className="sc-kind">Paid {fmtDateShort(r.paid_at)}</span>;
    if (['void', 'refunded'].includes(r.status)) return <span className="sc-kind">—</span>;
    const overdue = r.days_overdue > 0;
    return <span className={`sc-renews${overdue ? ' sc-renews--due' : ''}`}>{overdue ? `${r.days_overdue}d overdue` : fmtDateShort(r.due_date)}</span>;
  };

  const columns = [
    {
      key: 'tenant', label: 'Invoice', sortable: true,
      render: (r) => (
        <div className="pc-mrow">
          <div className="sc-av">{initials(r.tenant)}</div>
          <div><div className="pc-mname">{r.tenant}</div><div className="inv-num">{r.number}</div></div>
        </div>
      ),
    },
    { key: 'period_start', label: 'Period', hideSm: true, render: (r) => <span className="sc-kind">{fmtDateShort(r.period_start)} – {fmtDateShort(r.period_end)}</span> },
    { key: 'status', label: 'Status', render: (r) => <span className={`pc-chip pc-chip--${r.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[r.status] ?? r.status}</span> },
    { key: 'total', label: 'Amount', align: 'r', sortable: true, render: (r) => <span className="pc-price">{fmtMoney2(r.total)}</span> },
    { key: 'created_at', label: 'Issued', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{fmtDateShort(r.created_at)}</span> },
    { key: 'due_date', label: 'Due', align: 'r', sortable: true, render: dueCell },
    {
      key: 'actions', label: '', align: 'r', width: 44,
      render: (r) => (
        <button type="button" className="wb-kebab" aria-label={`Actions for ${r.number}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button>
      ),
    },
  ];

  const bulkIds = () => wb.selectedRows.map((r) => r.id);
  const exportSelectedCsv = () => {
    const header = 'invoice,tenant,status,subtotal,discount,tax,total,amount_paid,amount_due,due_date,issued_at';
    const lines = wb.selectedRows.map((r) =>
      [r.number, r.tenant, r.status, r.subtotal, r.discount, r.tax, r.total, r.amount_paid, r.amount_due, r.due_date ?? '', r.created_at ?? '']
        .map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `invoices-selected-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Billing · Collections</div>
          <h1 className="pc-title">Invoices</h1>
          <div className="pc-sub">Every invoice across the platform — collected, outstanding and overdue — with AR aging, collections trend, drawer detail and full lifecycle actions in one operating view.</div>
        </div>
        <div className="pc-actions">
          <button
            type="button"
            className="pc-btn"
            onClick={(e) => ctx.open(e.currentTarget, [
              { label: 'Export CSV — all invoices', onClick: () => { window.location.href = '/billing/invoices/export'; } },
              { label: 'Print this view', onClick: () => window.print() },
            ])}
          >
            {Glyph.export}<span>Export</span>
          </button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={() => setGenerating(true)}>{Glyph.plus}<span>Generate invoice</span></button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}${c.down ? ' pc-kpi__delta--down' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && (
                <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color ?? 'var(--aeos-primary)'} /></div>
              )}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Accounts-receivable aging</h2><div className="pc-panel-h__sub">{fmtMoney(s.outstanding)} outstanding — click a bucket to filter</div></div>
          </div>
          <div className="inv-aging">
            {agingRows.map((b, i) => (
              <button
                key={b.label}
                type="button"
                className={`inv-agerow${wb.facetValues.aging === agingKey(i) ? ' is-on' : ''}`}
                onClick={() => wb.setFacet('aging', wb.facetValues.aging === agingKey(i) ? 'all' : agingKey(i))}
              >
                <span className="inv-agerow__cap">{b.label}</span>
                <span className="inv-agerow__track">
                  <span className={`inv-agerow__fill inv-age-${i}`} style={{ width: `${b.amount > 0 ? Math.max((b.amount / agingMax) * 100, 6) : 0}%` }} />
                </span>
                <span className="inv-agerow__amt">{fmtMoney(b.amount)}<small>{b.count}</small></span>
              </button>
            ))}
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Collections trend</h2><div className="pc-panel-h__sub">Billed vs collected — last 6 months</div></div>
          </div>
          <AreaTrend
            series={[
              { key: 'billed', label: 'Billed', color: 'var(--aeos-primary)', values: trend?.billed ?? [] },
              { key: 'collected', label: 'Collected', color: 'var(--aeos-success)', fill: false, values: trend?.collected ?? [] },
            ]}
            labels={trend?.labels ?? []}
            height={210}
            ariaLabel="Collections trend, billed versus collected"
          />
          <div className="sc-dl sc-dl--row">
            <span className="li"><span className="d sc-d-exp" />Billed <b>{fmtK((trend?.billed ?? []).at(-1) ?? 0)}</b></span>
            <span className="li"><span className="d sc-d-new" />Collected <b>{fmtK((trend?.collected ?? []).at(-1) ?? 0)}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Status health</h2><div className="pc-panel-h__sub">{s.total ?? 0} invoices — click a status to filter</div></div>
          </div>
          <div className="sc-donut-row">
            <Donut
              segments={donutStatuses.map(([st, color]) => ({ color, value: s[st] ?? 0 }))}
              centerValue={`${s.paid_rate ?? 0}%`}
              centerLabel="collected"
              size={112}
            />
            <div className="sc-dl">
              {donutStatuses.map(([st]) => (
                <button key={st} type="button" className="li" onClick={() => wb.setFacet('status', wb.facetValues.status === st ? 'all' : st)}>
                  <span className={`d inv-seg-${st}`} />{STATUS_LABEL[st]}<b>{s[st] ?? 0}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>
      </div>

      {/* collections queues */}
      <div className="sc-queues">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Overdue — chase now</h2><div className="pc-panel-h__sub">Past due, awaiting collection</div></div>
            <span className={`sc-badge ${overdueQueue.length ? 'sc-badge--bad' : 'sc-badge--ok'}`}>{fmtMoney(s.overdue_amt)}</span>
          </div>
          {overdueQueue.length === 0 && <div className="wb-empty">Nothing overdue — collections are healthy.</div>}
          {overdueQueue.slice(0, 4).map((r) => (
            <div key={r.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.number}</span></div>
              <span className="sc-qitem__amt">{fmtMoney2(r.total)}</span>
              <span className="sc-qitem__when sc-qitem__when--due">{r.days_overdue}d overdue</span>
              <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => confirmPost(`Mark ${r.number} as paid?`, `/billing/invoices/${r.id}/mark-paid`)}>Mark paid</button>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/invoices/${r.id}/remind`)}>Remind</button>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('overdue')}>Open overdue view →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Open — awaiting payment</h2><div className="pc-panel-h__sub">Sent, not yet paid</div></div>
            <span className={`sc-badge ${dueSoonQueue.length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{dueSoonQueue.length}</span>
          </div>
          {dueSoonQueue.length === 0 && <div className="wb-empty">No open invoices awaiting payment.</div>}
          {dueSoonQueue.slice(0, 4).map((r) => (
            <div key={r.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.number}</span></div>
              <span className="sc-qitem__amt">{fmtMoney2(r.total)}</span>
              <span className="sc-qitem__when">due {fmtDateShort(r.due_date)}</span>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/invoices/${r.id}/remind`)}>Remind</button>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('unpaid')}>Open unpaid view →</button></div>
        </CardBody></Card>
      </div>

      {/* workbench */}
      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search tenant, invoice # or reference…" ariaLabel="Search invoices" />
          <div className="pc-seg" role="group" aria-label="Invoice group">
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.group === 'all'} onClick={() => wb.setFacet('group', 'all')}>All · {s.total ?? 0}</button>
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.group === 'unpaid'} onClick={() => wb.setFacet('group', 'unpaid')}>Unpaid · {(s.open ?? 0) + (s.overdue ?? 0)}</button>
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.group === 'paid'} onClick={() => wb.setFacet('group', 'paid')}>Paid · {s.paid ?? 0}</button>
          </div>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
            <option value="all">All statuses</option>
            {Object.entries(STATUS_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.tenant} onChange={(e) => wb.setFacet('tenant', e.target.value)} aria-label="Tenant filter">
            <option value="all">Any tenant</option>
            {tenantOptions.map((t) => <option key={t} value={t}>{t}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.aging} onChange={(e) => wb.setFacet('aging', e.target.value)} aria-label="Aging filter">
            <option value="all">Any age</option>
            <option value="current">Current</option>
            <option value="b30">1–30 days</option>
            <option value="b60">31–60 days</option>
            <option value="b90">61–90 days</option>
            <option value="b90p">90+ days</option>
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.amount} onChange={(e) => wb.setFacet('amount', e.target.value)} aria-label="Amount filter">
            <option value="all">Any amount</option>
            <option value="100">≥ $100</option>
            <option value="250">≥ $250</option>
            <option value="500">≥ $500</option>
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>

        <WbViews wb={wb} />

        <WbBulkBar wb={wb}>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Send ${wb.selection.size} selected invoice(s)?`, '/billing/invoices/bulk', { action: 'send', ids: bulkIds() })}>Send</button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Mark ${wb.selection.size} selected invoice(s) as paid?`, '/billing/invoices/bulk', { action: 'mark-paid', ids: bulkIds() })}>Mark paid</button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => post('/billing/invoices/bulk', { action: 'remind', ids: bulkIds() })}>Send reminder</button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={exportSelectedCsv}>Export selected</button>
          <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => {
            const reason = window.prompt(`Void ${wb.selection.size} selected invoice(s)?\nReason:`);
            if (reason !== null) post('/billing/invoices/bulk', { action: 'void', reason: reason || 'Bulk void', ids: bulkIds() });
          }}>Void</button>
        </WbBulkBar>

        <WbTable
          wb={wb}
          columns={columns}
          selectable
          onRowClick={setDrawerRow}
          rowAriaLabel={(r) => `${r.number} — ${r.tenant}, ${STATUS_LABEL[r.status] ?? r.status}`}
          empty={
            <>
              No invoices match these filters.
              <br />
              <button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button>
            </>
          }
        />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawerRow && <DetailDrawer row={list.find((r) => r.id === drawerRow.id) ?? drawerRow} onClose={() => setDrawerRow(null)} onVoid={(r) => { setDrawerRow(null); setVoidInv(r); }} onRefund={(r) => { setDrawerRow(null); setRefundInv(r); }} />}
      {voidInv && <VoidModal invoice={voidInv} onClose={() => setVoidInv(null)} />}
      {refundInv && <RefundModal invoice={refundInv} onClose={() => setRefundInv(null)} />}
      {generating && <GenerateModal subscriptions={subscriptions} onClose={() => setGenerating(false)} />}
    </div>
  );
}

/* ---------------- helpers ---------------- */
function daysUntil(due) {
  try { return Math.ceil((new Date(String(due).replace(' ', 'T')) - new Date()) / 86400000); } catch { return 999; }
}
function agingBucket(r) {
  if (!['open', 'overdue'].includes(r.status)) return 'none';
  const d = r.days_overdue;
  if (d <= 0) return 'current';
  if (d <= 30) return 'b30';
  if (d <= 60) return 'b60';
  if (d <= 90) return 'b90';
  return 'b90p';
}
const agingKey = (i) => ['current', 'b30', 'b60', 'b90', 'b90p'][i];

Index.layout = (page) => (
  <App title="Invoices" railTitle="Collections" rail={<InvoicesRail stats={page.props.stats} />}>
    {page}
  </App>
);
