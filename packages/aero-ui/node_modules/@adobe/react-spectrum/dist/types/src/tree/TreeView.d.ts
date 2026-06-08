import { AriaTreeProps } from 'react-aria/useTree';
import { DOMRef, Expandable, Key, SpectrumSelectionProps, StyleProps } from '@react-types/shared';
import { JSX, JSXElementConstructor, ReactElement, ReactNode } from 'react';
import { TreeItemContentProps, TreeItemProps } from 'react-aria-components/Tree';
export interface SpectrumTreeViewProps<T> extends Omit<AriaTreeProps<T>, 'children' | 'render'>, StyleProps, SpectrumSelectionProps, Expandable {
    /** Provides content to display when there are no items in the tree. */
    renderEmptyState?: () => JSX.Element;
    /**
     * Handler that is called when a user performs an action on an item. The exact user event depends
     * on the collection's `selectionStyle` prop and the interaction modality.
     */
    onAction?: (key: Key) => void;
    /**
     * The contents of the tree.
     */
    children?: ReactNode | ((item: T) => ReactNode);
}
export interface SpectrumTreeViewItemProps extends Omit<TreeItemProps, 'className' | 'style' | 'render' | 'value' | 'onHoverStart' | 'onHoverEnd' | 'onHoverChange' | 'onClick'> {
    /** Rendered contents of the tree item or child items. */
    children: ReactNode;
}
/**
 * A tree view provides users with a way to navigate nested hierarchical information.
 */
export declare const TreeView: <T>(props: SpectrumTreeViewProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | JSXElementConstructor<any>>;
export declare const TreeViewItem: (props: SpectrumTreeViewItemProps) => ReactNode;
export interface SpectrumTreeViewItemContentProps extends Omit<TreeItemContentProps, 'children'> {
    /** Rendered contents of the tree item or child items. */
    children: ReactNode;
}
export declare const TreeViewItemContent: (props: SpectrumTreeViewItemContentProps) => ReactNode;
