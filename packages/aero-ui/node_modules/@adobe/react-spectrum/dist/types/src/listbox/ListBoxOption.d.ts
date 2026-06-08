import { Node } from '@react-types/shared';
import { JSX } from 'react';
interface OptionProps<T> {
    item: Node<T>;
}
/** @private */
export declare function ListBoxOption<T>(props: OptionProps<T>): JSX.Element;
export {};
