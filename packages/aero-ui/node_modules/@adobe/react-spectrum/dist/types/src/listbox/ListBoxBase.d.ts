import { AriaLabelingProps, AsyncLoadable, DOMProps, FocusStrategy, RefObject, StyleProps } from '@react-types/shared';
import { AriaListBoxOptions, AriaListBoxPropsBase } from 'react-aria/useListBox';
import { ListBoxLayout } from './ListBoxLayout';
import { ListState } from 'react-stately/useListState';
import React, { HTMLAttributes, ReactElement, ReactNode } from 'react';
export interface SpectrumListBoxProps<T> extends AriaListBoxPropsBase<T>, AsyncLoadable, StyleProps {
}
interface ListBoxBaseProps<T> extends AriaListBoxOptions<T>, DOMProps, AriaLabelingProps, StyleProps {
    layout: ListBoxLayout<T>;
    state: ListState<T>;
    autoFocus?: boolean | FocusStrategy;
    shouldFocusWrap?: boolean;
    shouldSelectOnPressUp?: boolean;
    focusOnPointerEnter?: boolean;
    domProps?: HTMLAttributes<HTMLElement>;
    disallowEmptySelection?: boolean;
    shouldUseVirtualFocus?: boolean;
    isLoading?: boolean;
    showLoadingSpinner?: boolean;
    onLoadMore?: () => void;
    renderEmptyState?: () => ReactNode;
    onScroll?: () => void;
}
/** @private */
export declare function useListBoxLayout<T>(): ListBoxLayout<T>;
/** @private */
export declare const ListBoxBase: <T>(props: ListBoxBaseProps<T> & {
    ref?: RefObject<HTMLDivElement | null> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export {};
