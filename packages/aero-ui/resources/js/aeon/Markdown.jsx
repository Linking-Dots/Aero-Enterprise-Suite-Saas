import React from 'react';

// Minimal, dependency-free & XSS-safe markdown renderer for Aeon replies.
// Supports: #/##/### headings, **bold**, *italic*, `code`, - / * and 1. lists,
// paragraphs. Renders to React elements (never dangerouslySetInnerHTML).

function inline(str, kp) {
  const out = [];
  let rest = str;
  let k = 0;
  const re = /(\*\*([^*]+)\*\*|\*([^*\n]+)\*|`([^`]+)`)/;
  while (rest.length) {
    const m = re.exec(rest);
    if (!m) { out.push(rest); break; }
    if (m.index > 0) out.push(rest.slice(0, m.index));
    if (m[2] != null) out.push(<strong key={`${kp}-${k}`}>{m[2]}</strong>);
    else if (m[3] != null) out.push(<em key={`${kp}-${k}`}>{m[3]}</em>);
    else if (m[4] != null) out.push(<code key={`${kp}-${k}`}>{m[4]}</code>);
    rest = rest.slice(m.index + m[0].length);
    k += 1;
  }
  return out;
}

export default function Markdown({ text = '' }) {
  const lines = String(text).split('\n');
  const blocks = [];
  let list = null;
  const flush = () => { if (list) { blocks.push(list); list = null; } };

  lines.forEach((raw) => {
    const t = raw.trim();
    let m;
    if (!t) { flush(); return; }
    if ((m = /^(#{1,3})\s+(.*)$/.exec(t))) { flush(); blocks.push({ type: 'h', level: m[1].length, text: m[2] }); return; }
    if ((m = /^[-*]\s+(.*)$/.exec(t))) {
      if (!list || list.type !== 'ul') { flush(); list = { type: 'ul', items: [] }; }
      list.items.push(m[1]); return;
    }
    if ((m = /^\d+\.\s+(.*)$/.exec(t))) {
      if (!list || list.type !== 'ol') { flush(); list = { type: 'ol', items: [] }; }
      list.items.push(m[1]); return;
    }
    flush();
    blocks.push({ type: 'p', text: t });
  });
  flush();

  return (
    <div className="aeon-md">
      {blocks.map((b, i) => {
        if (b.type === 'h') return <div key={i} className={`aeon-md-h aeon-md-h${b.level}`}>{inline(b.text, i)}</div>;
        if (b.type === 'ul') return <ul key={i}>{b.items.map((it, j) => <li key={j}>{inline(it, `${i}-${j}`)}</li>)}</ul>;
        if (b.type === 'ol') return <ol key={i}>{b.items.map((it, j) => <li key={j}>{inline(it, `${i}-${j}`)}</li>)}</ol>;
        return <p key={i}>{inline(b.text, i)}</p>;
      })}
    </div>
  );
}
