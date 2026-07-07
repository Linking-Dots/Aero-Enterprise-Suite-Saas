import React from 'react';
import { IconButton, Icon } from '@aero/ui';

// Fixed ✨ launcher, bottom-right on every authenticated page.
export default function FloatingAeonButton({ onClick }) {
  return (
    <div className="aeon-fab">
      <IconButton icon={<Icon name="sparkles" />} label="Ask Aeon" intent="primary" onClick={onClick} />
    </div>
  );
}
