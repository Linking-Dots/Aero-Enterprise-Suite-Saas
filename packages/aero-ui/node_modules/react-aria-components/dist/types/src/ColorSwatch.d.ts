import { AriaColorSwatchProps } from 'react-aria/useColorSwatch';
import { ClassNameOrFunction, ContextValue, SlotProps, StyleRenderProps } from './utils';
import { Color } from 'react-stately/Color';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
export interface ColorSwatchRenderProps {
    /** The color of the swatch. */
    color: Color;
}
export interface ColorSwatchProps extends AriaColorSwatchProps, StyleRenderProps<ColorSwatchRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorSwatch'
     */
    className?: ClassNameOrFunction<ColorSwatchRenderProps>;
}
export declare const ColorSwatchContext: React.Context<ContextValue<ColorSwatchProps, HTMLDivElement>>;
/**
 * A ColorSwatch displays a preview of a selected color.
 */
export declare const ColorSwatch: React.ForwardRefExoticComponent<ColorSwatchProps & React.RefAttributes<HTMLDivElement>>;
