import { forwardRef } from 'react';
import { ArrowUpIcon, ArrowDownIcon, ArrowTrendingUpIcon as TrendingUpIcon, InboxIcon } from '@heroicons/react/24/outline';
import { cx } from './Primitives.jsx';
import { Skeleton } from './Display.jsx';
import { Icon } from '../icons/icons.jsx';

const STAT_ICON_CLASS = {
  cyan: '', default: '',
  amber: 'aeos-stat-icon-amber',
  indigo: 'aeos-stat-icon-indigo',
  success: 'aeos-stat-icon-success',
};

const PROGRESS_FILL_CLASS = {
  cyan: '', default: '',
  amber: 'aeos-progress-fill-amber',
  success: 'aeos-progress-fill-success',
};

/** KPI — key performance indicator tile with label, value, delta, and sparkline. */
export function KPI({ label, value, delta, deltaTrend, sparkline, loading }) {
  if (loading) {
    return (
      <div className="aeos-kpi-tile">
        <Skeleton h={10} w="60%" className="aeos-skeleton-gap-sm" />
        <Skeleton h={36} w="80%" className="aeos-skeleton-gap-xs" />
        <Skeleton h={12} w="50%" />
      </div>
    );
  }
  return (
    <div className="aeos-kpi-tile">
      <div className="aeos-kpi-label">{label}</div>
      <div className="aeos-kpi-value">{value}</div>
      {delta && (
        <div className={cx('aeos-kpi-delta', deltaTrend ?? 'neutral')}>
          {deltaTrend === 'up' ? (
            <ArrowUpIcon className="aeos-icon-xs" />
          ) : deltaTrend === 'down' ? (
            <ArrowDownIcon className="aeos-icon-xs" />
          ) : (
            <TrendingUpIcon className="aeos-icon-xs" />
          )}
          {delta}
        </div>
      )}
      {sparkline?.length > 0 && <Sparkline data={sparkline} />}
    </div>
  );
}

/** Sparkline — miniature bar chart. */
export function Sparkline({ data = [], height = 32, intent = 'cyan' }) {
  const max = Math.max(...data, 1);
  return (
    <div
      className={cx('aeos-sparkline', intent === 'amber' && 'amber')}
      style={{ height }}
      aria-hidden="true"
    >
      {data.map((v, i) => (
        <div
          key={i}
          className="aeos-sparkline-bar"
          style={{ height: `${Math.max(8, (v / max) * 100)}%` }}
        />
      ))}
    </div>
  );
}

/** Stat — icon + label + description + numeric value card. */
export const Stat = forwardRef(function Stat(
  { icon, iconTone = 'cyan', title, description, value, unit, className, ...rest },
  ref
) {
  return (
    <div ref={ref} className={cx('aeos-stat-card', className)} {...rest}>
      <div className={cx('aeos-stat-icon', STAT_ICON_CLASS[iconTone])}>
        {icon && <Icon name={icon} size={20} />}
      </div>
      <div className="aeos-stat-body">
        <h3>{title}</h3>
        {description && <p>{description}</p>}
      </div>
      <div className="aeos-stat-meta">
        {value !== undefined && <span className="aeos-stat-number">{value}</span>}
        {unit && <span className="aeos-stat-unit">{unit}</span>}
      </div>
    </div>
  );
});

/** MetricChip — small inline metric badge. */
export function MetricChip({ icon, intent = 'primary', children, className }) {
  return (
    <span className={cx('aeos-metric-chip', intent === 'amber' && 'aeos-metric-chip-amber', className)}>
      {icon && <Icon name={icon} size={12} />}
      {children}
    </span>
  );
}

/** ProgressRow — labelled progress bar with percentage. */
export function ProgressRow({ label, value = 0, max = 100, intent = 'cyan', className }) {
  const pct = Math.min(100, Math.max(0, (value / max) * 100));
  return (
    <div className={cx('aeos-progress-row', className)}>
      <div className="aeos-progress-header">
        <span className="aeos-progress-label">{label}</span>
        <span className="aeos-progress-value">{Math.round(pct)}%</span>
      </div>
      <div className="aeos-progress">
        <div
          className={cx('aeos-progress-fill', PROGRESS_FILL_CLASS[intent])}
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  );
}

/** DataTable — responsive data table with optional sorting, pagination and row click. */
export function DataTable({
  columns = [],
  rows = [],
  onRowClick,
  empty = 'No records found',
  stickyHeader,
  loading,
  className,
}) {
  if (loading) {
    return (
      <div className={cx('aeos-table-wrap', className)}>
        <table className="aeos-table">
          <thead>
            <tr>{columns.map(c => <th key={c.key}>{c.label}</th>)}</tr>
          </thead>
          <tbody>
            {Array.from({ length: 5 }).map((_, i) => (
              <tr key={i}>
                {columns.map(c => (
                  <td key={c.key}><Skeleton h={14} w="80%" /></td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }

  if (!rows.length) {
    return <EmptyState icon="inbox" title="No records" description={empty} />;
  }

  return (
    <div className={cx('aeos-table-wrap', stickyHeader && 'sticky', className)}>
      <table className="aeos-table">
        <thead>
          <tr>
            {columns.map(c => (
              <th
                key={c.key}
                style={{ textAlign: c.align ?? 'left', width: c.width }}
              >
                {c.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row, i) => (
            <tr
              key={row.id ?? i}
              onClick={onRowClick ? () => onRowClick(row) : undefined}
              style={onRowClick ? { cursor: 'pointer' } : undefined}
            >
              {columns.map(c => (
                <td
                  key={c.key}
                  className={c.mono ? 'mono' : undefined}
                  style={{ textAlign: c.align ?? 'left' }}
                >
                  {c.render ? c.render(row) : row[c.key]}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

/** EmptyState — centred placeholder for empty content areas. */
export function EmptyState({ icon = <InboxIcon className="aeos-icon-xl" />, title, description, action, className }) {
  return (
    <div className={cx('aeos-empty-state', className)}>
      <div className="aeos-empty-icon">
        {typeof icon === 'string' ? (
          <Icon name={icon} size={40} className="aeos-icon-xl" />
        ) : (
          icon
        )}
      </div>
      {title && <h3 className="aeos-empty-title">{title}</h3>}
      {description && <p className="aeos-empty-desc">{description}</p>}
      {action && <div className="aeos-empty-action">{action}</div>}
    </div>
  );
}
