import { AriaLabelingProps, DOMProps, Orientation, StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumDividerProps extends DOMProps, AriaLabelingProps, StyleProps {
    /**
     * How thick the Divider should be.
     *
     * @default 'L'
     */
    size?: 'S' | 'M' | 'L';
    /**
     * The axis the Divider should align with.
     *
     * @default 'horizontal'
     */
    orientation?: Orientation;
    /**
     * A slot to place the divider in.
     *
     * @default 'divider'
     */
    slot?: string;
}
/**
 * Dividers bring clarity to a layout by grouping and dividing content in close proximity.
 * They can also be used to establish rhythm and hierarchy.
 */
export declare const Divider: React.ForwardRefExoticComponent<SpectrumDividerProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
