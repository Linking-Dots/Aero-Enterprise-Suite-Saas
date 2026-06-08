import { AriaTimeFieldProps, MappedTimeValue, TimeValue } from 'react-aria/useTimeField';
import { FocusableRef, InputDOMProps, SpectrumFieldValidation, SpectrumLabelableProps, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumTimeFieldProps<T extends TimeValue> extends Omit<AriaTimeFieldProps<T>, 'isInvalid' | 'validationState'>, SpectrumFieldValidation<MappedTimeValue<T>>, SpectrumLabelableProps, StyleProps, InputDOMProps {
    /**
     * Whether the time field should be displayed with a quiet style.
     *
     * @default false
     */
    isQuiet?: boolean;
}
/**
 * TimeFields allow users to enter and edit time values using a keyboard.
 * Each part of the time is displayed in an individually editable segment.
 */
export declare const TimeField: <T extends TimeValue>(props: SpectrumTimeFieldProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
