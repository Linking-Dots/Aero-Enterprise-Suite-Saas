import { useCallback, useRef, useState } from 'react';
import { sendAeonMessage, sendAeonMessageStream, sendAeonFeedback } from './aeonClient.js';

// Client-side chat state for Aeon. One turn = optimistic user bubble, then the
// assistant reply (rendered as generative-UI blocks). Each message gets a stable
// id; `animatedRef` remembers which replies have already played their typewriter
// so re-opening the drawer never re-types an old answer. Sending uses the SSE
// stream (live stage narration) and falls back to the plain JSON endpoint.
// Initial AI allowance shared on the page (aeon.usage), so the drawer reflects
// the tenant's monthly quota before the first message is even sent.
function readInitialUsage() {
  try {
    const el = document.querySelector('[data-page]');
    return el ? (JSON.parse(el.dataset.page)?.props?.aeon?.usage ?? null) : null;
  } catch {
    return null;
  }
}

export function useAeon() {
  const [messages, setMessages] = useState([]);
  const [conversationId, setConversationId] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [sending, setSending] = useState(false);
  const [stage, setStage] = useState('');
  const [usage, setUsage] = useState(readInitialUsage);
  const idRef = useRef(1);
  const animatedRef = useRef(new Set());

  const open = useCallback(() => setIsOpen(true), []);
  const close = useCallback(() => setIsOpen(false), []);
  const hasAnimated = useCallback((id) => animatedRef.current.has(id), []);
  const markAnimated = useCallback((id) => { animatedRef.current.add(id); }, []);

  const send = useCallback(async (text) => {
    const trimmed = (text || '').trim();
    if (!trimmed || sending) return;
    setMessages((m) => [...m, { id: idRef.current++, role: 'user', blocks: [{ type: 'text', text: trimmed }] }]);
    setSending(true);
    setStage('');
    try {
      let data;
      try {
        data = await sendAeonMessageStream({ message: trimmed, conversationId, onStage: setStage });
      } catch (e) {
        // Stream blocked (proxy/buffering) — same turn over plain JSON.
        data = await sendAeonMessage({ message: trimmed, conversationId });
      }
      setConversationId(data.conversation_id);
      if (data.usage !== undefined) setUsage(data.usage);
      setMessages((m) => [...m, {
        id: idRef.current++,
        dbId: data.reply.id ?? null,
        role: 'assistant',
        blocks: data.reply.blocks,
        feedback: null,
      }]);
    } catch (e) {
      setMessages((m) => [
        ...m,
        { id: idRef.current++, role: 'assistant', blocks: [{ type: 'text', text: 'Aeon is unavailable right now. Please try again.' }] },
      ]);
    } finally {
      setSending(false);
      setStage('');
    }
  }, [conversationId, sending]);

  const feedback = useCallback(async (id, value) => {
    const msg = messages.find((m) => m.id === id);
    if (!msg || !msg.dbId) return;
    const next = msg.feedback === value ? 0 : value; // tap again to clear
    setMessages((m) => m.map((x) => (x.id === id ? { ...x, feedback: next === 0 ? null : next } : x)));
    await sendAeonFeedback({ messageId: msg.dbId, value: next });
  }, [messages]);

  return { messages, isOpen, open, close, send, sending, stage, usage, feedback, hasAnimated, markAnimated };
}
