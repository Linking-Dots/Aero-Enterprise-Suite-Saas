import { AriaColorSliderProps } from 'react-aria/useColorSlider';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { ColorSliderState } from 'react-stately/useColorSliderState';
import { GlobalDOMAttributes } from '@react-types/shared';
import { Orientation } from '@react-types/shared';
import React from 'react';
export interface ColorSliderRenderProps {
    /**
     * The orientation of the color slider.
     *
     * @selector [data-orientation="horizontal | vertical"]
     */
    orientation: Orientation;
    /**
     * Whether the color slider is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the color slider.
     */
    state: ColorSliderState;
}
export interface ColorSliderProps extends Omit<AriaColorSliderProps, 'label'>, RenderProps<ColorSliderRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorSlider'
     */
    className?: ClassNameOrFunction<ColorSliderRenderProps>;
}
export declare const ColorSliderContext: React.Context<ContextValue<Partial<ColorSliderProps>, HTMLDivElement>>;
export declare const ColorSliderStateContext: React.Context<ColorSliderState | null>;
/**
 * A color slider allows users to adjust an individual channel of a color value.
 */
export declare const ColorSlider: React.ForwardRefExoticComponent<ColorSliderProps & React.RefAttributes<HTMLDivElement>>;
