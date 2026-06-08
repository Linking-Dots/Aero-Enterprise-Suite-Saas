import { AriaGridListProps } from 'react-aria/useGridList';
import { AsyncLoadable, DOMRef, Key, LoadingState, SpectrumSelectionProps, StyleProps } from '@react-types/shared';
import type { DragAndDropHooks } from '../dnd/useDragAndDrop';
import type { DraggableCollectionState } from 'react-stately/useDraggableCollectionState';
import type { DroppableCollectionState } from 'react-stately/useDroppableCollectionState';
import { ListState } from 'react-stately/useListState';
import { ListViewLayout } from './ListViewLayout';
import React, { JSX, ReactElement } from 'react';
export interface SpectrumListViewProps<T> extends Omit<AriaGridListProps<T>, 'keyboardNavigationBehavior'>, StyleProps, SpectrumSelectionProps, Omit<AsyncLoadable, 'isLoading'> {
    /**
     * Sets the amount of vertical padding within each cell.
     *
     * @default 'regular'
     */
    density?: 'compact' | 'regular' | 'spacious';
    /** Whether the ListView should be displayed with a quiet style. */
    isQuiet?: boolean;
    /**
     * The current loading state of the ListView. Determines whether or not the progress circle should
     * be shown.
     */
    loadingState?: LoadingState;
    /**
     * Sets the text behavior for the row contents.
     *
     * @default 'truncate'
     */
    overflowMode?: 'truncate' | 'wrap';
    /** Sets what the ListView should render when there is no content to display. */
    renderEmptyState?: () => JSX.Element;
    /**
     * Handler that is called when a user performs an action on an item. The exact user event depends
     * on the collection's `selectionStyle` prop and the interaction modality.
     */
    onAction?: (key: Key) => void;
    /**
     * The drag and drop hooks returned by `useDragAndDrop` used to enable drag and drop behavior for
     * the ListView.
     */
    dragAndDropHooks?: DragAndDropHooks<NoInfer<T>>['dragAndDropHooks'];
}
interface ListViewContextValue<T> {
    state: ListState<T>;
    dragState: DraggableCollectionState | null;
    dropState: DroppableCollectionState | null;
    dragAndDropHooks?: DragAndDropHooks<T>['dragAndDropHooks'];
    onAction?: (key: Key) => void;
    isListDraggable: boolean;
    isListDroppable: boolean;
    layout: ListViewLayout<T>;
    loadingState?: LoadingState;
    renderEmptyState?: () => JSX.Element;
}
export declare const ListViewContext: React.Context<ListViewContextValue<unknown> | null>;
/**
 * A ListView displays a list of interactive items, and allows a user to navigate, select, or
 * perform an action.
 */
export declare const ListView: <T>(props: SpectrumListViewProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export {};
