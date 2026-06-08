import { AriaCheckboxProps } from 'react-aria/useCheckbox';
import { StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumCheckboxProps extends Omit<AriaCheckboxProps, 'onClick'>, StyleProps {
    /**
     * This prop sets the emphasized style which provides visual prominence.
     */
    isEmphasized?: boolean;
    /**
     * A slot name for the component. Slots allow the component to receive props from a parent
     * component. An explicit `null` value indicates that the local props completely override all
     * props received from a parent.
     *
     * @private
     */
    slot?: string | null;
}
/**
 * Checkboxes allow users to select multiple items from a list of individual items,
 * or to mark one individual item as selected.
 */
export declare const Checkbox: React.ForwardRefExoticComponent<SpectrumCheckboxProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLLabelElement, HTMLLabelElement>>>;
