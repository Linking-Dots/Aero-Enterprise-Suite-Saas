import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface TextProps extends DOMProps, StyleProps {
    /**
     * Text content.
     */
    children: ReactNode;
    /**
     * A slot to place the text in.
     *
     * @default 'text'
     */
    slot?: string;
}
/**
 * Text represents text with no specific semantic meaning.
 */
export declare const Text: React.ForwardRefExoticComponent<TextProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
