import { useBreakpoint } from '../hooks/index.js';
import { cx } from '../components/Primitives.jsx';
import { Breadcrumb, PageHeader } from '../components/Navigation.jsx';

/* ── IndexPageLayout — list views with KPIs + filter bar + table ─ */
export function IndexPageLayout({
  title, breadcrumb, eyebrow, description, actions,
  kpis, filters, table, pagination, maxWidth,
}) {
  return (
    <div
      className="aeos-page-layout aeos-page-layout-index"
      style={maxWidth ? { maxWidth } : undefined}
    >
      <PageHeader
        breadcrumb={breadcrumb}
        title={title}
        eyebrow={eyebrow}
        description={description}
        actions={actions}
      />
      {kpis?.length > 0 && (
        <div className="aeos-kpi-row">
          {kpis.map((kpi, i) => (
            <div key={i} className="aeos-kpi-col">{kpi}</div>
          ))}
        </div>
      )}
      {filters && <div className="aeos-filters-bar">{filters}</div>}
      {table    && <div className="aeos-table-section">{table}</div>}
      {pagination && <div className="aeos-pagination-bar">{pagination}</div>}
    </div>
  );
}

/* ── DetailPageLayout — single-record view with tabs and sections ─ */
export function DetailPageLayout({
  title, breadcrumb, status, actions, tabs, maxWidth, children,
}) {
  return (
    <div
      className="aeos-page-layout aeos-page-layout-detail"
      style={maxWidth ? { maxWidth } : undefined}
    >
      <PageHeader
        breadcrumb={breadcrumb}
        title={title}
        status={status}
        actions={actions}
      />
      {tabs && <div className="aeos-detail-tabs">{tabs}</div>}
      <div className="aeos-detail-content">{children}</div>
    </div>
  );
}

/* ── FormPageLayout — create/edit pages with sticky mobile footer ─ */
export function FormPageLayout({
  title, breadcrumb, description, actions,
  maxWidth, size = 'md', children,
}) {
  return (
    <div
      className={cx('aeos-page-layout aeos-page-layout-form', `aeos-page-layout-form-${size}`)}
      style={maxWidth ? { maxWidth } : undefined}
    >
      <PageHeader
        breadcrumb={breadcrumb}
        title={title}
        description={description}
      />
      <div className="aeos-form-sections">{children}</div>
      {actions && <div className="aeos-form-actions">{actions}</div>}
    </div>
  );
}

/* ── DashboardLayout — responsive metric grid ─────────────────── */
export function DashboardLayout({
  title, breadcrumb, actions,
  cols = { base: 1, md: 2, lg: 3 },
  gap = 'md',
  maxWidth,
  children,
}) {
  const bp = useBreakpoint();

  const activeCols =
    bp === 'sm'  ? (cols.base ?? 1) :
    bp === 'md'  ? (cols.md  ?? cols.base ?? 2) :
                   (cols.lg  ?? cols.md ?? cols.base ?? 3);

  return (
    <div
      className="aeos-page-layout aeos-page-layout-dashboard"
      style={maxWidth ? { maxWidth } : undefined}
    >
      <PageHeader
        breadcrumb={breadcrumb}
        title={title}
        actions={actions}
      />
      <div
        className={cx('aeos-dashboard-grid', `cols-${activeCols}`, `gap-${gap}`)}
      >
        {children}
      </div>
    </div>
  );
}
