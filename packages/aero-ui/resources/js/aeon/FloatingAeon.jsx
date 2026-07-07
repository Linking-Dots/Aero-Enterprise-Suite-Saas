import React, { useEffect, useState } from 'react';
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

// Global Aeon entry: the ✨ launcher + slide-over drawer. Renders only for
// authenticated users.
export default function FloatingAeon() {
  const [user, setUser] = useState(readAuthUser);
  const aeon = useAeon();

  useEffect(() => {
    return router.on('navigate', (event) => {
      setUser(event?.detail?.page?.props?.auth?.user ?? null);
    });
  }, []);

  if (!user) return null;

  return (
    <>
      <FloatingAeonButton onClick={aeon.open} />
      <AeonDrawer
        isOpen={aeon.isOpen}
        onClose={aeon.close}
        messages={aeon.messages}
        sending={aeon.sending}
        onSend={aeon.send}
      />
    </>
  );
}
