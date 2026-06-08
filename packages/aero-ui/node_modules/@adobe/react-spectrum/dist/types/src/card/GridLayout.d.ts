import { BaseLayout, BaseLayoutOptions } from './BaseLayout';
import { Key, Node, Orientation } from '@react-types/shared';
import { LayoutInfo, Size } from 'react-stately/useVirtualizerState';
export interface GridLayoutOptions extends BaseLayoutOptions {
    /**
     * The minimum item size.
     *
     * @default 208 x 208 for horizontal card orientation. 102 x 102 for vertical card orientation.
     */
    minItemSize?: Size;
    /**
     * The maximum item size.
     *
     * @default Infinity
     */
    maxItemSize?: Size;
    /**
     * The minimum space required between items.
     *
     * @default 18 x 18
     */
    minSpace?: Size;
    /**
     * The maximum number of columns.
     *
     * @default Infinity
     */
    maxColumns?: number;
    /**
     * The additional padding along the card's main axis. Affects the sizing of the content area
     * following the card image.
     *
     * @default 95
     */
    itemPadding?: number;
    /**
     * The orientation of the cards withn the grid.
     *
     * @default vertical
     */
    cardOrientation?: Orientation;
}
export declare class GridLayout<T> extends BaseLayout<T> {
    protected minItemSize: Size;
    protected maxItemSize: Size;
    protected minSpace: Size;
    protected maxColumns: number;
    itemPadding: number;
    cardOrientation: Orientation;
    protected itemSize: Size;
    protected numColumns: number;
    protected numRows: number;
    protected horizontalSpacing: number;
    constructor(options?: GridLayoutOptions);
    get layoutType(): string;
    getIndexAtPoint(x: number, y: number, allowInsertingAtEnd?: boolean): number;
    buildCollection(): void;
    buildChild(node: Node<T>, y: number, index: number): LayoutInfo;
    getKeyBelow(key: Key): Node<T> | undefined | null;
    getKeyAbove(key: Key): Node<T> | undefined | null;
}
