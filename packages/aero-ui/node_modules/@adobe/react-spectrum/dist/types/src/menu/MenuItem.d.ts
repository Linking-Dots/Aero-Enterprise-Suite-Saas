import { Node } from '@react-types/shared';
import { JSX } from 'react';
import { TreeState } from 'react-stately/useTreeState';
interface MenuItemProps<T> {
    item: Node<T>;
    state: TreeState<T>;
    isVirtualized?: boolean;
}
/** @private */
export declare function MenuItem<T>(props: MenuItemProps<T>): JSX.Element;
export {};
