import { SeparatorProps as AriaSeparatorProps } from 'react-aria/useSeparator';
import { BaseCollection, CollectionNode } from 'react-aria/private/collections/BaseCollection';
import { ContextValue, DOMRenderProps, SlotProps, StyleProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
export interface SeparatorProps extends AriaSeparatorProps, StyleProps, SlotProps, DOMRenderProps<'hr' | 'div', undefined>, GlobalDOMAttributes<HTMLElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-Separator'
     */
    className?: string;
}
export declare const SeparatorContext: React.Context<ContextValue<SeparatorProps, HTMLElement>>;
export declare class SeparatorNode extends CollectionNode<any> {
    static readonly type = "separator";
    filter(collection: BaseCollection<any>, newCollection: BaseCollection<any>): CollectionNode<any> | null;
}
/**
 * A separator is a visual divider between two groups of content, e.g. groups of menu items or
 * sections of a page.
 */
export declare const Separator: (props: SeparatorProps & React.RefAttributes<HTMLElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
