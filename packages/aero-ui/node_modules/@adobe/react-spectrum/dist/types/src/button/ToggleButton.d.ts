import React from 'react';
import { SpectrumActionButtonProps } from './ActionButton';
import { ToggleButtonProps } from 'react-aria/useToggleButton';
export interface SpectrumToggleButtonProps extends Omit<ToggleButtonProps, 'onClick'>, Omit<SpectrumActionButtonProps, 'aria-current' | 'type' | 'form' | 'formAction' | 'formEncType' | 'formMethod' | 'formNoValidate' | 'formTarget' | 'name' | 'value'> {
    /**
     * Whether the button should be displayed with an [emphasized
     * style](https://spectrum.adobe.com/page/action-button/#Emphasis).
     */
    isEmphasized?: boolean;
}
/**
 * ToggleButtons allow users to toggle a selection on or off, for example
 * switching between two states or modes.
 */
export declare const ToggleButton: React.ForwardRefExoticComponent<SpectrumToggleButtonProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLButtonElement, HTMLButtonElement>>>;
