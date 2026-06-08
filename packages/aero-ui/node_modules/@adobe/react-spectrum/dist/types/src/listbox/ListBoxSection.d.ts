import { LayoutInfo } from 'react-stately/useVirtualizerState';
import { Node } from '@react-types/shared';
import { JSX, ReactNode } from 'react';
import { VirtualizerItemOptions } from 'react-aria/private/virtualizer/useVirtualizerItem';
interface ListBoxSectionProps<T> extends Omit<VirtualizerItemOptions, 'ref' | 'layoutInfo'> {
    layoutInfo: LayoutInfo;
    headerLayoutInfo: LayoutInfo | null;
    item: Node<T>;
    children?: ReactNode;
}
/** @private */
export declare function ListBoxSection<T>(props: ListBoxSectionProps<T>): JSX.Element;
export {};
