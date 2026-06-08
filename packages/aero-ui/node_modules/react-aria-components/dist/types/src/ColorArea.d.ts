import { AriaColorAreaProps } from 'react-aria/useColorArea';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { ColorAreaState } from 'react-stately/useColorAreaState';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
export interface ColorAreaRenderProps {
    /**
     * Whether the color area is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the color area.
     */
    state: ColorAreaState;
}
export interface ColorAreaProps extends AriaColorAreaProps, RenderProps<ColorAreaRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorArea'
     */
    className?: ClassNameOrFunction<ColorAreaRenderProps>;
}
export declare const ColorAreaContext: React.Context<ContextValue<Partial<ColorAreaProps>, HTMLDivElement>>;
export declare const ColorAreaStateContext: React.Context<ColorAreaState | null>;
/**
 * A color area allows users to adjust two channels of an RGB, HSL or HSB color value against a
 * two-dimensional gradient background.
 */
export declare const ColorArea: React.ForwardRefExoticComponent<ColorAreaProps & React.RefAttributes<HTMLDivElement>>;
