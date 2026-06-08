import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface HeadingProps extends DOMProps, StyleProps {
    /**
     * Heading content.
     */
    children: ReactNode;
    /**
     * A slot to place the heading in.
     *
     * @default 'heading'
     */
    slot?: string;
    /**
     * Sets heading level, h1 through h6.
     *
     * @default 3
     */
    level?: 1 | 2 | 3 | 4 | 5 | 6;
}
/**
 * Heading is used to create various levels of typographic hierarchies.
 */
export declare const Heading: React.ForwardRefExoticComponent<HeadingProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLHeadingElement>>>;
