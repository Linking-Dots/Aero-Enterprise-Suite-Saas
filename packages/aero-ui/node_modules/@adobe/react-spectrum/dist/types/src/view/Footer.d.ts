import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface FooterProps extends DOMProps, StyleProps {
    /**
     * Footer content.
     */
    children: ReactNode;
}
/**
 * Footer represents a footer within a Spectrum container.
 */
export declare const Footer: React.ForwardRefExoticComponent<FooterProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
