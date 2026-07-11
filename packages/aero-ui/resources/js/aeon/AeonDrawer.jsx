import React, { useEffect, useRef, useState } from 'react';
import AeonCore from './AeonCore.jsx';
import AeonAura from './AeonAura.jsx';
import AeonConversation from './AeonConversation.jsx';

const STATUS = { idle: 'Online', listening: 'Listening…', thinking: 'Thinking…', speaking: 'Speaking…' };

const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const IcoExpand = svg(<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" />);
const IcoCollapse = svg(<path d="M3 8h5V3M21 8h-5V3M3 16h5v5M21 16h-5v5" />);
const IcoClose = svg(<path d="M6 6l12 12M18 6L6 18" />);

// The Aeon "living console" as a slide-over: ambient aura, animated core header,
// and the shared conversation body (stream + composer).
export default function AeonDrawer({ isOpen, onClose, messages, sending, stage, usage, onSend, onAction, onFeedback, user, hasAnimated, markAnimated }) {
  const [expanded, setExpanded] = useState(false);
  const inputRef = useRef(null);
  const state = sending ? 'thinking' : 'listening';

  useEffect(() => {
    if (!isOpen) return;
    const onKey = (e) => { if (e.key === 'Escape') onClose?.(); };
    window.addEventListener('keydown', onKey);
    const id = setTimeout(() => inputRef.current?.focus(), 60);
    return () => { window.removeEventListener('keydown', onKey); clearTimeout(id); };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

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

        <AeonConversation
          messages={messages}
          sending={sending}
          stage={stage}
          usage={usage}
          onSend={onSend}
          onAction={onAction}
          onFeedback={onFeedback}
          user={user}
          hasAnimated={hasAnimated}
          markAnimated={markAnimated}
          inputRef={inputRef}
        />
      </section>
    </div>
  );
}
