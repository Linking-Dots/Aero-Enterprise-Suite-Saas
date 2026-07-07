import { useCallback, useState } from 'react';
import { sendAeonMessage } from './aeonClient.js';

// Client-side chat state for Aeon. One turn = optimistic user bubble, then the
// assistant reply (rendered as generative-UI blocks).
export function useAeon() {
  const [messages, setMessages] = useState([]);
  const [conversationId, setConversationId] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [sending, setSending] = useState(false);

  const open = useCallback(() => setIsOpen(true), []);
  const close = useCallback(() => setIsOpen(false), []);

  const send = useCallback(async (text) => {
    const trimmed = (text || '').trim();
    if (!trimmed || sending) return;
    setMessages((m) => [...m, { role: 'user', blocks: [{ type: 'text', text: trimmed }] }]);
    setSending(true);
    try {
      const data = await sendAeonMessage({ message: trimmed, conversationId });
      setConversationId(data.conversation_id);
      setMessages((m) => [...m, { role: 'assistant', blocks: data.reply.blocks }]);
    } catch (e) {
      setMessages((m) => [
        ...m,
        { role: 'assistant', blocks: [{ type: 'text', text: 'Aeon is unavailable right now. Please try again.' }] },
      ]);
    } finally {
      setSending(false);
    }
  }, [conversationId, sending]);

  return { messages, isOpen, open, close, send, sending };
}
