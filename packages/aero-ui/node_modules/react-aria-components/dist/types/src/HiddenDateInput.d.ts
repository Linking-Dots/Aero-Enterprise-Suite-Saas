import { DateFieldState } from 'react-stately/useDateFieldState';
import { DatePickerState } from 'react-stately/useDatePickerState';
import React, { ReactNode } from 'react';
interface AriaHiddenDateInputProps {
    /**
     * Describes the type of autocomplete functionality the input should provide if any. See
     * [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#htmlattrdefautocomplete).
     */
    autoComplete?: string;
    /** HTML form input name. */
    name?: string;
    /** Sets the disabled state of the input. */
    isDisabled?: boolean;
}
interface HiddenDateInputProps extends AriaHiddenDateInputProps {
    /**
     * State for the input.
     */
    state: DateFieldState | DatePickerState;
}
export interface HiddenDateAria {
    /** Props for the container element. */
    containerProps: React.HTMLAttributes<HTMLDivElement>;
    /** Props for the hidden input element. */
    inputProps: React.InputHTMLAttributes<HTMLInputElement>;
}
export declare function useHiddenDateInput(props: HiddenDateInputProps, state: DateFieldState | DatePickerState): HiddenDateAria;
export declare function HiddenDateInput(props: HiddenDateInputProps): ReactNode | null;
export {};
