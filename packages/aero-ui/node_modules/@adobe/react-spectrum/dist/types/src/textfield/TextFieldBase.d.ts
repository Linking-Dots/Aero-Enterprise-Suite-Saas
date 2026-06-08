import { PressEvents, RefObject, ValidationResult } from '@react-types/shared';
import React, { HTMLAttributes, InputHTMLAttributes, LabelHTMLAttributes, ReactElement, TextareaHTMLAttributes } from 'react';
import { SpectrumTextFieldProps, TextFieldRef } from './TextField';
interface TextFieldBaseProps extends Omit<SpectrumTextFieldProps, 'onChange' | 'validate'>, PressEvents, Partial<ValidationResult> {
    wrapperChildren?: ReactElement | ReactElement[];
    inputClassName?: string;
    validationIconClassName?: string;
    multiLine?: boolean;
    labelProps?: LabelHTMLAttributes<HTMLLabelElement>;
    inputProps: InputHTMLAttributes<HTMLInputElement> | TextareaHTMLAttributes<HTMLTextAreaElement>;
    descriptionProps?: HTMLAttributes<HTMLElement>;
    errorMessageProps?: HTMLAttributes<HTMLElement>;
    inputRef?: RefObject<HTMLInputElement | HTMLTextAreaElement | null>;
    loadingIndicator?: ReactElement;
    isLoading?: boolean;
    disableFocusRing?: boolean;
}
export declare const TextFieldBase: React.ForwardRefExoticComponent<TextFieldBaseProps & React.RefAttributes<TextFieldRef<HTMLInputElement | HTMLTextAreaElement>>>;
export {};
