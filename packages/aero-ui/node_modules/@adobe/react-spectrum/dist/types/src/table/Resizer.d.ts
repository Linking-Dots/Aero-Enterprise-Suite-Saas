import { ColumnSize, TableColumnResizeState } from 'react-stately/useTableState';
import { GridNode } from 'react-stately/private/grid/GridCollection';
import { Key, RefObject } from '@react-types/shared';
import React from 'react';
interface ResizerProps<T> {
    column: GridNode<T>;
    showResizer: boolean;
    triggerRef: RefObject<HTMLDivElement | null>;
    onResizeStart?: (widths: Map<Key, ColumnSize>) => void;
    onResize?: (widths: Map<Key, ColumnSize>) => void;
    onResizeEnd?: (widths: Map<Key, ColumnSize>) => void;
}
export declare const ResizeStateContext: React.Context<TableColumnResizeState<unknown> | null>;
export declare const Resizer: React.ForwardRefExoticComponent<ResizerProps<unknown> & React.RefAttributes<HTMLInputElement | null>>;
export {};
