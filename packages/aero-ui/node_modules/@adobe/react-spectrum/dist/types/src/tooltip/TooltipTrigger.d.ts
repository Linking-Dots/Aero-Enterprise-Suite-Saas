import { Placement, PositionProps } from 'react-aria/useOverlayPosition';
import React, { JSX, ReactElement } from 'react';
import { TooltipTriggerProps } from 'react-stately/useTooltipTriggerState';
export interface SpectrumTooltipTriggerProps extends Omit<TooltipTriggerProps, 'closeDelay'>, PositionProps {
    children: [ReactElement, ReactElement];
    /**
     * The additional offset applied along the main axis between the element and its
     * anchor element.
     *
     * @default 7
     */
    offset?: number;
    /**
     * The placement of the tooltip with respect to the trigger.
     *
     * @default 'top'
     */
    placement?: Placement;
}
declare function TooltipTrigger(props: SpectrumTooltipTriggerProps): JSX.Element;
declare namespace TooltipTrigger {
    var getCollectionNode: (props: SpectrumTooltipTriggerProps) => Generator<{
        element: ReactElement<unknown, string | React.JSXElementConstructor<any>>;
        wrapper: (element: any) => JSX.Element;
    }, void, unknown>;
}
/**
 * TooltipTrigger wraps around a trigger element and a Tooltip. It handles opening and closing
 * the Tooltip when the user hovers over or focuses the trigger, and positioning the Tooltip
 * relative to the trigger.
 */
declare let _TooltipTrigger: (props: SpectrumTooltipTriggerProps) => JSX.Element;
export { _TooltipTrigger as TooltipTrigger };
