import { BaseLayout, BaseLayoutOptions } from './BaseLayout';
import { Size } from 'react-stately/useVirtualizerState';
export interface GalleryLayoutOptions extends BaseLayoutOptions {
    /**
     * The the default row height. Note this must be larger than the min item height.
     *
     * @default 208
     */
    idealRowHeight?: number;
    /**
     * The spacing between items.
     *
     * @default 18 x 18
     */
    itemSpacing?: Size;
    /**
     * The vertical padding for an item.
     *
     * @default 78
     */
    itemPadding?: number;
    /**
     * Minimum size for a item in the grid.
     *
     * @default 136 x 136
     */
    minItemSize?: Size;
    /**
     * Target for adding extra weight to elements during linear partitioning. Anything with an aspect
     * ratio smaler than this value will be targeted.
     *
     * @type {number}
     */
    threshold?: number;
}
export declare class GalleryLayout<T> extends BaseLayout<T> {
    protected idealRowHeight: number;
    protected itemSpacing: Size;
    itemPadding: number;
    protected minItemSize: Size;
    protected threshold: number;
    constructor(options?: GalleryLayoutOptions);
    get layoutType(): string;
    /**
     * Takes a row of widths and if there are any widths smaller than the min-width, leech width
     * starting from the widest in the row until it can't give anymore, then move to the second widest
     * and so forth. Do this until all assets meet the min-width.
     */
    _distributeWidths(widths: number[]): boolean;
    buildCollection(): void;
}
