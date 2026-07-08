import React, { useEffect, useRef, useState } from 'react';
import AeonCore from './AeonCore.jsx';
import BlockRenderer from './BlockRenderer.jsx';

const IcoSend = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M4 12l16-8-6 16-3-6-7-2z" />
  </svg>
);

const SUGGESTIONS = [
  { icon: '↗', text: 'How do I add a new employee?' },
  { icon: '◫', text: 'Break down employees by department' },
  { icon: '◐', text: 'Show the share of employees by type' },
  { icon: '◈', text: 'How many users are there?' },
];

function initials(user) {
  const name = (user?.name || user?.full_name || user?.email || 'You').trim();
  const parts = name.split(/\s+/).filter(Boolean);
  const s = parts.length >= 2 ? parts[0][0] + parts[parts.length - 1][0] : name.slice(0, 2);
  return s.toUpperCase();
}

// The signed-in user's avatar (photo if present, else initials).
export function UserAvatar({ user }) {
  const src = user?.avatar_url || user?.avatar || user?.profile_photo_url || user?.profile_photo;
  if (src) return <img className="aeon-av-img" src={src} alt="" />;
  return <span>{initials(user)}</span>;
}

// Shared conversation body: the message stream (generative-UI blocks, typed
// once) + the smart composer. Used by both the slide-over drawer and the
// full-page /aeon console so they stay identical.
export default function AeonConversation({ messages, sending, onSend, onAction, user, hasAnimated, markAnimated, inputRef }) {
  const [draft, setDraft] = useState('');
  const streamRef = useRef(null);

  useEffect(() => {
    const el = streamRef.current;
    if (el) el.scrollTop = el.scrollHeight;
  }, [messages, sending]);

  const submit = (e) => { e.preventDefault(); onSend(draft); setDraft(''); };

  return (
    <>
      <div className="aeon-stream" ref={streamRef}>
        {messages.length === 0 ? (
          <div className="aeon-empty">
            <div className="aeon-empty-core"><AeonCore state="idle" size={72} /></div>
            <div className="aeon-empty-t">Ask Aeon anything</div>
            <div className="aeon-empty-d">Guidance, live data on any table, and I'll take you to the right page.</div>
            <div className="aeon-suggest">
              {SUGGESTIONS.map((s) => (
                <button type="button" className="aeon-suggest-card" key={s.text} onClick={() => onSend(s.text)}>
                  <span className="aeon-suggest-ico">{s.icon}</span>
                  <span>{s.text}</span>
                </button>
              ))}
            </div>
          </div>
        ) : (
          messages.map((m, i) => {
            const key = m.id ?? i;
            const animate = m.role !== 'user'
              && i === messages.length - 1
              && !(hasAnimated ? hasAnimated(key) : false);
            return (
              <div className={`aeon-turn ${m.role === 'user' ? 'is-me' : ''}`} key={key}>
                <div className={`aeon-av ${m.role === 'user' ? 'is-me' : 'is-ai'}`}>
                  {m.role === 'user' ? <UserAvatar user={user} /> : '✦'}
                </div>
                <div className="aeon-bubble">
                  <BlockRenderer blocks={m.blocks} onAction={onAction} animate={animate} onAnimated={() => markAnimated?.(key)} />
                </div>
              </div>
            );
          })
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
    </>
  );
}
