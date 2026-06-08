import { InvalidationContext, LayoutNode, ListLayout, ListLayoutOptions } from 'react-stately/useVirtualizerState';
import { Node } from '@react-types/shared';
interface ListViewLayoutProps extends ListLayoutOptions {
    isLoading?: boolean;
}
export declare class ListViewLayout<T> extends ListLayout<T, ListViewLayoutProps> {
    private isLoading;
    update(invalidationContext: InvalidationContext<ListViewLayoutProps>): void;
    protected buildCollection(): LayoutNode[];
    protected buildItem(node: Node<T>, x: number, y: number): LayoutNode;
}
export {};
