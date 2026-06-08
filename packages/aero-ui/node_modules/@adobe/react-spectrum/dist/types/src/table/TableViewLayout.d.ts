import { DropTarget } from '@react-types/shared';
import { GridNode } from 'react-stately/private/grid/GridCollection';
import { LayoutNode, TableLayout } from 'react-stately/useVirtualizerState';
export declare class TableViewLayout<T> extends TableLayout<T> {
    private isLoading;
    protected buildCollection(): LayoutNode[];
    protected buildColumn(node: GridNode<T>, x: number, y: number): LayoutNode;
    protected buildBody(): LayoutNode;
    protected buildRow(node: GridNode<T>, x: number, y: number): LayoutNode;
    protected buildCell(node: GridNode<T>, x: number, y: number): LayoutNode;
    protected getEstimatedRowHeight(): number;
    protected isStickyColumn(node: GridNode<T>): boolean;
    getDropTargetFromPoint(x: number, y: number, isValidDropTarget: (target: DropTarget) => boolean): DropTarget | null;
}
