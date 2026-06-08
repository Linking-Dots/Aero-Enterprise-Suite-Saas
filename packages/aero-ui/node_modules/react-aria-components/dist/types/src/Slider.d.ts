import { AriaSliderProps, AriaSliderThumbProps } from 'react-aria/useSlider';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, RefObject } from '@react-types/shared';
import { HoverEvents, Orientation } from '@react-types/shared';
import React, { HTMLAttributes, OutputHTMLAttributes } from 'react';
import { SliderState } from 'react-stately/useSliderState';
export interface SliderProps<T = number | number[]> extends Omit<AriaSliderProps<T>, 'label'>, RenderProps<SliderRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Slider'
     */
    className?: ClassNameOrFunction<SliderRenderProps>;
    /**
     * The display format of the value label.
     */
    formatOptions?: Intl.NumberFormatOptions;
}
export declare const SliderContext: React.Context<ContextValue<SliderProps<number | number[]>, HTMLDivElement>>;
export declare const SliderStateContext: React.Context<SliderState | null>;
export declare const SliderTrackContext: React.Context<ContextValue<SliderTrackContextValue, HTMLDivElement>>;
export declare const SliderFillContext: React.Context<ContextValue<SliderFillProps, HTMLDivElement>>;
export declare const SliderOutputContext: React.Context<ContextValue<SliderOutputContextValue, HTMLOutputElement>>;
export interface SliderRenderProps {
    /**
     * The orientation of the slider.
     *
     * @selector [data-orientation="horizontal | vertical"]
     */
    orientation: Orientation;
    /**
     * Whether the slider is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the slider.
     */
    state: SliderState;
}
/**
 * A slider allows a user to select one or more values within a range.
 */
export declare const Slider: <T extends number | number[]>(props: SliderProps<T> & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface SliderOutputProps extends RenderProps<SliderRenderProps, 'output'>, GlobalDOMAttributes<HTMLOutputElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SliderOutput'
     */
    className?: ClassNameOrFunction<SliderRenderProps>;
}
interface SliderOutputContextValue extends Omit<OutputHTMLAttributes<HTMLOutputElement>, 'children' | 'className' | 'style'>, SliderOutputProps {
}
/**
 * A slider output displays the current value of a slider as text.
 */
export declare const SliderOutput: (props: SliderOutputProps & React.RefAttributes<HTMLOutputElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface SliderTrackRenderProps extends SliderRenderProps {
    /**
     * Whether the slider track is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
}
export interface SliderTrackProps extends HoverEvents, RenderProps<SliderTrackRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SliderTrack'
     */
    className?: ClassNameOrFunction<SliderTrackRenderProps>;
}
interface SliderTrackContextValue extends Omit<HTMLAttributes<HTMLDivElement>, 'children' | 'className' | 'style'>, SliderTrackProps {
}
/**
 * A slider track is a container for one or more slider thumbs.
 */
export declare const SliderTrack: (props: SliderTrackProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface SliderThumbRenderProps {
    /**
     * State of the slider.
     */
    state: SliderState;
    /**
     * Whether this thumb is currently being dragged.
     *
     * @selector [data-dragging]
     */
    isDragging: boolean;
    /**
     * Whether the thumb is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the thumb is currently focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the thumb is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the thumb is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
}
export interface SliderThumbProps extends Omit<AriaSliderThumbProps, 'label' | 'validationState'>, HoverEvents, RenderProps<SliderThumbRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SliderThumb'
     */
    className?: ClassNameOrFunction<SliderThumbRenderProps>;
    /**
     * A ref for the HTML input element.
     */
    inputRef?: RefObject<HTMLInputElement | null>;
}
/**
 * A slider thumb represents an individual value that the user can adjust within a slider track.
 */
export declare const SliderThumb: (props: SliderThumbProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface SliderFillRenderProps extends SliderRenderProps {
    /**
     * Whether the slider fill is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
}
export interface SliderFillProps extends HoverEvents, RenderProps<SliderFillRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The offset from which to start the fill.
     *
     * @default 0
     */
    offset?: number;
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SliderFill'
     */
    className?: ClassNameOrFunction<SliderFillRenderProps>;
}
/**
 * Displays the selected range.
 */
export declare const SliderFill: (props: SliderFillProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export {};
