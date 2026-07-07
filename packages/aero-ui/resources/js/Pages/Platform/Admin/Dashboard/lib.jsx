import { useEffect, useRef, useState } from 'react';
import { useTheme } from '@aero/ui';

/* ── Formatters ─────────────────────────────────────────────────────── */

export function fmtCurrency(v) {
  const n = Number(v ?? 0);
  const sign = n < 0 ? '-' : '';
  const a = Math.abs(n);
  if (a >= 1_000_000) return `${sign}$${(a / 1_000_000).toFixed(2)}M`;
  if (a >= 1_000)     return `${sign}$${(a / 1_000).toFixed(1)}K`;
  return `${sign}$${Math.round(a)}`;
}

export function fmtNumber(v) {
  return new Intl.NumberFormat('en-US').format(Math.round(Number(v ?? 0)));
}

export function fmtPct(v) {
  return `${Number(v ?? 0).toFixed(1)}%`;
}

export function formatValue(value, format) {
  if (format === 'currency') return fmtCurrency(value);
  if (format === 'percent')  return fmtPct(value);
  return fmtNumber(value);
}

/** Downsample a numeric series to at most `n` evenly-spaced points. */
export function sampleSeries(arr = [], n = 14) {
  if (arr.length <= n) return arr;
  const step = (arr.length - 1) / (n - 1);
  return Array.from({ length: n }, (_, i) => arr[Math.round(i * step)]);
}

/* ── Motion ─────────────────────────────────────────────────────────── */

/** True when the Theme Studio motion setting is reduced/off. */
export function useReducedMotion() {
  const { motion } = useTheme();
  return motion !== 'full';
}

/* ── Count-up ───────────────────────────────────────────────────────── */

/**
 * Animate a number from 0 → target on mount (and on target change).
 * Instant (no animation) when reduced motion is active.
 */
export function useCountUp(target, { duration = 650 } = {}) {
  const reduced = useReducedMotion();
  const [val, setVal] = useState(reduced ? target : 0);
  const rafRef = useRef(null);
  const fromRef = useRef(0);

  useEffect(() => {
    if (reduced) { setVal(target); return; }

    const from = fromRef.current;
    const start = performance.now();
    const ease = (t) => 1 - Math.pow(1 - t, 3); // ease-out cubic

    const tick = (now) => {
      const p = Math.min(1, (now - start) / duration);
      setVal(from + (target - from) * ease(p));
      if (p < 1) rafRef.current = requestAnimationFrame(tick);
      else fromRef.current = target;
    };
    rafRef.current = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(rafRef.current);
  }, [target, duration, reduced]);

  return val;
}

/** Count-up hero value — shows shimmer until first frame, then the formatted number. */
export function HeroValue({ value, format }) {
  const animated = useCountUp(Number(value ?? 0));
  return <div className="lcc-hero__value">{formatValue(animated, format)}</div>;
}

/* ── Polling (visibility-gated) ─────────────────────────────────────── */

/**
 * Calls `cb` every `ms` — but only while the tab is visible.
 * Pauses on hidden, fires once immediately on re-show.
 */
export function usePolling(cb, ms = 30000) {
  const saved = useRef(cb);
  saved.current = cb;

  useEffect(() => {
    let id = null;
    const start = () => { if (!id) id = setInterval(() => saved.current(), ms); };
    const stop = () => { if (id) { clearInterval(id); id = null; } };

    const onVisibility = () => {
      if (document.hidden) stop();
      else { saved.current(); start(); }
    };

    if (!document.hidden) start();
    document.addEventListener('visibilitychange', onVisibility);
    return () => { stop(); document.removeEventListener('visibilitychange', onVisibility); };
  }, [ms]);
}

/* ── Small components ────────────────────────────────────────────────── */

const ArrowUp = (p) => (
  <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" {...p}><path fillRule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clipRule="evenodd" /></svg>
);
const ArrowDown = (p) => (
  <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" {...p}><path fillRule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clipRule="evenodd" /></svg>
);

/** DeltaChip — signed percentage with directional arrow. `invert` flips good/bad color. */
export function DeltaChip({ value, invert = false, suffix = '%' }) {
  if (value === null || value === undefined) return null;
  const n = Number(value);
  const positive = n >= 0;
  const good = invert ? !positive : positive;
  const cls = n === 0 ? '' : good ? 'is-up' : 'is-down';
  return (
    <span className={`lcc-delta ${cls}`}>
      {n >= 0 ? <ArrowUp /> : <ArrowDown />}
      {Math.abs(n).toFixed(1)}{suffix}
    </span>
  );
}

/** PulseDot — heartbeat status chip. */
export function PulseDot({ status = 'operational', label }) {
  const cls = status === 'critical' ? 'is-critical' : status === 'warning' ? 'is-warning' : '';
  const text = label ?? (status === 'critical' ? 'Critical issues' : status === 'warning' ? 'Attention needed' : 'All systems operational');
  return (
    <span className={`lcc-pulse ${cls}`}>
      <span className="lcc-pulse__dot" />
      {text}
    </span>
  );
}
