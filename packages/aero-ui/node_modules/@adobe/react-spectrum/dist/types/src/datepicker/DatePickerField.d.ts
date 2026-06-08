import { DateValue } from 'react-aria/useDateField';
import { JSX } from 'react';
import { SpectrumDatePickerProps } from './DatePicker';
interface DatePickerFieldProps<T extends DateValue> extends SpectrumDatePickerProps<T> {
    inputClassName?: string;
    hideValidationIcon?: boolean;
    maxGranularity?: SpectrumDatePickerProps<T>['granularity'];
}
export declare function DatePickerField<T extends DateValue>(props: DatePickerFieldProps<T>): JSX.Element;
export {};
