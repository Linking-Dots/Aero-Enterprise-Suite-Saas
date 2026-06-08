import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
interface ActionBarContainerProps {
    /**
     * The contents of the ActionBarContainer. Should include a ActionBar and the renderable content
     * it is associated with.
     */
    children: ReactNode;
}
export interface SpectrumActionBarContainerProps extends ActionBarContainerProps, DOMProps, StyleProps {
}
/**
 * ActionBarContainer wraps around an ActionBar and a component that supports selection. It handles
 * the ActionBar's position with respect to its linked component.
 */
export declare const ActionBarContainer: React.ForwardRefExoticComponent<SpectrumActionBarContainerProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
export {};
