import { AriaLinkOptions } from 'react-aria/useLink';
import { ClassNameOrFunction, ContextValue, PossibleLinkDOMRenderProps, RenderProps, SlotProps } from './utils';
import { DOMProps, GlobalDOMAttributes } from '@react-types/shared';
import { HoverEvents } from '@react-types/shared';
import React from 'react';
export interface LinkProps extends Omit<AriaLinkOptions, 'elementType'>, HoverEvents, Omit<RenderProps<LinkRenderProps>, 'render'>, PossibleLinkDOMRenderProps<'span', LinkRenderProps>, SlotProps, DOMProps, Omit<GlobalDOMAttributes<HTMLAnchorElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Link'
     */
    className?: ClassNameOrFunction<LinkRenderProps>;
}
export interface LinkRenderProps {
    /**
     * Whether the link is the current item within a list.
     *
     * @selector [data-current]
     */
    isCurrent: boolean;
    /**
     * Whether the link is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the link is currently in a pressed state.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the link is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the link is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the link is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
}
export declare const LinkContext: React.Context<ContextValue<LinkProps, HTMLAnchorElement>>;
/**
 * A link allows a user to navigate to another page or resource within a web page
 * or application.
 */
export declare const Link: (props: LinkProps & React.RefAttributes<HTMLAnchorElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
