import { AriaDateFieldProps, DateValue, MappedDateValue } from 'react-aria/useDateField';
import { FocusableRef, HelpTextProps, SpectrumFieldValidation, SpectrumLabelableProps, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumDateFieldBase<T extends DateValue> extends SpectrumLabelableProps, HelpTextProps, SpectrumFieldValidation<MappedDateValue<T>>, StyleProps {
    /**
     * Whether the date picker should be displayed with a quiet style.
     *
     * @default false
     */
    isQuiet?: boolean;
    /**
     * Whether to show the localized date format as help text below the field.
     *
     * @default false
     */
    showFormatHelpText?: boolean;
}
export interface SpectrumDateFieldProps<T extends DateValue> extends Omit<AriaDateFieldProps<T>, 'isInvalid' | 'validationState' | 'autoComplete'>, SpectrumDateFieldBase<T> {
}
/**
 * DateFields allow users to enter and edit date and time values using a keyboard.
 * Each part of a date value is displayed in an individually editable segment.
 */
export declare const DateField: <T extends DateValue>(props: SpectrumDateFieldProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
