import { GridNode } from 'react-stately/private/grid/GridCollection';
import { JSX } from 'react';
import type { SpectrumListViewProps } from './ListView';
interface DragPreviewProps<T> {
    item: GridNode<any>;
    itemCount: number;
    itemHeight: number;
    density: SpectrumListViewProps<T>['density'];
}
export declare function DragPreview(props: DragPreviewProps<unknown>): JSX.Element;
export {};
