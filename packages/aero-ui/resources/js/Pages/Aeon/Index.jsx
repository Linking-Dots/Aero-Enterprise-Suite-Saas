import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Card, VStack, HStack, Input, Button, Heading, Text, EmptyState } from '@aero/ui';
import App from '@/Pages/App.jsx';
import BlockRenderer from '@/aeon/BlockRenderer.jsx';
import { useAeon } from '@/aeon/useAeon.js';

export default function AeonPage() {
  const aeon = useAeon();
  const [draft, setDraft] = useState('');

  const submit = (e) => {
    e.preventDefault();
    aeon.send(draft);
    setDraft('');
  };

  return (
    <>
      <Head title="Aeon" />
      <VStack gap={4}>
        <Heading>Aeon</Heading>

        {aeon.messages.length === 0 ? (
          <EmptyState title="Ask Aeon anything" description="Your AEOS365 AI assistant." />
        ) : (
          aeon.messages.map((m, i) => (
            <Card key={i} className={`aeon-msg aeon-msg--${m.role}`}>
              <BlockRenderer blocks={m.blocks} />
            </Card>
          ))
        )}
        {aeon.sending && <Text muted>Aeon is thinking…</Text>}

        <form onSubmit={submit} className="aeon-composer">
          <HStack gap={2}>
            <Input
              value={draft}
              onChange={(e) => setDraft(e.target.value)}
              placeholder="Message Aeon…"
              disabled={aeon.sending}
              aria-label="Message Aeon"
            />
            <Button type="submit" disabled={aeon.sending || !draft.trim()}>Send</Button>
          </HStack>
        </form>
      </VStack>
    </>
  );
}

AeonPage.layout = (page) => <App title="Aeon">{page}</App>;
