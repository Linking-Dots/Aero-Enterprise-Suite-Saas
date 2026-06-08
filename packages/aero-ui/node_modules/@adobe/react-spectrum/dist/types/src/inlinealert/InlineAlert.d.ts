import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumInlineAlertProps extends DOMProps, StyleProps {
    /**
     * The [visual style](https://spectrum.adobe.com/page/in-line-alert/#Options) of the Inline Alert.
     *
     * @default 'neutral'
     */
    variant?: 'neutral' | 'info' | 'positive' | 'notice' | 'negative';
    /**
     * The contents of the Inline Alert.
     */
    children: ReactNode;
    /**
     * Whether to automatically focus the Inline Alert when it first renders.
     */
    autoFocus?: boolean;
}
/**
 * Inline alerts display a non-modal message associated with objects in a view. These are often used
 * in form validation, providing a place to aggregate feedback related to multiple fields.
 */
export declare const InlineAlert: React.ForwardRefExoticComponent<SpectrumInlineAlertProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
