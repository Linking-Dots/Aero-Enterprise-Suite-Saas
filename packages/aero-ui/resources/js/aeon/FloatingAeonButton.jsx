import React from 'react';
import { IconButton } from '@aero/ui';

// Fixed ✨ launcher, bottom-right on every authenticated page.
export default function FloatingAeonButton({ onClick }) {
  return (
    <div className="aeon-fab">
      <IconButton icon="sparkles" label="Ask Aeon" intent="primary" onClick={onClick} />
    </div>
  );
}
