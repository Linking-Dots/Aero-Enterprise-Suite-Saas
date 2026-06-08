import { AriaDatePickerProps } from 'react-aria/useDatePicker';
import { DateFieldState, DateSegment } from 'react-stately/useDateFieldState';
import { JSX } from 'react';
interface DatePickerSegmentProps extends AriaDatePickerProps<any> {
    segment: DateSegment;
    state: DateFieldState;
}
export declare function DatePickerSegment({ segment, state, ...otherProps }: DatePickerSegmentProps): JSX.Element;
export {};
