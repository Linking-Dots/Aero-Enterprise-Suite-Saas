import { ContextValue, DOMRenderProps } from './utils';
import React, { HTMLAttributes } from 'react';
export interface TextProps extends HTMLAttributes<HTMLElement>, DOMRenderProps<any, any> {
    elementType?: string;
}
export declare const TextContext: React.Context<ContextValue<TextProps, HTMLElement>>;
export declare const Text: React.ForwardRefExoticComponent<TextProps & React.RefAttributes<HTMLElement>>;
