import { AriaLabelingProps, GlobalDOMAttributes, HoverEvents, PressEvents, ValueBase } from '@react-types/shared';
import { ClassNameOrFunction, ContextValue, RenderProps, StyleRenderProps } from './utils';
import { Color } from 'react-stately/Color';
import { ListBoxItemRenderProps, ListBoxRenderProps } from './ListBox';
import React, { ReactNode } from 'react';
export interface ColorSwatchPickerRenderProps extends Omit<ListBoxRenderProps, 'isDropTarget'> {
}
export interface ColorSwatchPickerProps extends ValueBase<string | Color, Color>, AriaLabelingProps, StyleRenderProps<ColorSwatchPickerRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorSwatchPicker'
     */
    className?: ClassNameOrFunction<ColorSwatchPickerRenderProps>;
    /** The children of the ColorSwatchPicker. */
    children?: ReactNode;
    /**
     * Whether the items are arranged in a stack or grid.
     *
     * @default 'grid'
     */
    layout?: 'grid' | 'stack';
}
export declare const ColorSwatchPickerContext: React.Context<ContextValue<ColorSwatchPickerProps, HTMLDivElement>>;
/**
 * A ColorSwatchPicker displays a list of color swatches and allows a user to select one of them.
 */
export declare const ColorSwatchPicker: React.ForwardRefExoticComponent<ColorSwatchPickerProps & React.RefAttributes<HTMLDivElement>>;
export interface ColorSwatchPickerItemRenderProps extends Omit<ListBoxItemRenderProps, 'selectionMode' | 'selectionBehavior'> {
    /** The color of the swatch. */
    color: Color;
}
export interface ColorSwatchPickerItemProps extends RenderProps<ColorSwatchPickerItemRenderProps>, HoverEvents, PressEvents, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorSwatchPickerItem'
     */
    className?: ClassNameOrFunction<ColorSwatchPickerItemRenderProps>;
    /** The color of the swatch. */
    color: string | Color;
    /** Whether the color swatch is disabled. */
    isDisabled?: boolean;
}
export declare const ColorSwatchPickerItem: React.ForwardRefExoticComponent<ColorSwatchPickerItemProps & React.RefAttributes<HTMLDivElement>>;
