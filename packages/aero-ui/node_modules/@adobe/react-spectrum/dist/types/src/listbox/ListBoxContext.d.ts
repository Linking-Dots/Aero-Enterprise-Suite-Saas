import { ListState } from 'react-stately/useListState';
import React, { ReactNode } from 'react';
interface ListBoxContextValue {
    state: ListState<unknown>;
    renderEmptyState?: () => ReactNode;
    shouldFocusOnHover: boolean;
    shouldUseVirtualFocus: boolean;
}
export declare const ListBoxContext: React.Context<ListBoxContextValue | null>;
export {};
