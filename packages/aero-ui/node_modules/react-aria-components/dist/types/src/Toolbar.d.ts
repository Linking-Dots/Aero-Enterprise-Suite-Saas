import { AriaToolbarProps } from 'react-aria/useToolbar';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, Orientation } from '@react-types/shared';
import React from 'react';
export interface ToolbarRenderProps {
    /**
     * The current orientation of the toolbar.
     *
     * @selector [data-orientation]
     */
    orientation: Orientation;
}
export interface ToolbarProps extends AriaToolbarProps, SlotProps, RenderProps<ToolbarRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Toolbar'
     */
    className?: ClassNameOrFunction<ToolbarRenderProps>;
}
export declare const ToolbarContext: React.Context<ContextValue<ToolbarProps, HTMLDivElement>>;
/**
 * A toolbar is a container for a set of interactive controls, such as buttons, dropdown menus, or
 * checkboxes, with arrow key navigation.
 */
export declare const Toolbar: (props: ToolbarProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
