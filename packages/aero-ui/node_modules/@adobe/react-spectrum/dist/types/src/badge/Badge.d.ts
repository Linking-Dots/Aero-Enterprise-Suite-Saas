import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumBadgeProps extends DOMProps, StyleProps, AriaLabelingProps {
    /** The content to display in the badge. */
    children: ReactNode;
    /**
     * The variant changes the background color of the badge.
     * When badge has a semantic meaning, they should use the variant for semantic colors.
     */
    variant: 'neutral' | 'info' | 'positive' | 'negative' | 'indigo' | 'yellow' | 'magenta' | 'fuchsia' | 'purple' | 'seafoam';
}
/**
 * Badges are used for showing a small amount of color-categorized metadata, ideal for getting a
 * user's attention.
 */
export declare const Badge: React.ForwardRefExoticComponent<SpectrumBadgeProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
