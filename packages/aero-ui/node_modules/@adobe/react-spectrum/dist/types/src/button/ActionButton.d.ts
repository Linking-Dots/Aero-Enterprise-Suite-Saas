import { AriaBaseButtonProps, ButtonProps } from 'react-aria/useButton';
import { StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumActionButtonProps extends AriaBaseButtonProps, Omit<ButtonProps, 'onClick'>, StyleProps {
    /**
     * Whether the button should be displayed with a [quiet
     * style](https://spectrum.adobe.com/page/action-button/#Quiet).
     */
    isQuiet?: boolean;
    /** The static color style to apply. Useful when the button appears over a color background. */
    staticColor?: 'white' | 'black';
}
/**
 * ActionButtons allow users to perform an action. They’re used for similar, task-based options
 * within a workflow, and are ideal for interfaces where buttons aren’t meant to draw a lot of
 * attention.
 */
export declare const ActionButton: React.ForwardRefExoticComponent<SpectrumActionButtonProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLButtonElement, HTMLButtonElement>>>;
