import { Node } from '@react-types/shared';
import { JSX } from 'react';
import { TreeState } from 'react-stately/useTreeState';
interface MenuSectionProps<T> {
    item: Node<T>;
    state: TreeState<T>;
}
/** @private */
export declare function MenuSection<T>(props: MenuSectionProps<T>): JSX.Element;
export {};
