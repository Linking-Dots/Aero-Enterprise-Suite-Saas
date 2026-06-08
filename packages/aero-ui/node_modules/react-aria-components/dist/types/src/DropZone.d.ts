import { AriaLabelingProps, GlobalDOMAttributes, HoverEvents } from '@react-types/shared';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { DropOptions } from 'react-aria/useDrop';
import React from 'react';
export interface DropZoneRenderProps {
    /**
     * Whether the dropzone is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the dropzone is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the dropzone is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the dropzone is the drop target.
     *
     * @selector [data-drop-target]
     */
    isDropTarget: boolean;
    /**
     * Whether the dropzone is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
}
export interface DropZoneProps extends Omit<DropOptions, 'getDropOperationForPoint' | 'ref' | 'hasDropButton'>, HoverEvents, RenderProps<DropZoneRenderProps>, SlotProps, AriaLabelingProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DropZone'
     */
    className?: ClassNameOrFunction<DropZoneRenderProps>;
}
export declare const DropZoneContext: React.Context<ContextValue<DropZoneProps, HTMLDivElement>>;
/**
 * A drop zone is an area into which one or multiple objects can be dragged and dropped.
 */
export declare const DropZone: React.ForwardRefExoticComponent<DropZoneProps & React.RefAttributes<HTMLDivElement>>;
