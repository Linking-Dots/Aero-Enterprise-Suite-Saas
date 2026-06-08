import { AriaLabelingProps, GlobalDOMAttributes, RefObject } from '@react-types/shared';
import { AriaPositionProps, Placement, PlacementAxis, PositionProps } from 'react-aria/useOverlayPosition';
import { ClassNameOrFunction, ContextValue, RenderProps } from './utils';
import { OverlayTriggerProps } from 'react-stately/useOverlayTriggerState';
import React, { JSX, ReactNode } from 'react';
import { TooltipTriggerProps, TooltipTriggerState } from 'react-stately/useTooltipTriggerState';
export interface TooltipTriggerComponentProps extends TooltipTriggerProps {
    children: ReactNode;
}
export interface TooltipProps extends PositionProps, Pick<AriaPositionProps, 'arrowBoundaryOffset'>, OverlayTriggerProps, AriaLabelingProps, RenderProps<TooltipRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Tooltip'
     */
    className?: ClassNameOrFunction<TooltipRenderProps>;
    /**
     * The ref for the element which the tooltip positions itself with respect to.
     *
     * When used within a TooltipTrigger this is set automatically. It is only required when used
     * standalone.
     */
    triggerRef?: RefObject<Element | null>;
    /**
     * Whether the tooltip is currently performing an entry animation.
     */
    isEntering?: boolean;
    /**
     * Whether the tooltip is currently performing an exit animation.
     */
    isExiting?: boolean;
    /**
     * The container element in which the overlay portal will be placed. This may have unknown
     * behavior depending on where it is portalled to.
     *
     * @deprecated - Use a parent UNSAFE_PortalProvider to set your portal container instead.
     * @default document.body
     */
    UNSTABLE_portalContainer?: Element;
    /**
     * The placement of the tooltip with respect to the trigger.
     *
     * @default 'top'
     */
    placement?: Placement;
}
export interface TooltipRenderProps {
    /**
     * The placement of the tooltip relative to the trigger.
     *
     * @selector [data-placement="left | right | top | bottom"]
     */
    placement: PlacementAxis | null;
    /**
     * Whether the tooltip is currently entering. Use this to apply animations.
     *
     * @selector [data-entering]
     */
    isEntering: boolean;
    /**
     * Whether the tooltip is currently exiting. Use this to apply animations.
     *
     * @selector [data-exiting]
     */
    isExiting: boolean;
    /**
     * State of the tooltip.
     */
    state: TooltipTriggerState;
}
export declare const TooltipTriggerStateContext: React.Context<TooltipTriggerState | null>;
export declare const TooltipContext: React.Context<ContextValue<TooltipProps, HTMLDivElement>>;
/**
 * TooltipTrigger wraps around a trigger element and a Tooltip. It handles opening and closing
 * the Tooltip when the user hovers over or focuses the trigger, and positioning the Tooltip
 * relative to the trigger.
 */
export declare function TooltipTrigger(props: TooltipTriggerComponentProps): JSX.Element;
/**
 * A tooltip displays a description of an element on hover or focus.
 */
export declare const Tooltip: (props: TooltipProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
