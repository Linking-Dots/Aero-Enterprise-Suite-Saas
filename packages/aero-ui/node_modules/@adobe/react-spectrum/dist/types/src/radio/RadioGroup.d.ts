import { AriaRadioGroupProps, RadioProps } from 'react-aria/useRadioGroup';
import { SpectrumHelpTextProps, SpectrumLabelableProps, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumRadioGroupProps extends AriaRadioGroupProps, SpectrumLabelableProps, StyleProps, SpectrumHelpTextProps {
    /**
     * The Radio(s) contained within the RadioGroup.
     */
    children: ReactElement<RadioProps> | ReactElement<RadioProps>[];
    /**
     * By default, radio buttons are not emphasized (gray).
     * The emphasized (blue) version provides visual prominence.
     */
    isEmphasized?: boolean;
}
/**
 * Radio groups allow users to select a single option from a list of mutually exclusive options.
 * All possible options are exposed up front for users to compare.
 */
export declare const RadioGroup: React.ForwardRefExoticComponent<SpectrumRadioGroupProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
