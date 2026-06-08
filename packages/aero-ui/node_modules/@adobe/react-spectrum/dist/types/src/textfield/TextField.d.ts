import { AriaTextFieldProps } from 'react-aria/useTextField';
import { FocusableRefValue, SpectrumFieldValidation, SpectrumLabelableProps, SpectrumTextInputBase, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumTextFieldProps extends SpectrumTextInputBase, Omit<AriaTextFieldProps, 'isInvalid' | 'validationState'>, SpectrumFieldValidation<string>, SpectrumLabelableProps, StyleProps {
    /** An icon to display at the start of the input. */
    icon?: ReactElement | null;
    /** Whether the input should be displayed with a quiet style. */
    isQuiet?: boolean;
}
export interface TextFieldRef<T extends HTMLInputElement | HTMLTextAreaElement = HTMLInputElement> extends FocusableRefValue<T, HTMLDivElement> {
    select(): void;
    getInputElement(): T | null;
}
/**
 * TextFields are text inputs that allow users to input custom text entries
 * with a keyboard. Various decorations can be displayed around the field to
 * communicate the entry requirements.
 */
export declare const TextField: React.ForwardRefExoticComponent<SpectrumTextFieldProps & React.RefAttributes<TextFieldRef<HTMLInputElement>>>;
