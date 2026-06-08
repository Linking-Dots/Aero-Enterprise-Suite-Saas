import { Alignment, DOMProps, LabelPosition, NecessityIndicator, StyleProps } from '@react-types/shared';
import React, { ElementType, HTMLAttributes, ReactNode } from 'react';
export interface LabelProps {
    children?: ReactNode;
    htmlFor?: string;
    for?: string;
    elementType?: ElementType;
}
export interface SpectrumLabelPropsBase extends LabelProps, DOMProps, StyleProps {
    labelPosition?: LabelPosition;
    labelAlign?: Alignment;
    isRequired?: boolean;
    necessityIndicator?: NecessityIndicator;
    includeNecessityIndicatorInAccessibilityName?: boolean;
}
export interface SpectrumLabelProps extends SpectrumLabelPropsBase, HTMLAttributes<HTMLElement> {
}
export declare const Label: React.ForwardRefExoticComponent<SpectrumLabelProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLLabelElement>>>;
