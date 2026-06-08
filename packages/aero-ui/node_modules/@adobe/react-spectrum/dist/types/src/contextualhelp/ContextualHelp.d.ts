import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import { OverlayTriggerProps } from 'react-stately/useOverlayTriggerState';
import { Placement, PositionProps } from 'react-aria/useOverlayPosition';
import React, { ReactNode } from 'react';
export interface SpectrumContextualHelpProps extends OverlayTriggerProps, PositionProps, StyleProps, DOMProps, AriaLabelingProps {
    /** Contents of the Contextual Help popover. */
    children: ReactNode;
    /**
     * Indicates whether contents are informative or provides helpful guidance.
     *
     * @default 'help'
     */
    variant?: 'help' | 'info';
    /**
     * The placement of the popover with respect to the action button.
     *
     * @default 'bottom start'
     */
    placement?: Placement;
}
/**
 * Contextual help shows a user extra information about the state of an adjacent component, or a
 * total view.
 */
export declare const ContextualHelp: React.ForwardRefExoticComponent<SpectrumContextualHelpProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLButtonElement, HTMLButtonElement>>>;
