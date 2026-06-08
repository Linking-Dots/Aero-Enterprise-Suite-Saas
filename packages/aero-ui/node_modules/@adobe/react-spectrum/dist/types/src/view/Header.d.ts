import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface HeaderProps extends DOMProps, StyleProps {
    /**
     * Header content.
     */
    children: ReactNode;
}
/**
 * Header represents a header within a Spectrum container.
 */
export declare const Header: React.ForwardRefExoticComponent<HeaderProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
