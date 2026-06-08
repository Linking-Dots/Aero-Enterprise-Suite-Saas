import { AriaRadioProps } from 'react-aria/useRadioGroup';
import { StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumRadioProps extends Omit<AriaRadioProps, 'onClick'>, StyleProps {
}
/**
 * Radio buttons allow users to select a single option from a list of mutually exclusive options.
 * All possible options are exposed up front for users to compare.
 */
export declare const Radio: React.ForwardRefExoticComponent<SpectrumRadioProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLLabelElement, HTMLLabelElement>>>;
