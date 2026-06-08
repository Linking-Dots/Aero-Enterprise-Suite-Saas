import { InvalidationContext, LayoutNode, ListLayout, ListLayoutOptions } from 'react-stately/useVirtualizerState';
import { Node } from '@react-types/shared';
interface ListBoxLayoutProps extends ListLayoutOptions {
    isLoading?: boolean;
}
interface ListBoxLayoutOptions extends ListLayoutOptions {
    placeholderHeight: number;
    paddingY: number;
}
export declare class ListBoxLayout<T> extends ListLayout<T, ListBoxLayoutProps> {
    private isLoading;
    private placeholderHeight;
    private paddingY;
    constructor(opts: ListBoxLayoutOptions);
    update(invalidationContext: InvalidationContext<ListBoxLayoutProps>): void;
    protected buildCollection(): LayoutNode[];
    protected buildSection(node: Node<T>, x: number, y: number): LayoutNode;
}
export {};
