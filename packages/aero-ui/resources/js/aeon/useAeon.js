import { useCallback, useRef, useState } from 'react';
import { sendAeonMessage } from './aeonClient.js';

// Client-side chat state for Aeon. One turn = optimistic user bubble, then the
// assistant reply (rendered as generative-UI blocks). Each message gets a stable
// id; `animatedRef` remembers which replies have already played their typewriter
// so re-opening the drawer never re-types an old answer.
export function useAeon() {
  const [messages, setMessages] = useState([]);
  const [conversationId, setConversationId] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [sending, setSending] = useState(false);
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
    try {
      const data = await sendAeonMessage({ message: trimmed, conversationId });
      setConversationId(data.conversation_id);
      setMessages((m) => [...m, { id: idRef.current++, role: 'assistant', blocks: data.reply.blocks }]);
    } catch (e) {
      setMessages((m) => [
        ...m,
        { id: idRef.current++, role: 'assistant', blocks: [{ type: 'text', text: 'Aeon is unavailable right now. Please try again.' }] },
      ]);
    } finally {
      setSending(false);
    }
  }, [conversationId, sending]);

  return { messages, isOpen, open, close, send, sending, hasAnimated, markAnimated };
}
