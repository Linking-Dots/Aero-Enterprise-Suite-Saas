import { ClassNameOrFunction, ContextValue, RenderProps } from './utils';
import { DOMProps } from '@react-types/shared';
import { PlacementAxis } from 'react-aria/useOverlayPosition';
import React, { HTMLAttributes } from 'react';
interface OverlayArrowContextValue extends OverlayArrowProps {
    placement: PlacementAxis | null;
}
export declare const OverlayArrowContext: React.Context<ContextValue<OverlayArrowContextValue, HTMLDivElement>>;
export interface OverlayArrowProps extends Omit<HTMLAttributes<HTMLDivElement>, 'className' | 'style' | 'render' | 'children'>, RenderProps<OverlayArrowRenderProps>, DOMProps {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-OverlayArrow'
     */
    className?: ClassNameOrFunction<OverlayArrowRenderProps>;
}
export interface OverlayArrowRenderProps {
    /**
     * The placement of the overlay relative to the trigger.
     *
     * @selector [data-placement="left | right | top | bottom"]
     */
    placement: PlacementAxis | null;
}
/**
 * An OverlayArrow renders a custom arrow element relative to an overlay element
 * such as a popover or tooltip such that it aligns with a trigger element.
 */
export declare const OverlayArrow: (props: OverlayArrowProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export {};
