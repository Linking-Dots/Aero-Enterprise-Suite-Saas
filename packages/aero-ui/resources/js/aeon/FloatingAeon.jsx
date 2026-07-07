import React from 'react';
import { usePage } from '@inertiajs/react';
import FloatingAeonButton from './FloatingAeonButton.jsx';
import AeonDrawer from './AeonDrawer.jsx';
import { useAeon } from './useAeon.js';

// Global Aeon entry: the ✨ launcher + slide-over drawer. Renders only for
// authenticated users (reads the shared `auth.user` Inertia prop).
export default function FloatingAeon() {
  const page = usePage();
  const user = page?.props?.auth?.user;
  const aeon = useAeon();

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
