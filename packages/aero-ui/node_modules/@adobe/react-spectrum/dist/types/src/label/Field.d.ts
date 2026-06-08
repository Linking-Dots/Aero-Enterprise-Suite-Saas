import { SpectrumLabelPropsBase } from './Label';
import { SpectrumFieldValidation, SpectrumHelpTextProps, Validation, ValidationResult } from '@react-types/shared';
import React, { HTMLAttributes, LabelHTMLAttributes, ReactElement, ReactNode } from 'react';
export interface SpectrumFieldProps extends SpectrumLabelPropsBase, SpectrumHelpTextProps, Omit<Validation<any>, 'validationState'>, SpectrumFieldValidation<any>, Partial<ValidationResult> {
    children: ReactElement;
    label?: ReactNode;
    contextualHelp?: ReactNode;
    labelProps?: LabelHTMLAttributes<HTMLLabelElement>;
    descriptionProps?: HTMLAttributes<HTMLElement>;
    errorMessageProps?: HTMLAttributes<HTMLElement>;
    wrapperClassName?: string;
    wrapperProps?: HTMLAttributes<HTMLElement>;
}
export declare const Field: React.ForwardRefExoticComponent<SpectrumFieldProps & React.RefAttributes<HTMLElement>>;
