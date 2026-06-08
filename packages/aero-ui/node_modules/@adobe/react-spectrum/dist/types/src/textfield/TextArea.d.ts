import { AriaTextFieldProps } from 'react-aria/useTextField';
import React, { ReactElement } from 'react';
import { SpectrumFieldValidation, SpectrumLabelableProps, SpectrumTextInputBase, StyleProps } from '@react-types/shared';
import { TextFieldRef } from './TextField';
export interface SpectrumTextAreaProps extends SpectrumTextInputBase, Omit<AriaTextFieldProps<HTMLTextAreaElement>, 'isInvalid' | 'validationState' | 'type' | 'pattern'>, SpectrumFieldValidation<string>, SpectrumLabelableProps, StyleProps {
    /** An icon to display at the start of the input. */
    icon?: ReactElement | null;
    /** Whether the input should be displayed with a quiet style. */
    isQuiet?: boolean;
}
/**
 * TextAreas are multiline text inputs, useful for cases where users have
 * a sizable amount of text to enter. They allow for all customizations that
 * are available to text fields.
 */
export declare const TextArea: React.ForwardRefExoticComponent<SpectrumTextAreaProps & React.RefAttributes<TextFieldRef<HTMLTextAreaElement>>>;
