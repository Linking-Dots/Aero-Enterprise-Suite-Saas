import React, { useEffect, useState } from 'react';
import Markdown from './Markdown.jsx';

// Reveals a markdown reply one letter at a time (eased) on first mount; markdown
// renders progressively as it streams. Instant under reduced-motion. Calls
// onAnimated() once on mount so the reply is marked "typed" and never replays.
function TypewriterText({ text, onAnimated }) {
  const full = text ?? '';
  const reduce = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const [n, setN] = useState(reduce ? full.length : 0);
  const done = n >= full.length;

  useEffect(() => {
    onAnimated?.();
    if (reduce) { setN(full.length); return; }
    let i = 0;
    const id = setInterval(() => {
      i += 1;
      setN(i);
      if (i >= full.length) clearInterval(id);
    }, 14);
    return () => clearInterval(id);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [full]);

  return (
    <div className={`aeon-typing ${done ? 'is-done' : ''}`}>
      <Markdown text={full.slice(0, n)} />
    </div>
  );
}

// Sparkline for `chart` blocks — dependency-free inline SVG.
function Spark({ points = [], unit = '' }) {
  if (!points.length) return null;
  const w = 300, h = 68, min = Math.min(...points), max = Math.max(...points) || 1;
  const span = max - min || 1;
  const xs = (i) => i * (w / (points.length - 1 || 1));
  const ys = (v) => h - 6 - ((v - min) / span) * (h - 16);
  let d = '', a = `M0 ${h}`;
  points.forEach((v, i) => { const x = xs(i), y = ys(v); d += (i ? ' L' : 'M') + x + ' ' + y; a += ` L${x} ${y}`; });
  a += ` L${w} ${h} Z`;
  const last = points.length - 1;
  return (
    <svg className="aeon-spark" viewBox={`0 0 ${w} ${h}`} width="100%" height={h} preserveAspectRatio="none" aria-label="trend">
      <defs>
        <linearGradient id="aeonSpark" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stopColor="var(--aeos-primary,#22e3ff)" stopOpacity="0.32" />
          <stop offset="1" stopColor="var(--aeos-primary,#22e3ff)" stopOpacity="0" />
        </linearGradient>
      </defs>
      <path d={a} fill="url(#aeonSpark)" />
      <path d={d} fill="none" stroke="var(--aeos-primary,#22e3ff)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
      <circle cx={xs(last)} cy={ys(points[last])} r="3.4" fill="var(--aeos-primary,#22e3ff)" />
    </svg>
  );
}

function Block({ block, onAction, animate, onAnimated }) {
  switch (block.type) {
    case 'stats':
      return (
        <div className="aeon-stat-row">
          {(block.items || []).map((s, i) => (
            <div className="aeon-stat" key={i}>
              <div className="aeon-stat-k">{s.k}</div>
              <div className="aeon-stat-v">{s.v}</div>
              {s.d ? <div className={`aeon-stat-d ${s.dir === 'down' ? 'is-down' : 'is-up'}`}>{s.d}</div> : null}
            </div>
          ))}
        </div>
      );
    case 'chart':
      return (
        <div className="aeon-chartcard">
          <div className="aeon-chart-h"><span>{block.title || 'Trend'}</span>{block.value ? <span className="aeon-chart-v">{block.value}</span> : null}</div>
          <Spark points={block.points || []} unit={block.unit} />
        </div>
      );
    case 'chips':
      return (
        <div className="aeon-chips">
          {(block.items || []).map((c, i) => (
            <button
              type="button"
              key={i}
              className={`aeon-chip ${block.variant === 'source' ? 'is-src' : ''}`}
              onClick={block.variant === 'source' ? undefined : () => onAction?.({ kind: 'chip', value: c })}
            >
              {block.variant === 'source' ? <span className="aeon-chip-glyph">◈</span> : null}
              {typeof c === 'string' ? c : c.label}
            </button>
          ))}
        </div>
      );
    case 'action':
      return (
        <div className="aeon-action">
          <div className="aeon-action-h">◆ Proposed action · needs your confirm</div>
          <div className="aeon-action-t">{block.title}</div>
          {block.desc ? <div className="aeon-action-d">{block.desc}</div> : null}
          {block.fields?.length ? (
            <div className="aeon-action-fields">
              {block.fields.map((f, i) => (
                <span className="aeon-field" key={i}>{f.k} <b>{f.v}</b></span>
              ))}
            </div>
          ) : null}
          <div className="aeon-action-row">
            <button type="button" className="aeon-abtn is-go" onClick={() => onAction?.({ kind: 'confirm', block })}>
              {block.confirm_label || 'Open & prefill →'}
            </button>
            <button type="button" className="aeon-abtn" onClick={() => onAction?.({ kind: 'edit', block })}>Edit</button>
            <button type="button" className="aeon-abtn" onClick={() => onAction?.({ kind: 'cancel', block })}>Cancel</button>
          </div>
        </div>
      );
    case 'text':
    default:
      return animate
        ? <TypewriterText text={block.text ?? ''} onAnimated={onAnimated} />
        : <Markdown text={block.text ?? ''} />;
  }
}

// Renders Aeon's generative-UI blocks into @aero/ui-styled components.
// `animate` types text blocks in letter-by-letter (used for Aeon's replies);
// onAnimated() fires once so a reply is never re-typed on re-open.
export default function BlockRenderer({ blocks = [], onAction, animate = false, onAnimated }) {
  return (
    <div className="aeon-blocks">
      {blocks.map((block, i) => (
        <Block key={i} block={block} onAction={onAction} animate={animate} onAnimated={onAnimated} />
      ))}
    </div>
  );
}
