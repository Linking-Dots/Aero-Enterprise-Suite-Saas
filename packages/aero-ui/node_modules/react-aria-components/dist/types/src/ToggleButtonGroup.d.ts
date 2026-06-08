import { AriaToggleButtonGroupProps } from 'react-aria/useToggleButtonGroup';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, Orientation } from '@react-types/shared';
import React from 'react';
import { ToggleGroupState } from 'react-stately/useToggleGroupState';
export interface ToggleButtonGroupRenderProps {
    /**
     * The orientation of the toggle button group.
     *
     * @selector [data-orientation="horizontal | vertical"]
     */
    orientation: Orientation;
    /**
     * Whether the toggle button group is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the toggle button group.
     */
    state: ToggleGroupState;
}
export interface ToggleButtonGroupProps extends AriaToggleButtonGroupProps, RenderProps<ToggleButtonGroupRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ToggleButtonGroup'
     */
    className?: ClassNameOrFunction<ToggleButtonGroupRenderProps>;
}
export declare const ToggleButtonGroupContext: React.Context<ContextValue<ToggleButtonGroupProps, HTMLDivElement>>;
export declare const ToggleGroupStateContext: React.Context<ToggleGroupState | null>;
/**
 * A toggle button group allows a user to toggle multiple options, with single or multiple
 * selection.
 */
export declare const ToggleButtonGroup: (props: ToggleButtonGroupProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
