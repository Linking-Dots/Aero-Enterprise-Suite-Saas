import { AriaToastProps, AriaToastRegionProps } from 'react-aria/useToast';
import { ClassNameOrFunction, ContextValue, DOMRenderProps, RenderProps, StyleRenderProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import { QueuedToast, ToastQueue, ToastState } from 'react-stately/useToastState';
import React, { HTMLAttributes, ReactElement, ReactNode } from 'react';
declare const ToastStateContext: React.Context<ToastState<any> | null>;
export interface ToastRegionRenderProps<T> {
    /** A list of all currently visible toasts. */
    visibleToasts: QueuedToast<T>[];
    /**
     * Whether the toast region is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the toast region is currently focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the toast region is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
}
export interface ToastRegionProps<T> extends AriaToastRegionProps, StyleRenderProps<ToastRegionRenderProps<T>>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ToastRegion'
     */
    className?: ClassNameOrFunction<ToastRegionRenderProps<T>>;
    /** The queue of toasts to display. */
    queue: ToastQueue<T>;
    /** A function to render each toast, or children containing a `<ToastList>`. */
    children: ReactNode | ((renderProps: {
        toast: QueuedToast<T>;
    }) => ReactElement);
}
/**
 * A ToastRegion displays one or more toast notifications.
 */
declare const ToastRegion: <T>(props: ToastRegionProps<T> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface ToastListProps<T> extends Omit<ToastRegionProps<T>, 'queue' | 'children' | 'render'>, DOMRenderProps<'ol', ToastRegionRenderProps<T>> {
    /** A function to render each toast. */
    children: (renderProps: {
        toast: QueuedToast<T>;
    }) => ReactElement;
}
declare const ToastList: <T>(props: ToastListProps<T> & React.RefAttributes<HTMLOListElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface ToastRenderProps<T> {
    /**
     * The toast object to display.
     */
    toast: QueuedToast<T>;
    /**
     * Whether the toast is currently focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the toast is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
}
export interface ToastProps<T> extends AriaToastProps<T>, RenderProps<ToastRenderProps<T>>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Toast'
     */
    className?: ClassNameOrFunction<ToastRenderProps<T>>;
}
/**
 * A Toast displays a brief, temporary notification of actions, errors, or other events in an
 * application.
 */
declare const Toast: <T>(props: ToastProps<T> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface ToastContentProps extends HTMLAttributes<HTMLElement>, DOMRenderProps<'div', undefined> {
}
export declare const ToastContentContext: React.Context<ContextValue<HTMLAttributes<HTMLElement>, HTMLDivElement>>;
/**
 * ToastContent wraps the main content of a toast notification.
 */
export declare const ToastContent: React.ForwardRefExoticComponent<ToastContentProps & React.RefAttributes<HTMLDivElement>>;
export { Toast as UNSTABLE_Toast, ToastList as UNSTABLE_ToastList, ToastRegion as UNSTABLE_ToastRegion, ToastContent as UNSTABLE_ToastContent, ToastStateContext as UNSTABLE_ToastStateContext };
