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

const IcoThumbUp = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
    <path d="M7 10v11H4a1 1 0 01-1-1v-9a1 1 0 011-1h3zm0 0l4.5-7a2 2 0 011.8 2.6L12.5 9H19a2 2 0 012 2.4l-1.3 7A2 2 0 0117.7 20H7" />
  </svg>
);
const IcoThumbDown = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
    <path d="M17 14V3h3a1 1 0 011 1v9a1 1 0 01-1 1h-3zm0 0l-4.5 7a2 2 0 01-1.8-2.6l.8-3.4H5a2 2 0 01-2-2.4l1.3-7A2 2 0 016.3 4H17" />
  </svg>
);

// Shared conversation body: the message stream (generative-UI blocks, typed
// once) + the smart composer. Used by both the slide-over drawer and the
// full-page /aeon console so they stay identical. `stage` narrates the agent
// loop while sending; `onFeedback(id, 1|-1)` records thumbs on a reply.
const MODEL_LABEL = { flash: 'Aeon · Flash', pro: 'Aeon · Pro', all: 'Aeon · Pro' };

export default function AeonConversation({ messages, sending, stage, usage, onSend, onAction, onFeedback, user, hasAnimated, markAnimated, inputRef }) {
  const [draft, setDraft] = useState('');
  const streamRef = useRef(null);

  useEffect(() => {
    const el = streamRef.current;
    if (el) el.scrollTop = el.scrollHeight;
  }, [messages, sending, stage]);

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
                  {m.role !== 'user' && m.dbId && onFeedback ? (
                    <div className="aeon-fb" aria-label="Was this helpful?">
                      <button
                        type="button"
                        className={`aeon-fb-btn ${m.feedback === 1 ? 'is-on' : ''}`}
                        title="Helpful"
                        aria-label="Helpful"
                        aria-pressed={m.feedback === 1}
                        onClick={() => onFeedback(m.id, 1)}
                      >
                        {IcoThumbUp}
                      </button>
                      <button
                        type="button"
                        className={`aeon-fb-btn ${m.feedback === -1 ? 'is-on is-down' : ''}`}
                        title="Not helpful"
                        aria-label="Not helpful"
                        aria-pressed={m.feedback === -1}
                        onClick={() => onFeedback(m.id, -1)}
                      >
                        {IcoThumbDown}
                      </button>
                    </div>
                  ) : null}
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
              <span>{stage || 'Aeon is thinking…'}</span>
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
          <span className="aeon-model"><span className="aeon-model-g" /> {MODEL_LABEL[usage?.model] || 'Aeon AI'}</span>
          <span className="aeon-cfoot-sp" />
          {usage && !usage.unlimited && usage.limit > 0 ? (
            <span className={`aeon-quota ${usage.remaining <= 0 ? 'is-out' : usage.remaining <= usage.limit * 0.1 ? 'is-low' : ''}`}>
              {Math.max(0, usage.remaining)} of {usage.limit} left this month
            </span>
          ) : (
            <span>Guarded by your access</span>
          )}
        </div>
      </form>
    </>
  );
}
