import { ClassNameOrFunction, RenderProps } from './utils';
import { Color } from 'react-stately/Color';
import { GlobalDOMAttributes, HoverEvents, RefObject } from '@react-types/shared';
import React, { HTMLAttributes, InputHTMLAttributes } from 'react';
interface ColorState {
    getDisplayColor(): Color;
    isDragging: boolean;
}
interface InternalColorThumbContextValue {
    state: ColorState;
    thumbProps: HTMLAttributes<HTMLElement>;
    inputXRef: RefObject<HTMLInputElement | null>;
    inputYRef?: RefObject<HTMLInputElement | null>;
    xInputProps: InputHTMLAttributes<HTMLInputElement>;
    yInputProps?: InputHTMLAttributes<HTMLInputElement>;
    isDisabled?: boolean;
}
export declare const InternalColorThumbContext: React.Context<InternalColorThumbContextValue | null>;
export interface ColorThumbRenderProps {
    /**
     * The selected color, excluding the alpha channel.
     */
    color: Color;
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
export interface ColorThumbProps extends HoverEvents, RenderProps<ColorThumbRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorThumb'
     */
    className?: ClassNameOrFunction<ColorThumbRenderProps>;
}
/**
 * A color thumb appears within a ColorArea, ColorSlider, or ColorWheel and allows a user to drag to
 * adjust the color value.
 */
export declare const ColorThumb: React.ForwardRefExoticComponent<ColorThumbProps & React.RefAttributes<HTMLDivElement>>;
export {};
