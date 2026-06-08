import { BaseLayout, BaseLayoutOptions } from './BaseLayout';
import { InvalidationContext, Size } from 'react-stately/useVirtualizerState';
import { Key, KeyboardDelegate } from '@react-types/shared';
export interface WaterfallLayoutOptions extends BaseLayoutOptions {
    /**
     * The minimum item size.
     *
     * @default 240 x 136
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
}
export declare class WaterfallLayout<T> extends BaseLayout<T> implements KeyboardDelegate {
    protected minItemSize: Size;
    protected maxItemSize: Size;
    protected minSpace: Size;
    protected maxColumns: number;
    protected numColumns: number;
    protected itemWidth: number;
    protected horizontalSpacing: number;
    constructor(options?: WaterfallLayoutOptions);
    get layoutType(): string;
    buildCollection(invalidationContext: InvalidationContext): void;
    updateItemSize(key: Key, size: Size): number;
    getNextColumnIndex(columnHeights: number[]): number;
    getClosestRight(key: Key): Node<T> | undefined;
    getClosestLeft(key: Key): Node<T> | undefined;
    getKeyRightOf(key: Key): Node<T> | undefined;
    getKeyLeftOf(key: Key): Node<T> | undefined;
}
