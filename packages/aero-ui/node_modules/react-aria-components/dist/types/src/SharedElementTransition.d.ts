import { RenderProps } from './utils';
import React, { HTMLAttributes, ReactNode } from 'react';
export interface SharedElementTransitionProps {
    children: ReactNode;
}
/**
 * A scope for SharedElements, which animate between parents.
 */
export declare function SharedElementTransition(props: SharedElementTransitionProps): React.JSX.Element;
export interface SharedElementRenderProps {
    /**
     * Whether the element is currently entering.
     *
     * @selector [data-entering]
     */
    isEntering: boolean;
    /**
     * Whether the element is currently exiting.
     *
     * @selector [data-exiting]
     */
    isExiting: boolean;
}
export interface SharedElementPropsBase extends Omit<HTMLAttributes<HTMLDivElement>, 'children' | 'className' | 'style'>, RenderProps<SharedElementRenderProps> {
}
export interface SharedElementProps extends SharedElementPropsBase {
    name: string;
    isVisible?: boolean;
}
/**
 * An element that animates between its old and new position when moving between parents.
 */
export declare const SharedElement: React.ForwardRefExoticComponent<SharedElementProps & React.RefAttributes<HTMLDivElement>>;
