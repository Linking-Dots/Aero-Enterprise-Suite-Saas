import { AriaColorWheelOptions } from 'react-aria/useColorWheel';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps, StyleRenderProps } from './utils';
import { ColorWheelState } from 'react-stately/useColorWheelState';
import { GlobalDOMAttributes } from '@react-types/shared';
import React, { HTMLAttributes } from 'react';
export interface ColorWheelRenderProps {
    /**
     * Whether the color wheel is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the color wheel.
     */
    state: ColorWheelState;
}
export interface ColorWheelProps extends AriaColorWheelOptions, RenderProps<ColorWheelRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorWheel'
     */
    className?: ClassNameOrFunction<ColorWheelRenderProps>;
}
export declare const ColorWheelContext: React.Context<ContextValue<Partial<ColorWheelProps>, HTMLDivElement>>;
export declare const ColorWheelStateContext: React.Context<ColorWheelState | null>;
/**
 * A color wheel allows users to adjust the hue of an HSL or HSB color value on a circular track.
 */
export declare const ColorWheel: React.ForwardRefExoticComponent<ColorWheelProps & React.RefAttributes<HTMLDivElement>>;
export interface ColorWheelTrackRenderProps extends ColorWheelRenderProps {
}
export interface ColorWheelTrackProps extends StyleRenderProps<ColorWheelTrackRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorWheelTrack'
     */
    className?: ClassNameOrFunction<ColorWheelTrackRenderProps>;
}
interface ColorWheelTrackContextValue extends Omit<HTMLAttributes<HTMLElement>, 'children' | 'className' | 'style'>, StyleRenderProps<ColorWheelTrackRenderProps> {
}
export declare const ColorWheelTrackContext: React.Context<ContextValue<ColorWheelTrackContextValue, HTMLDivElement>>;
/**
 * A color wheel track renders a circular gradient track.
 */
export declare const ColorWheelTrack: React.ForwardRefExoticComponent<ColorWheelTrackProps & React.RefAttributes<HTMLDivElement>>;
export {};
