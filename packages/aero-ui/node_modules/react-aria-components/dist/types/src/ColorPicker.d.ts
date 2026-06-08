import { ChildrenOrFunction, SlotProps, SlottedContextValue } from './utils';
import { Color } from 'react-stately/Color';
import { ColorPickerState, ColorPickerProps as StatelyColorPickerProps } from 'react-stately/useColorPickerState';
import React, { JSX } from 'react';
export interface ColorPickerRenderProps {
    /** The currently selected color. */
    color: Color;
}
export interface ColorPickerProps extends StatelyColorPickerProps, SlotProps {
    /**
     * The children of the component. A function may be provided to alter the children based on
     * component state.
     */
    children: ChildrenOrFunction<ColorPickerRenderProps>;
}
export declare const ColorPickerContext: React.Context<SlottedContextValue<ColorPickerProps>>;
export declare const ColorPickerStateContext: React.Context<ColorPickerState | null>;
/**
 * A ColorPicker synchronizes a color value between multiple React Aria color components.
 * It simplifies building color pickers with customizable layouts via composition.
 */
export declare function ColorPicker(props: ColorPickerProps): JSX.Element;
