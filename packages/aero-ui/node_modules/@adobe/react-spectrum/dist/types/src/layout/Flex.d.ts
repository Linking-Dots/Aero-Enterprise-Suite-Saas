import { DOMProps, FlexStyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface FlexProps extends DOMProps, FlexStyleProps {
    /** Children of the flex container. */
    children: ReactNode;
}
/**
 * A layout container using flexbox. Provides Spectrum dimension values, and supports the gap
 * property to define consistent spacing between items.
 */
export declare const Flex: React.ForwardRefExoticComponent<FlexProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
