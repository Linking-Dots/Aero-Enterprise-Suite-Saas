import { AriaCheckboxGroupProps } from 'react-aria/useCheckboxGroup';
import { Orientation, SpectrumHelpTextProps, SpectrumLabelableProps, StyleProps, Validation } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { SpectrumCheckboxProps } from './Checkbox';
export interface SpectrumCheckboxGroupProps extends AriaCheckboxGroupProps, SpectrumLabelableProps, Validation<string[]>, StyleProps, SpectrumHelpTextProps {
    /**
     * The Checkboxes contained within the CheckboxGroup.
     */
    children: ReactElement<SpectrumCheckboxProps> | ReactElement<SpectrumCheckboxProps>[];
    /**
     * The axis the checkboxes should align with.
     *
     * @default 'vertical'
     */
    orientation?: Orientation;
    /**
     * By default, checkboxes are not emphasized (gray).
     * The emphasized (blue) version provides visual prominence.
     */
    isEmphasized?: boolean;
}
/**
 * A CheckboxGroup allows users to select one or more items from a list of choices.
 */
export declare const CheckboxGroup: React.ForwardRefExoticComponent<SpectrumCheckboxGroupProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
