import React, { useEffect, useRef } from 'react';

/**
 * Ambient aurora backdrop for the Aeon console — soft drifting colour blobs
 * (accent + companions) rendered on a canvas. Theme-reactive via --aeos-primary,
 * static under prefers-reduced-motion.
 */
export default function AeonAura() {
  const ref = useRef(null);

  useEffect(() => {
    const canvas = ref.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const hexToRgb = (h) => {
      h = (h || '#22e3ff').replace('#', '');
      if (h.length === 3) h = h.split('').map((c) => c + c).join('');
      const n = parseInt(h, 16);
      return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    };
    const accent = () => getComputedStyle(document.documentElement).getPropertyValue('--aeos-primary').trim() || '#22e3ff';

    let W, H;
    const size = () => {
      const r = canvas.getBoundingClientRect();
      W = canvas.width = Math.max(1, r.width * dpr);
      H = canvas.height = Math.max(1, r.height * dpr);
    };
    size();
    const ro = new ResizeObserver(size);
    ro.observe(canvas);

    const blobs = [
      { x: 0.2, y: 0.28, r: 0.55, c: () => hexToRgb(accent()), p: 0 },
      { x: 0.85, y: 0.14, r: 0.45, c: () => [140, 107, 255], p: 2 },
      { x: 0.72, y: 0.92, r: 0.5, c: () => [255, 102, 196], p: 4 },
    ];

    let raf;
    const draw = (t) => {
      ctx.clearRect(0, 0, W, H);
      ctx.globalCompositeOperation = 'lighter';
      blobs.forEach((b) => {
        const [r, g, bl] = b.c();
        const wob = reduce ? 0 : Math.sin(t / 2600 + b.p) * 0.06;
        const X = (b.x + wob) * W, Y = (b.y - wob) * H, R = b.r * Math.min(W, H);
        const grad = ctx.createRadialGradient(X, Y, 0, X, Y, R);
        grad.addColorStop(0, `rgba(${r},${g},${bl},.15)`);
        grad.addColorStop(1, `rgba(${r},${g},${bl},0)`);
        ctx.fillStyle = grad;
        ctx.beginPath(); ctx.arc(X, Y, R, 0, 6.2832); ctx.fill();
      });
      ctx.globalCompositeOperation = 'source-over';
      if (!reduce) raf = requestAnimationFrame(draw);
    };
    if (reduce) draw(0);
    else raf = requestAnimationFrame(draw);

    return () => { cancelAnimationFrame(raf); ro.disconnect(); };
  }, []);

  return <canvas ref={ref} className="aeon-aura" aria-hidden="true" />;
}
