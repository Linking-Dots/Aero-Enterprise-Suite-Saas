import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumIllustratedMessageProps extends DOMProps, StyleProps {
    /** The contents of the IllustratedMessage. */
    children: ReactNode;
}
/**
 * An IllustratedMessage displays an illustration and a message, usually
 * for an empty state or an error page.
 */
export declare const IllustratedMessage: React.ForwardRefExoticComponent<SpectrumIllustratedMessageProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
