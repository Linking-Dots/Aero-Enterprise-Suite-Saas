import { AriaTooltipProps } from 'react-aria/useTooltipTrigger';
import { StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumTooltipProps extends AriaTooltipProps, StyleProps {
    /**
     * The [visual style](https://spectrum.adobe.com/page/tooltip/#Semantic-variants) of the Tooltip.
     */
    variant?: 'neutral' | 'positive' | 'negative' | 'info';
    /**
     * The placement of the element with respect to its anchor element.
     *
     * @default 'top'
     */
    placement?: 'start' | 'end' | 'right' | 'left' | 'top' | 'bottom';
    /**
     * Whether the element is rendered.
     */
    showIcon?: boolean;
    children: ReactNode;
}
/**
 * Display container for Tooltip content. Has a directional arrow dependent on its placement.
 */
export declare const Tooltip: React.ForwardRefExoticComponent<SpectrumTooltipProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
