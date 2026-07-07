import React from 'react';
import { Text } from '@aero/ui';

// Renders Aeon's generative-UI blocks. M1: text only; unknown types degrade to
// text. Later milestones add stat/table/chart/form/options/chips.
export default function BlockRenderer({ blocks = [] }) {
  return (
    <>
      {blocks.map((block, i) => {
        switch (block.type) {
          case 'text':
          default:
            return <Text key={i}>{block.text ?? ''}</Text>;
        }
      })}
    </>
  );
}
