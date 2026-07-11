import React from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import AeonAura from '@/aeon/AeonAura.jsx';
import AeonCore from '@/aeon/AeonCore.jsx';
import AeonConversation from '@/aeon/AeonConversation.jsx';
import { useAeon } from '@/aeon/useAeon.js';

// The dedicated /aeon page — the living console rendered full-height (same
// engine + blocks as the drawer, via the shared AeonConversation).
export default function AeonPage() {
  const aeon = useAeon();
  const user = usePage().props?.auth?.user;
  const state = aeon.sending ? 'thinking' : 'listening';

  const onAction = (evt) => {
    const route = evt?.block?.route;
    if (route && (evt.kind === 'confirm' || evt.kind === 'navigate')) {
      router.visit(route);
    }
  };

  return (
    <>
      <Head title="Aeon" />
      <div className="aeon-page">
        <section className="aeon-console is-page">
          <AeonAura />
          <header className="aeon-head">
            <div className="aeon-head-core"><AeonCore state={state} size={44} /></div>
            <div className="aeon-head-id">
              <span className="aeon-head-name">Aeon <span className="aeon-badge">AI</span></span>
              <span className="aeon-status"><span className="aeon-status-dot" /> {aeon.sending ? 'Thinking…' : 'Online'}</span>
            </div>
          </header>

          <AeonConversation
            messages={aeon.messages}
            sending={aeon.sending}
            stage={aeon.stage}
            usage={aeon.usage}
            onSend={aeon.send}
            onAction={onAction}
            onFeedback={aeon.feedback}
            user={user}
            hasAnimated={aeon.hasAnimated}
            markAnimated={aeon.markAnimated}
          />
        </section>
      </div>
    </>
  );
}

AeonPage.layout = (page) => <App title="Aeon">{page}</App>;
