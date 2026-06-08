import { DOMProps, FormProps, SpectrumLabelableProps, StyleProps, ValidationState } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumFormProps extends FormProps, DOMProps, StyleProps, Omit<SpectrumLabelableProps, 'contextualHelp' | 'label'> {
    /** The contents of the Form. */
    children: ReactElement<SpectrumLabelableProps> | ReactElement<SpectrumLabelableProps>[];
    /** Whether the Form elements are displayed with their quiet style. */
    isQuiet?: boolean;
    /** Whether the Form elements are rendered with their emphasized style. */
    isEmphasized?: boolean;
    /** Whether the Form elements are disabled. */
    isDisabled?: boolean;
    /** Whether user input is required on each of the Form elements before Form submission. */
    isRequired?: boolean;
    /** Whether the Form elements can be selected but not changed by the user. */
    isReadOnly?: boolean;
    /**
     * Whether the Form elements should display their "valid" or "invalid" visual styling.
     *
     * @default 'valid'
     */
    validationState?: ValidationState;
    /**
     * Whether to use native HTML form validation to prevent form submission
     * when a field value is missing or invalid, or mark fields as required
     * or invalid via ARIA.
     *
     * @default 'aria'
     */
    validationBehavior?: 'aria' | 'native';
}
export declare function useFormProps<T extends SpectrumLabelableProps>(props: T): T;
/**
 * Forms allow users to enter data that can be submitted while providing alignment and styling for
 * form fields.
 */
export declare const Form: React.ForwardRefExoticComponent<SpectrumFormProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLFormElement>>>;
