import { Direction, Key, KeyboardDelegate, Node } from '@react-types/shared';
import { GridCollection } from 'react-stately/private/grid/GridCollection';
import { InvalidationContext, Layout, LayoutInfo, Rect, Size } from 'react-stately/useVirtualizerState';
import { Scale } from '../provider/types';
export interface BaseLayoutOptions {
    collator?: Intl.Collator;
    scale?: Scale;
    /**
     * The margin around the grid view between the edges and the items.
     *
     * @default 24
     */
    margin?: number;
}
interface CardViewLayoutOptions {
    isLoading: boolean;
    direction: Direction;
}
export declare class BaseLayout<T> extends Layout<Node<T>, CardViewLayoutOptions> implements KeyboardDelegate {
    protected contentSize: Size;
    protected layoutInfos: Map<Key, LayoutInfo>;
    protected collator: Intl.Collator;
    protected lastCollection: GridCollection<T>;
    collection: GridCollection<T>;
    isLoading: boolean;
    disabledKeys: Set<Key>;
    direction: Direction;
    scale: Scale;
    margin: number;
    constructor(options?: BaseLayoutOptions);
    update(invalidationContext: InvalidationContext<CardViewLayoutOptions>): void;
    buildCollection(invalidationContext?: InvalidationContext): void;
    getContentSize(): number;
    getLayoutInfo(key: Key): LayoutInfo;
    getVisibleLayoutInfos(rect: Rect, excludePersistedKeys?: boolean): LayoutInfo[];
    isVisible(layoutInfo: LayoutInfo, rect: Rect, excludePersistedKeys: boolean): boolean;
    _findClosestLayoutInfo(target: Rect, rect: Rect): LayoutInfo | null;
    _findClosest(target: Rect, rect: Rect): LayoutInfo | null;
    getKeyBelow(key: Key): Node<T> | undefined;
    getKeyAbove(key: Key): Node<T> | undefined;
    getKeyRightOf(key: Key): Node<T> | undefined;
    getKeyLeftOf(key: Key): Node<T> | undefined;
    getFirstKey(): Node<T> | undefined;
    getLastKey(): Node<T> | undefined;
    getKeyPageAbove(key: Key): Node<T> | undefined;
    getKeyPageBelow(key: Key): Node<T> | undefined;
    getKeyForSearch(search: string, fromKey?: Key): Node<T> | undefined | null;
}
export {};
