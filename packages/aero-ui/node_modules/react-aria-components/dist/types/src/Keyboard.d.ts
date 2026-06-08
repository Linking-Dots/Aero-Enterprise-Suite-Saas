import { ContextValue, DOMRenderProps } from './utils';
import React, { HTMLAttributes } from 'react';
export interface KeyboardProps extends HTMLAttributes<HTMLElement>, DOMRenderProps<'kbd', undefined> {
}
export declare const KeyboardContext: React.Context<ContextValue<KeyboardProps, HTMLElement>>;
export declare const Keyboard: React.ForwardRefExoticComponent<KeyboardProps & React.RefAttributes<HTMLElement>>;
