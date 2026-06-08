import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumWellProps extends DOMProps, AriaLabelingProps, StyleProps {
    /**
     * The contents of the Well.
     */
    children: ReactNode;
    /**
     * An accessibility role for the well. Use `'region'` when the contents of the well
     * is important enough to be included in the page table of contents, and `'group'` otherwise.
     * If a role is provided, then an aria-label or aria-labelledby must also be provided.
     */
    role?: 'region' | 'group';
}
/**
 * A Well is a content container that displays non-editable content separate from other content on
 * the screen. Often this is used to display preformatted text, such as code/markup examples on a
 * documentation page.
 */
export declare const Well: React.ForwardRefExoticComponent<SpectrumWellProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
