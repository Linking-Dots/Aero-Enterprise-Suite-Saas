import { Node } from '@react-types/shared';
import { JSX } from 'react';
interface ListViewItemProps<T> {
    item: Node<T>;
    isEmphasized: boolean;
    hasActions: boolean;
}
export declare function ListViewItem<T>(props: ListViewItemProps<T>): JSX.Element;
export {};
