import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface KeyboardProps extends DOMProps, StyleProps {
    /**
     * Keyboard shortcut text.
     */
    children: ReactNode;
    /**
     * A slot to place the keyboard shortcut in.
     *
     * @default 'keyboard'
     */
    slot?: string;
}
/**
 * Keyboard represents text that specifies a keyboard command.
 */
export declare const Keyboard: React.ForwardRefExoticComponent<KeyboardProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
