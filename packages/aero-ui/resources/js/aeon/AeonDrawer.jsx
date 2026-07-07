import React, { useState } from 'react';
import { Drawer, VStack, HStack, Card, Text, Input, Button, EmptyState } from '@aero/ui';
import BlockRenderer from './BlockRenderer.jsx';

// Slide-over Aeon chat. Messages scroll in the body; the composer sits in the
// drawer footer.
export default function AeonDrawer({ isOpen, onClose, messages, sending, onSend }) {
  const [draft, setDraft] = useState('');

  const submit = (e) => {
    e.preventDefault();
    onSend(draft);
    setDraft('');
  };

  const composer = (
    <form onSubmit={submit} className="aeon-composer">
      <HStack gap={2}>
        <Input
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          placeholder="Message Aeon…"
          disabled={sending}
          aria-label="Message Aeon"
        />
        <Button type="submit" disabled={sending || !draft.trim()}>Send</Button>
      </HStack>
    </form>
  );

  return (
    <Drawer open={isOpen} onClose={onClose} title="Aeon" side="right" footer={composer}>
      <VStack gap={3}>
        {messages.length === 0 ? (
          <EmptyState
            title="Ask Aeon anything"
            description="How do I add an employee? Where are billing settings?"
          />
        ) : (
          messages.map((m, i) => (
            <Card key={i} className={`aeon-msg aeon-msg--${m.role}`}>
              <BlockRenderer blocks={m.blocks} />
            </Card>
          ))
        )}
        {sending && <Text muted>Aeon is thinking…</Text>}
      </VStack>
    </Drawer>
  );
}
