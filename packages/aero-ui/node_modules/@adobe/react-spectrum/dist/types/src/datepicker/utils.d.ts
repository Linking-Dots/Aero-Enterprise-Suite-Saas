import { DateFormatter } from '@internationalized/date';
import { FocusableRef } from '@react-types/shared';
import { FormatterOptions } from 'react-stately/private/datepicker/utils';
import React, { ReactNode } from 'react';
import { SpectrumDatePickerBase } from './DatePicker';
export declare function useFormatHelpText(props: Pick<SpectrumDatePickerBase<any>, 'description' | 'showFormatHelpText'>): ReactNode;
export declare function useVisibleMonths(maxVisibleMonths: number): number;
export declare function useFocusManagerRef(ref: FocusableRef<HTMLElement>): React.RefObject<HTMLElement | null>;
export declare function useFormattedDateWidth(state: {
    getDateFormatter: (locale: string, formatOptions: FormatterOptions) => DateFormatter;
}): number;
