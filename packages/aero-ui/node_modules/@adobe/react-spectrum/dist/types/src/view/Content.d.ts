import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface ContentProps extends DOMProps, StyleProps {
    /**
     * Content content.
     */
    children: ReactNode;
}
/**
 * Content represents the primary content within a Spectrum container.
 */
export declare const Content: React.ForwardRefExoticComponent<ContentProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
