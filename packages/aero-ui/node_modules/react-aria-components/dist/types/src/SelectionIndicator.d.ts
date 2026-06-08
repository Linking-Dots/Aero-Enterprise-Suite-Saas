import { ClassNameOrFunction, ContextValue } from './utils';
import React from 'react';
import { SharedElementPropsBase, SharedElementRenderProps } from './SharedElementTransition';
export interface SelectionIndicatorProps extends SharedElementPropsBase {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SelectionIndicator'
     */
    className?: ClassNameOrFunction<SharedElementRenderProps>;
    /**
     * Whether the SelectionIndicator is visible. This is usually set automatically by the parent
     * component.
     */
    isSelected?: boolean;
}
export declare const SelectionIndicatorContext: React.Context<ContextValue<SelectionIndicatorProps, HTMLDivElement>>;
/**
 * An animated indicator of selection state within a group of items.
 */
export declare const SelectionIndicator: React.ForwardRefExoticComponent<SelectionIndicatorProps & React.RefAttributes<HTMLDivElement>>;
