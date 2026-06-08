import { AriaBreadcrumbsProps } from 'react-aria/useBreadcrumbs';
import { AriaLabelingProps, GlobalDOMAttributes, Key } from '@react-types/shared';
import { ClassNameOrFunction, ContextValue, DOMRenderProps, RenderProps, SlotProps, StyleProps } from './utils';
import { CollectionProps } from './Collection';
import React from 'react';
export interface BreadcrumbsProps<T> extends Omit<CollectionProps<T>, 'disabledKeys'>, AriaBreadcrumbsProps, StyleProps, SlotProps, AriaLabelingProps, DOMRenderProps<'ol', undefined>, GlobalDOMAttributes<HTMLOListElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-Breadcrumbs'
     */
    className?: string;
    /** Whether the breadcrumbs are disabled. */
    isDisabled?: boolean;
    /** Handler that is called when a breadcrumb is clicked. */
    onAction?: (key: Key) => void;
}
export declare const BreadcrumbsContext: React.Context<ContextValue<BreadcrumbsProps<any>, HTMLOListElement>>;
/**
 * Breadcrumbs display a hierarchy of links to the current page or resource in an application.
 */
export declare const Breadcrumbs: <T>(props: BreadcrumbsProps<T> & React.RefAttributes<HTMLOListElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface BreadcrumbRenderProps {
    /**
     * Whether the breadcrumb is for the current page.
     *
     * @selector [data-current]
     */
    isCurrent: boolean;
    /**
     * Whether the breadcrumb is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
}
export interface BreadcrumbProps extends RenderProps<BreadcrumbRenderProps, 'li'>, AriaLabelingProps, GlobalDOMAttributes<HTMLLIElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Breadcrumb'
     */
    className?: ClassNameOrFunction<BreadcrumbRenderProps>;
    /**
     * A unique id for the breadcrumb, which will be passed to `onAction` when the breadcrumb is
     * pressed.
     */
    id?: Key;
}
/**
 * A Breadcrumb represents an individual item in a `<Breadcrumbs>` list.
 */
export declare const Breadcrumb: (props: BreadcrumbProps & React.RefAttributes<HTMLLIElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
