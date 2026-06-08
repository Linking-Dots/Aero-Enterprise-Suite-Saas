import { ContextValue, DOMRenderProps } from './utils';
import React, { HTMLAttributes } from 'react';
export interface HeaderProps extends HTMLAttributes<HTMLElement>, DOMRenderProps<'header', undefined> {
}
export declare const HeaderContext: React.Context<ContextValue<HeaderProps, HTMLElement>>;
export declare const Header: (props: HeaderProps & React.RefAttributes<HTMLElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
