import { ColumnSize } from 'react-stately/useTableState';
import { DOMRef, Key } from '@react-types/shared';
import type { DragAndDropHooks } from '../dnd/useDragAndDrop';
import type { DraggableCollectionState } from 'react-stately/useDraggableCollectionState';
import type { DroppableCollectionState } from 'react-stately/useDroppableCollectionState';
import React, { ReactElement } from 'react';
import { SpectrumTableProps } from './TableView';
import { TableState } from 'react-stately/useTableState';
import { TableViewLayout } from './TableViewLayout';
import { TreeGridState } from 'react-stately/private/table/useTreeGridState';
export interface TableContextValue<T> {
    state: TableState<T>;
    dragState: DraggableCollectionState | null;
    dropState: DroppableCollectionState | null;
    dragAndDropHooks?: DragAndDropHooks<T>['dragAndDropHooks'];
    isTableDraggable: boolean;
    isTableDroppable: boolean;
    layout: TableViewLayout<T>;
    headerRowHovered: boolean;
    isInResizeMode: boolean;
    setIsInResizeMode: (val: boolean) => void;
    isEmpty: boolean;
    onFocusedResizer: () => void;
    onResizeStart?: (widths: Map<Key, ColumnSize>) => void;
    onResize?: (widths: Map<Key, ColumnSize>) => void;
    onResizeEnd?: (widths: Map<Key, ColumnSize>) => void;
    headerMenuOpen: boolean;
    setHeaderMenuOpen: (val: boolean) => void;
    renderEmptyState?: () => ReactElement;
}
export declare const TableContext: React.Context<TableContextValue<unknown> | null>;
export declare function useTableContext(): TableContextValue<unknown>;
export declare const VirtualizerContext: React.Context<{
    width: number;
    key: Key | null;
} | null>;
export declare function useVirtualizerContext(): {
    width: number;
    key: Key | null;
} | null;
interface TableBaseProps<T> extends SpectrumTableProps<T> {
    state: TableState<T> | TreeGridState<T>;
}
interface TableRowContextValue {
    dragButtonProps: React.HTMLAttributes<HTMLDivElement>;
    dragButtonRef: React.RefObject<HTMLDivElement | null>;
    isFocusVisibleWithin: boolean;
}
export declare function useTableRowContext(): TableRowContextValue;
declare const ForwardTableViewBase: <T>(props: TableBaseProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export { ForwardTableViewBase as TableViewBase };
