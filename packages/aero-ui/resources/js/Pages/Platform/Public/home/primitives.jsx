// ─── Living data-OS — shared motion primitives ───────────────────────────────
import { useRef, useEffect, useState } from 'react';
import {
  motion, useInView, useMotionValue, useSpring, useTransform, animate,
} from 'framer-motion';

// ── useThemeMode — tracks the active light/dark mode from <body> class ───────
export function useThemeMode() {
  const [mode, setMode] = useState('dark');
  useEffect(() => {
    const read = () => setMode(document.body.classList.contains('aeos--light') ? 'light' : 'dark');
    read();
    const obs = new MutationObserver(read);
    obs.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    return () => obs.disconnect();
  }, []);
  return mode;
}

// Respect reduced-motion at runtime
export function usePrefersReducedMotion() {
  const [reduced, setReduced] = useState(false);
  useEffect(() => {
    const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
    const on = () => setReduced(mq.matches);
    on();
    mq.addEventListener('change', on);
    return () => mq.removeEventListener('change', on);
  }, []);
  return reduced;
}

// ── CountUp — animates to `value` when scrolled into view ────────────────────
export function CountUp({ value, decimals = 0, prefix = '', suffix = '', duration = 1.8 }) {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: '-15% 0px' });
  const [display, setDisplay] = useState(0);
  const reduced = usePrefersReducedMotion();

  useEffect(() => {
    if (!inView) return;
    if (reduced) { setDisplay(value); return; }
    const controls = animate(0, value, {
      duration,
      ease: [0.16, 1, 0.3, 1],
      onUpdate: (v) => setDisplay(v),
    });
    return () => controls.stop();
  }, [inView, value, duration, reduced]);

  return (
    <span ref={ref}>
      {prefix}{display.toLocaleString('en-US', {
        minimumFractionDigits: decimals, maximumFractionDigits: decimals,
      })}{suffix}
    </span>
  );
}

// ── useTilt — pointer-driven 3D tilt (spring-smoothed) ───────────────────────
export function useTilt({ max = 8 } = {}) {
  const ref = useRef(null);
  const reduced = usePrefersReducedMotion();
  const px = useMotionValue(0.5);
  const py = useMotionValue(0.5);
  const rx = useSpring(useTransform(py, [0, 1], [max, -max]), { stiffness: 150, damping: 18 });
  const ry = useSpring(useTransform(px, [0, 1], [-max, max]), { stiffness: 150, damping: 18 });

  function onMove(e) {
    if (reduced) return;
    const r = ref.current?.getBoundingClientRect();
    if (!r) return;
    px.set((e.clientX - r.left) / r.width);
    py.set((e.clientY - r.top) / r.height);
  }
  function onLeave() { px.set(0.5); py.set(0.5); }

  return { ref, onMove, onLeave, rotateX: reduced ? 0 : rx, rotateY: reduced ? 0 : ry };
}

// ── BrowserFrame — a product screenshot in faux browser chrome ───────────────
export function BrowserFrame({ src, alt = '', url = 'app.aeos365.com', className = '', imgKey }) {
  return (
    <div className={`lv-frame ${className}`}>
      <div className="lv-frame-bar">
        <span className="lv-frame-dot lv-frame-dot--r" />
        <span className="lv-frame-dot lv-frame-dot--y" />
        <span className="lv-frame-dot lv-frame-dot--g" />
        <span className="lv-frame-url">{url}</span>
      </div>
      <div className="lv-frame-shot">
        <motion.img
          key={imgKey ?? src}
          src={src}
          alt={alt}
          loading="lazy"
          initial={{ opacity: 0, scale: 1.02 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.5, ease: 'easeOut' }}
        />
      </div>
    </div>
  );
}

// ── ThemedShot — BrowserFrame that swaps dark/light product shot ─────────────
export function ThemedShot({ shot, url = 'app.aeos365.com', alt = '', className = '' }) {
  const mode = useThemeMode();
  const src = (shot && (shot[mode] || shot.dark || shot.light)) || shot;
  return (
    <BrowserFrame src={src} url={url} alt={alt} className={className} imgKey={`${mode}:${src}`} />
  );
}

// ── ExtractedCard — a legible UI fragment rebuilt in DOM (theme-reactive) ─────
// Floats beside the main product shot; crisp at any size, unlike a screenshot crop.
export function ExtractedCard({ kind = 'stat', accent = 'cyan', className = '', ...p }) {
  return (
    <div className={`lv-xcard lv-xcard--${accent} ${className}`}>
      {kind === 'stat' && (
        <>
          <span className="lv-xcard-label">{p.label}</span>
          <span className="lv-xcard-value">{p.value}</span>
          {p.delta && <span className="lv-xcard-delta">▲ {p.delta}</span>}
          {p.spark && (
            <span className="lv-xcard-spark" aria-hidden="true">
              {p.spark.map((h, i) => <i key={i} style={{ height: `${h}%` }} />)}
            </span>
          )}
        </>
      )}
      {kind === 'toast' && (
        <>
          <span className={`lv-xcard-dot lv-accent--${accent}`} />
          <div className="lv-xcard-toast">
            <span className="lv-xcard-toast-title">{p.title}</span>
            <span className="lv-xcard-toast-body">{p.body}</span>
          </div>
        </>
      )}
      {kind === 'status' && (
        <>
          <span className="lv-xcard-status-row">
            <span className="lv-xcard-check" aria-hidden="true">✓</span>
            <span className="lv-xcard-label">{p.label}</span>
          </span>
          <span className="lv-xcard-value">{p.value}</span>
        </>
      )}
    </div>
  );
}

// ── Reveal — fade/rise on scroll into view ───────────────────────────────────
export function Reveal({ children, delay = 0, y = 24, className = '' }) {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: '-12% 0px' });
  return (
    <motion.div
      ref={ref}
      className={className}
      initial={{ opacity: 0, y }}
      animate={inView ? { opacity: 1, y: 0 } : {}}
      transition={{ duration: 0.7, delay, ease: [0.16, 1, 0.3, 1] }}
    >
      {children}
    </motion.div>
  );
}
