import { Alignment } from '@react-types/shared';
import { MenuTriggerProps } from 'react-stately/useMenuTriggerState';
import React, { ReactElement } from 'react';
export interface SpectrumMenuTriggerProps extends MenuTriggerProps {
    /**
     * The contents of the MenuTrigger - a trigger and a Menu.
     */
    children: ReactElement[];
    /**
     * Alignment of the menu relative to the trigger.
     *
     * @default 'start'
     */
    align?: Alignment;
    /**
     * Where the Menu opens relative to its trigger.
     *
     * @default 'bottom'
     */
    direction?: 'bottom' | 'top' | 'left' | 'right' | 'start' | 'end';
    /**
     * Whether the menu should automatically flip direction when space is limited.
     *
     * @default true
     */
    shouldFlip?: boolean;
    /**
     * Whether the Menu closes when a selection is made.
     *
     * @default true
     */
    closeOnSelect?: boolean;
}
/**
 * The MenuTrigger serves as a wrapper around a Menu and its associated trigger,
 * linking the Menu's open state with the trigger's press state.
 */
export declare const MenuTrigger: React.ForwardRefExoticComponent<SpectrumMenuTriggerProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
