import React, { useEffect, useRef, useState } from 'react';
import AeonCore from './AeonCore.jsx';
import AeonAura from './AeonAura.jsx';
import BlockRenderer from './BlockRenderer.jsx';

const STATUS = { idle: 'Online', listening: 'Listening…', thinking: 'Thinking…', speaking: 'Speaking…' };

const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const IcoExpand = svg(<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" />);
const IcoCollapse = svg(<path d="M3 8h5V3M21 8h-5V3M3 16h5v5M21 16h-5v5" />);
const IcoClose = svg(<path d="M6 6l12 12M18 6L6 18" />);
const IcoSend = <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 12l16-8-6 16-3-6-7-2z" /></svg>;

function initials(user) {
  const name = (user?.name || user?.full_name || user?.email || 'You').trim();
  const parts = name.split(/\s+/).filter(Boolean);
  const s = parts.length >= 2 ? parts[0][0] + parts[parts.length - 1][0] : name.slice(0, 2);
  return s.toUpperCase();
}

// The signed-in user's avatar (photo if present, else initials).
function UserAvatar({ user }) {
  const src = user?.avatar_url || user?.avatar || user?.profile_photo_url || user?.profile_photo;
  if (src) return <img className="aeon-av-img" src={src} alt="" />;
  return <span>{initials(user)}</span>;
}

// The Aeon "living console" — a slide-over with an ambient aura, an animated
// core that reflects state, generative-UI message blocks, and a smart composer.
export default function AeonDrawer({ isOpen, onClose, messages, sending, onSend, onAction, user }) {
  const [draft, setDraft] = useState('');
  const [expanded, setExpanded] = useState(false);
  const streamRef = useRef(null);
  const inputRef = useRef(null);

  const state = sending ? 'thinking' : 'listening';

  useEffect(() => {
    if (!isOpen) return;
    const onKey = (e) => { if (e.key === 'Escape') onClose?.(); };
    window.addEventListener('keydown', onKey);
    const id = setTimeout(() => inputRef.current?.focus(), 60);
    return () => { window.removeEventListener('keydown', onKey); clearTimeout(id); };
  }, [isOpen, onClose]);

  useEffect(() => {
    const el = streamRef.current;
    if (el) el.scrollTop = el.scrollHeight;
  }, [messages, sending]);

  if (!isOpen) return null;

  const submit = (e) => { e.preventDefault(); onSend(draft); setDraft(''); };

  return (
    <div className="aeon-overlay" role="dialog" aria-label="Aeon assistant" aria-modal="true">
      <div className="aeon-backdrop" onClick={onClose} />
      <section className={`aeon-console ${expanded ? 'is-expanded' : ''}`}>
        <AeonAura />

        <header className="aeon-head">
          <div className="aeon-head-core"><AeonCore state={state} size={44} /></div>
          <div className="aeon-head-id">
            <span className="aeon-head-name">Aeon <span className="aeon-badge">AI</span></span>
            <span className="aeon-status"><span className="aeon-status-dot" /> {STATUS[state]}</span>
          </div>
          <button type="button" className="aeon-ico" title={expanded ? 'Collapse' : 'Expand'} onClick={() => setExpanded((v) => !v)} aria-label="Toggle size">
            {expanded ? IcoCollapse : IcoExpand}
          </button>
          <button type="button" className="aeon-ico" title="Close" onClick={onClose} aria-label="Close Aeon">
            {IcoClose}
          </button>
        </header>

        <div className="aeon-stream" ref={streamRef}>
          {messages.length === 0 ? (
            <div className="aeon-empty">
              <div className="aeon-empty-core"><AeonCore state="idle" size={72} /></div>
              <div className="aeon-empty-t">Ask Aeon anything</div>
              <div className="aeon-empty-d">How do I add an employee? · Who's on leave this week? · Where are billing settings?</div>
            </div>
          ) : (
            messages.map((m, i) => (
              <div className={`aeon-turn ${m.role === 'user' ? 'is-me' : ''}`} key={i}>
                <div className={`aeon-av ${m.role === 'user' ? 'is-me' : 'is-ai'}`}>
                  {m.role === 'user' ? <UserAvatar user={user} /> : '✦'}
                </div>
                <div className="aeon-bubble">
                  <BlockRenderer blocks={m.blocks} onAction={onAction} animate={m.role !== 'user' && i === messages.length - 1} />
                </div>
              </div>
            ))
          )}

          {sending && (
            <div className="aeon-turn">
              <div className="aeon-av is-ai">✦</div>
              <div className="aeon-bubble aeon-think">
                <span className="aeon-eq"><i /><i /><i /><i /></span>
                <span>Aeon is thinking…</span>
              </div>
            </div>
          )}
        </div>

        <form className="aeon-composer" onSubmit={submit}>
          <div className="aeon-cbar">
            <input
              ref={inputRef}
              value={draft}
              onChange={(e) => setDraft(e.target.value)}
              placeholder="Message Aeon…  try /employee, /leave, /report"
              aria-label="Message Aeon"
              disabled={sending}
            />
            <button type="submit" className="aeon-send" disabled={sending || !draft.trim()} aria-label="Send">
              {IcoSend}
            </button>
          </div>
          <div className="aeon-cfoot">
            <span className="aeon-model"><span className="aeon-model-g" /> gemini-flash-latest</span>
            <span className="aeon-cfoot-sp" />
            <span>Guarded by your access</span>
          </div>
        </form>
      </section>
    </div>
  );
}
