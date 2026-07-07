/**
 * @aero/ui — Charts
 *
 * Dependency-free, theme-token SVG charts. No recharts, no inline colours
 * beyond the caller-supplied series colour (which must itself be a theme
 * token, e.g. var(--aeos-primary)). Structural colours (grid, axis labels,
 * endpoint halo) come from CSS classes in components/data.css so they stay
 * theme-reactive.
 *
 * Exports:
 *   AreaTrend  — multi-series area/line trend chart (the robust revenue/
 *                activity chart used across dashboards).
 *   AreaSpark  — tiny hero-card area sparkline.
 *   BarMini    — clean vertical bar chart (when bars are genuinely wanted).
 *   Donut      — ring / progress donut with a centred label.
 */
import { useId } from 'react';
import { cx } from './Primitives.jsx';

const num = (v) => Number(v ?? 0);

/**
 * AreaTrend — robust multi-series SVG area+line chart.
 *
 * @param series  [{ key, label, color, values:number[], fill?:boolean }]
 *                The first series with fill !== false is drawn as a filled
 *                area; every series is drawn as a line. `color` must be a
 *                theme token string.
 * @param labels  string[] x-axis labels (one per data point).
 * @param height  SVG viewBox height (default 150). Uniform-scales to width.
 */
export function AreaTrend({
  series = [],
  labels = [],
  height = 150,
  grid = 4,
  className,
  ariaLabel = 'Trend chart',
  empty = 'Not enough data yet.',
}) {
  const uid = useId().replace(/[:]/g, '');
  const clean = series.filter((s) => Array.isArray(s.values) && s.values.length);
  const n = clean.length ? Math.max(...clean.map((s) => s.values.length)) : 0;

  if (n < 2) {
    return <div className={cx('aeos-chart aeos-chart--empty', className)}>{empty}</div>;
  }

  const W = 560;
  const H = height;
  const padL = 8;
  const padR = 12;
  const padT = 16;
  const padB = 22;
  const max = Math.max(...clean.flatMap((s) => s.values.map(num)), 1) * 1.14;

  const x = (i) => padL + (i / (n - 1)) * (W - padL - padR);
  const y = (v) => H - padB - (num(v) / max) * (H - padT - padB);
  const line = (vals) => vals.map((v, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ');
  const area = (vals) => `${line(vals)} L${x(n - 1).toFixed(1)} ${H - padB} L${x(0).toFixed(1)} ${H - padB} Z`;

  const gridYs = Array.from({ length: grid }, (_, g) => padT + (g / (grid - 1)) * (H - padT - padB));
  const primary = clean.find((s) => s.fill !== false) ?? clean[0];
  const last = primary.values[primary.values.length - 1];

  return (
    <div className={cx('aeos-chart', className)}>
      <svg viewBox={`0 0 ${W} ${H}`} role="img" aria-label={ariaLabel}>
        <defs>
          <linearGradient id={`ac-${uid}`} x1="0" x2="0" y1="0" y2="1">
            <stop offset="0" stopColor={primary.color} stopOpacity="0.28" />
            <stop offset="1" stopColor={primary.color} stopOpacity="0" />
          </linearGradient>
        </defs>
        {gridYs.map((gy, i) => (
          <line key={i} className="aeos-chart-grid" x1={padL} x2={W - padR} y1={gy.toFixed(1)} y2={gy.toFixed(1)} />
        ))}
        <path d={area(primary.values)} fill={`url(#ac-${uid})`} />
        {clean.map((s) => (
          <path key={s.key} d={line(s.values)} fill="none" stroke={s.color} strokeWidth={s === primary ? 2 : 1.6} />
        ))}
        <circle className="aeos-chart-end" cx={x(n - 1)} cy={y(last)} r="3.5" fill={primary.color} />
        {labels.map((l, i) => (
          <text key={i} className="aeos-chart-xlab" x={x(i)} y={H - 6} textAnchor="middle">{l}</text>
        ))}
      </svg>
    </div>
  );
}

/**
 * AreaSpark — tiny area sparkline for hero/KPI cards.
 * @param data  number[]
 * @param color theme token stroke/fill colour.
 */
export function AreaSpark({ data = [], color = 'var(--aeos-primary)', className }) {
  const uid = useId().replace(/[:]/g, '');
  if (!Array.isArray(data) || data.length < 2) return null;
  const w = 120;
  const h = 38;
  const vals = data.map(num);
  const max = Math.max(...vals);
  const min = Math.min(...vals);
  const span = max - min || 1;
  const pts = vals.map((p, i) => [(i / (vals.length - 1)) * w, h - 3 - ((p - min) / span) * (h - 8)]);
  const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
  return (
    <svg className={cx('aeos-spark', className)} viewBox={`0 0 ${w} ${h}`} preserveAspectRatio="none" aria-hidden="true">
      <defs>
        <linearGradient id={`sp-${uid}`} x1="0" x2="0" y1="0" y2="1">
          <stop offset="0" stopColor={color} stopOpacity="0.45" />
          <stop offset="1" stopColor={color} stopOpacity="0" />
        </linearGradient>
      </defs>
      <path d={`${line} L${w} ${h} L0 ${h} Z`} fill={`url(#sp-${uid})`} />
      <path d={line} fill="none" stroke={color} strokeWidth="1.5" />
    </svg>
  );
}

/**
 * BarMini — clean vertical bar chart. Use when bars carry meaning that a
 * line/area would blur (e.g. discrete per-bucket counts).
 * @param data  number[]
 * @param labels string[] optional x labels (rendered under bars).
 * @param color theme token bar colour.
 */
export function BarMini({ data = [], labels = [], color = 'var(--aeos-primary)', height = 140, className, ariaLabel = 'Bar chart' }) {
  const vals = (data ?? []).map(num);
  if (!vals.length) return <div className={cx('aeos-chart aeos-chart--empty', className)}>No data yet.</div>;
  const W = 560;
  const H = height;
  const padT = 12;
  const padB = labels.length ? 20 : 8;
  const max = Math.max(...vals, 1) * 1.1;
  const slot = W / vals.length;
  const bw = Math.min(34, slot * 0.56);
  const y = (v) => H - padB - (v / max) * (H - padT - padB);
  return (
    <div className={cx('aeos-chart', className)}>
      <svg viewBox={`0 0 ${W} ${H}`} role="img" aria-label={ariaLabel}>
        {vals.map((v, i) => {
          const cx0 = slot * i + slot / 2;
          const top = y(v);
          return (
            <g key={i}>
              <rect className="aeos-bar" x={(cx0 - bw / 2).toFixed(1)} y={top.toFixed(1)}
                width={bw.toFixed(1)} height={Math.max(0, H - padB - top).toFixed(1)} rx="3" fill={color} />
              {labels[i] != null && (
                <text className="aeos-chart-xlab" x={cx0.toFixed(1)} y={H - 5} textAnchor="middle">{labels[i]}</text>
              )}
            </g>
          );
        })}
      </svg>
    </div>
  );
}

/**
 * Donut — ring chart with a centred label.
 * @param segments [{ color, value }]  theme-token colours.
 * @param centerValue  big centre text (e.g. "95%").
 * @param centerLabel  small centre caption.
 * @param size  px diameter (default 118).
 */
export function Donut({ segments = [], centerValue, centerLabel, size = 118, thickness = 13, className }) {
  const total = segments.reduce((a, s) => a + num(s.value), 0) || 1;
  const r = (size - thickness) / 2 - 1;
  const c = 2 * Math.PI * r;
  const mid = size / 2;
  let off = 0;
  return (
    <div className={cx('aeos-donut', className)} style={{ width: size, height: size }}>
      <svg viewBox={`0 0 ${size} ${size}`} aria-hidden="true">
        <circle className="aeos-donut-track" cx={mid} cy={mid} r={r} fill="none" strokeWidth={thickness} />
        {segments.map((s, i) => {
          const len = (num(s.value) / total) * c;
          const el = (
            <circle key={i} cx={mid} cy={mid} r={r} fill="none" stroke={s.color} strokeWidth={thickness}
              strokeDasharray={`${len.toFixed(1)} ${(c - len).toFixed(1)}`} strokeDashoffset={(-off).toFixed(1)}
              transform={`rotate(-90 ${mid} ${mid})`} />
          );
          off += len;
          return el;
        })}
      </svg>
      {(centerValue != null || centerLabel != null) && (
        <div className="aeos-donut-mid">
          {centerValue != null && <b>{centerValue}</b>}
          {centerLabel != null && <small>{centerLabel}</small>}
        </div>
      )}
    </div>
  );
}
