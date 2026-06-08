import { ColorVersion, DOMProps, DOMRef, ViewStyleProps } from '@react-types/shared';
import { JSXElementConstructor, ReactElement, ReactNode } from 'react';
export interface ViewProps<C extends ColorVersion> extends ViewStyleProps<C>, DOMProps {
    /**
     * The element to render as the node.
     */
    elementType?: string | JSXElementConstructor<any>;
    /**
     * Children to be displayed in the View.
     */
    children?: ReactNode;
}
/**
 * View is a general purpose container with no specific semantics that can be used for custom
 * styling purposes. It supports Spectrum style props to ensure consistency with other Spectrum
 * components.
 */
export declare const View: <C extends ColorVersion = 5>(props: ViewProps<C> & {
    ref?: DOMRef | undefined;
}) => ReactElement<unknown, string | JSXElementConstructor<any>>;
