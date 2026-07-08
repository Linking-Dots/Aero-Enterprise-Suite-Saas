import { useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody } from '@aero/ui';

import '../../Products/products.css';
import './subscriptions.css';
import './invoices.css';

const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  subs: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  download: svg(<><path d="M12 3v12M7 10l5 5 5-5M5 21h14" /></>),
};

const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
const fmtMoney2 = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const STATUS_LABEL = { paid: 'Paid', open: 'Open', overdue: 'Overdue', draft: 'Draft', void: 'Void' };
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; } };

function InvoicesRail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Collections</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Collected</span><b>{fmtMoney(s.collected)}</b></div>
          <div className="pc-rail__row"><span>Outstanding</span><b>{fmtMoney(s.outstanding)}</b></div>
          <div className="pc-rail__row"><span>Overdue</span><b>{s.overdue ?? 0}</b></div>
          <div className="pc-rail__row"><span>Paid rate</span><b>{s.paid_rate ?? 0}%</b></div>
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

/* void modal (reason required) */
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

export default function Index({ stats, invoices }) {
  const s = stats ?? {};
  const list = invoices ?? [];
  const [statusFilter, setStatusFilter] = useState('all');
  const [voidInv, setVoidInv] = useState(null);

  const rows = useMemo(() => statusFilter === 'all' ? list : list.filter((r) => r.status === statusFilter), [list, statusFilter]);

  const kpis = [
    { label: 'Collected', value: fmtMoney(s.collected), delta: `${s.paid ?? 0} invoices paid`, up: true },
    { label: 'Outstanding', value: fmtMoney(s.outstanding), delta: `${s.open ?? 0} open · ${s.overdue ?? 0} overdue` },
    { label: 'Overdue', value: fmtMoney(s.overdue_amt), delta: `${s.overdue ?? 0} invoices past due`, up: (s.overdue ?? 0) === 0 },
    { label: 'Paid rate', value: `${s.paid_rate ?? 0}%`, delta: `${s.paid ?? 0} of ${s.total ?? 0}`, up: true },
  ];

  const total = Math.max(1, s.total ?? 1);
  const distSegments = [['paid', s.paid], ['open', s.open], ['overdue', s.overdue]];

  const markPaid = (r) => { if (window.confirm(`Mark ${r.number} (${fmtMoney2(r.total)}) as paid?`)) router.post(`/billing/invoices/${r.id}/mark-paid`, {}, { preserveScroll: true }); };
  const send = (r) => { if (window.confirm(`Send ${r.number} to ${r.tenant}?`)) router.post(`/billing/invoices/${r.id}/send`, {}, { preserveScroll: true }); };
  const download = (r) => window.open(`/billing/invoices/${r.id}/download`, '_blank');

  const actionsFor = (r) => {
    const acts = [];
    if (['open', 'overdue'].includes(r.status)) {
      acts.push(<button key="mp" type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => markPaid(r)}>Mark paid</button>);
      acts.push(<button key="s" type="button" className="pc-btn pc-btn--sm" onClick={() => send(r)}>Send</button>);
    }
    if (r.status === 'draft') {
      acts.push(<button key="s" type="button" className="pc-btn pc-btn--sm" onClick={() => send(r)}>Send</button>);
    }
    acts.push(<button key="d" type="button" className="pc-btn pc-btn--sm" onClick={() => download(r)}>Download</button>);
    if (['open', 'overdue', 'draft'].includes(r.status)) {
      acts.push(<button key="v" type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => setVoidInv(r)}>Void</button>);
    }
    return acts;
  };

  return (
    <div className="pc">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Billing · Collections</div>
          <h1 className="pc-title">Invoices</h1>
          <div className="pc-sub">Every invoice across the platform — collected, outstanding and overdue — with mark-paid, send, void and download in one view.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => router.visit('/billing/subscriptions')}>{Glyph.subs}<span>Subscriptions</span></button>
        </div>
      </div>

      <div className="pc-kpis sc-kpis4">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}`}>{c.delta}</div>
            </div>
          </CardBody></Card>
        ))}
      </div>

      <Card>
        <CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Collections</h2><div className="pc-panel-h__sub">{s.total ?? 0} invoices</div></div>
          </div>
          <div className="sc-dist">
            {distSegments.map(([st, n]) => (n > 0 ? <span key={st} className={`inv-seg-${st}`} style={{ width: `${(n / total) * 100}%` }} /> : null))}
          </div>
          <div className="sc-legend">
            {distSegments.map(([st, n]) => (
              <span key={st} className="li" onClick={() => setStatusFilter(statusFilter === st ? 'all' : st)}>
                <span className={`d inv-seg-${st}`} />{STATUS_LABEL[st]} <b>{n ?? 0}</b>
              </span>
            ))}
          </div>
        </CardBody>
      </Card>

      <Card>
        <CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Invoices</h2><div className="pc-panel-h__sub">Newest first</div></div>
            <select className="pc-input sc-statusfilter" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
              <option value="all">All statuses</option>
              {Object.entries(STATUS_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </div>
          <div className="pc-tablewrap">
            <table className="pc-table">
              <thead>
                <tr>
                  <th>Invoice</th>
                  <th className="pc-hide-sm">Period</th>
                  <th>Status</th>
                  <th className="pc-r">Amount</th>
                  <th className="pc-hide-sm pc-r">Due</th>
                  <th className="pc-r">Actions</th>
                </tr>
              </thead>
              <tbody>
                {rows.length === 0 && <tr><td colSpan={6}><div className="pc-sub ent-empty">No invoices match this filter.</div></td></tr>}
                {rows.slice(0, 100).map((r) => (
                  <tr key={r.id}>
                    <td>
                      <div className="pc-mrow">
                        <div className="sc-av">{initials(r.tenant)}</div>
                        <div><div className="pc-mname">{r.tenant}</div><div className="inv-num">{r.number}</div></div>
                      </div>
                    </td>
                    <td className="pc-hide-sm sc-kind">{fmtDate(r.period_start)} – {fmtDate(r.period_end)}</td>
                    <td><span className={`pc-chip pc-chip--${r.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[r.status] ?? r.status}</span></td>
                    <td className="pc-r pc-price">{fmtMoney2(r.total)}</td>
                    <td className="pc-hide-sm pc-r sc-kind">{fmtDate(r.due_date)}</td>
                    <td className="pc-r"><div className="sc-actions">{actionsFor(r)}</div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardBody>
      </Card>

      {voidInv && <VoidModal invoice={voidInv} onClose={() => setVoidInv(null)} />}
    </div>
  );
}

Index.layout = (page) => (
  <App title="Invoices" railTitle="Collections" rail={<InvoicesRail stats={page.props.stats} />}>
    {page}
  </App>
);
