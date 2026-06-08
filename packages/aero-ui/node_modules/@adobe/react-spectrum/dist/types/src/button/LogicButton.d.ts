import { AriaBaseButtonProps, ButtonProps } from 'react-aria/useButton';
import { StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumLogicButtonProps extends AriaBaseButtonProps, Omit<ButtonProps, 'onClick'>, StyleProps {
    /** The type of boolean sequence to be represented by the LogicButton. */
    variant: 'and' | 'or';
}
/**
 * A LogicButton displays an operator within a boolean logic sequence.
 */
export declare const LogicButton: React.ForwardRefExoticComponent<SpectrumLogicButtonProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLButtonElement, HTMLButtonElement>>>;
