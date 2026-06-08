import { ContextValue, DOMRenderProps } from './utils';
import React, { HTMLAttributes } from 'react';
export interface HeadingProps extends HTMLAttributes<HTMLElement>, DOMRenderProps<'h1', undefined> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-Heading'
     */
    className?: string;
    /**
     * The heading level.
     *
     * @default 3
     */
    level?: number;
}
export declare const HeadingContext: React.Context<ContextValue<HeadingProps, HTMLHeadingElement>>;
export declare const Heading: React.ForwardRefExoticComponent<HeadingProps & React.RefAttributes<HTMLHeadingElement>>;
