import React, { useCallback, useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import FloatingAeonButton from './FloatingAeonButton.jsx';
import AeonDrawer from './AeonDrawer.jsx';
import { useAeon } from './useAeon.js';

// Read the currently-shared auth user WITHOUT usePage(): this component is
// mounted at the app root (a sibling of Inertia's <App>), so it has no Inertia
// context. Instead we read the initial page JSON off the root `data-page`
// element and track subsequent SPA visits via the router `navigate` event.
function readAuthUser() {
  try {
    const el = document.querySelector('[data-page]');
    if (!el) return null;
    return JSON.parse(el.dataset.page)?.props?.auth?.user ?? null;
  } catch {
    return null;
  }
}

// Aeon is a tenant/standalone feature — the platform admin shares no `aeon`
// prop, so the launcher stays hidden there. Read it off the same data-page.
function readAeonAvailable(page) {
  if (page && page.props) return page.props?.aeon?.available === true;
  try {
    const el = document.querySelector('[data-page]');
    if (!el) return false;
    return JSON.parse(el.dataset.page)?.props?.aeon?.available === true;
  } catch {
    return false;
  }
}

// Global Aeon entry: the ✨ launcher + slide-over drawer. Renders only for
// authenticated users.
export default function FloatingAeon() {
  const [user, setUser] = useState(readAuthUser);
  const [available, setAvailable] = useState(readAeonAvailable);
  const aeon = useAeon();

  useEffect(() => {
    return router.on('navigate', (event) => {
      const page = event?.detail?.page;
      setUser(page?.props?.auth?.user ?? null);
      setAvailable(readAeonAvailable(page));
    });
  }, []);

  // Guided action: a confirmed navigate directive routes the user to the real
  // page (Inertia SPA visit) and closes the drawer.
  const onAction = useCallback((evt) => {
    const route = evt?.block?.route;
    if (route && (evt.kind === 'confirm' || evt.kind === 'navigate')) {
      aeon.close();
      router.visit(route);
    }
  }, [aeon]);

  if (!user || !available) return null;

  return (
    <>
      <FloatingAeonButton onClick={aeon.open} />
      <AeonDrawer
        isOpen={aeon.isOpen}
        onClose={aeon.close}
        messages={aeon.messages}
        sending={aeon.sending}
        stage={aeon.stage}
        usage={aeon.usage}
        onSend={aeon.send}
        user={user}
        onAction={onAction}
        onFeedback={aeon.feedback}
        hasAnimated={aeon.hasAnimated}
        markAnimated={aeon.markAnimated}
      />
    </>
  );
}
