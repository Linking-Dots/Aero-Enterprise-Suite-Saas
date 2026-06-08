import { Alignment, DOMProps, Orientation, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumButtonGroupProps extends DOMProps, StyleProps {
    /** Whether the Buttons in the ButtonGroup are all disabled. */
    isDisabled?: boolean;
    /**
     * The axis the ButtonGroup should align with. Setting this to 'vertical' will prevent
     * any switching behaviors between 'vertical' and 'horizontal'.
     *
     * @default 'horizontal'
     */
    orientation?: Orientation;
    /** The Buttons contained within the ButtonGroup. */
    children: ReactNode;
    /**
     * The alignment of the buttons within the ButtonGroup.
     *
     * @default 'start'
     */
    align?: Alignment | 'center';
}
/**
 * ButtonGroup handles overflow for a grouping of buttons whose actions are related to each other.
 */
export declare const ButtonGroup: React.ForwardRefExoticComponent<SpectrumButtonGroupProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
