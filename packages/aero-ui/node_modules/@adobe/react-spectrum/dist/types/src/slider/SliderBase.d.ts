import { AriaSliderProps } from 'react-aria/useSlider';
import { LabelPosition, RefObject, StyleProps, ValueBase } from '@react-types/shared';
import React, { CSSProperties, ReactNode } from 'react';
import { SliderState } from 'react-stately/useSliderState';
export interface SpectrumBarSliderBase<T> extends AriaSliderProps<T>, ValueBase<T>, StyleProps {
    /**
     * The display format of the value label.
     */
    formatOptions?: Intl.NumberFormatOptions;
    /**
     * The label's overall position relative to the element it is labeling.
     *
     * @default 'top'
     */
    labelPosition?: LabelPosition;
    /**
     * Whether the value's label is displayed. True by default if there's a `label`, false by default
     * if not.
     */
    showValueLabel?: boolean;
    /**
     * A function that returns the content to display as the value's label. Overrides default
     * formatted number.
     */
    getValueLabel?: (value: T) => string;
    /**
     * A ContextualHelp element to place next to the label.
     */
    contextualHelp?: ReactNode;
}
export interface SliderBaseChildArguments {
    inputRef: RefObject<HTMLInputElement | null>;
    trackRef: RefObject<HTMLElement | null>;
    state: SliderState;
}
export interface SliderBaseProps<T = number[]> extends SpectrumBarSliderBase<T> {
    children: (opts: SliderBaseChildArguments) => ReactNode;
    classes?: string[] | Object;
    style?: CSSProperties;
}
export declare const SliderBase: React.ForwardRefExoticComponent<SliderBaseProps<number[]> & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLDivElement, HTMLDivElement>>>;
