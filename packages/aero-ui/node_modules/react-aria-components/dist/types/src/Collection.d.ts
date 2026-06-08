import { CollectionBase, DropTargetDelegate, GlobalDOMAttributes, ItemDropTarget, Key, LayoutDelegate, RefObject } from '@react-types/shared';
import { Collection as ICollection, Node, SelectionBehavior, SelectionMode, SectionProps as SharedSectionProps } from '@react-types/shared';
import React, { ForwardedRef, HTMLAttributes, ReactElement, ReactNode } from 'react';
import { StyleProps } from './utils';
export interface CollectionProps<T> extends Omit<CollectionBase<T>, 'children'> {
    /** The contents of the collection. */
    children?: ReactNode | ((item: T) => ReactNode);
    /** Values that should invalidate the item cache when using dynamic collections. */
    dependencies?: ReadonlyArray<any>;
}
export interface ItemRenderProps {
    /**
     * Whether the item is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the item is currently in a pressed state.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the item is currently selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the item is currently focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the item is currently keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the item is non-interactive, i.e. both selection and actions are disabled and the item
     * may not be focused. Dependent on `disabledKeys` and `disabledBehavior`.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * The type of selection that is allowed in the collection.
     *
     * @selector [data-selection-mode="single | multiple"]
     */
    selectionMode: SelectionMode;
    /** The selection behavior for the collection. */
    selectionBehavior: SelectionBehavior;
    /**
     * Whether the item allows dragging.
     *
     * @note This property is only available in collection components that support drag and drop.
     * @selector [data-allows-dragging]
     */
    allowsDragging?: boolean;
    /**
     * Whether the item is currently being dragged.
     *
     * @note This property is only available in collection components that support drag and drop.
     * @selector [data-dragging]
     */
    isDragging?: boolean;
    /**
     * Whether the item is currently an active drop target.
     *
     * @note This property is only available in collection components that support drag and drop.
     * @selector [data-drop-target]
     */
    isDropTarget?: boolean;
}
export interface SectionProps<T> extends Omit<SharedSectionProps<T>, 'children' | 'title'>, StyleProps, GlobalDOMAttributes<HTMLElement> {
    /** The unique id of the section. */
    id?: Key;
    /**
     * The object value that this section represents. When using dynamic collections, this is set
     * automatically.
     */
    value?: T;
    /** Static child items or a function to render children. */
    children?: ReactNode | ((item: T) => ReactElement);
    /** Values that should invalidate the item cache when using dynamic collections. */
    dependencies?: ReadonlyArray<any>;
}
interface SectionContextValue {
    name: string;
    render: (props: SectionProps<any>, ref: ForwardedRef<HTMLElement>, section: Node<any>, className?: string) => ReactElement;
}
export declare const SectionContext: React.Context<SectionContextValue | null>;
/** @deprecated */
export declare const Section: <T extends unknown>(props: SectionProps<T> & React.RefAttributes<HTMLElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface CollectionBranchProps {
    /** The collection of items to render. */
    collection: ICollection<Node<unknown>>;
    /** The parent node of the items to render. */
    parent: Node<unknown>;
    /** A function that renders a drop indicator between items. */
    renderDropIndicator?: (target: ItemDropTarget) => ReactNode;
}
export interface CollectionRootProps extends HTMLAttributes<HTMLElement> {
    /** The collection of items to render. */
    collection: ICollection<Node<unknown>>;
    /** A set of keys for items that should always be persisted in the DOM. */
    persistedKeys?: Set<Key> | null;
    /** A ref to the scroll container for the collection. */
    scrollRef?: RefObject<HTMLElement | null>;
    /** A function that renders a drop indicator between items. */
    renderDropIndicator?: (target: ItemDropTarget) => ReactNode;
}
export interface CollectionRenderer {
    /** Whether this is a virtualized collection. */
    isVirtualized?: boolean;
    /** A delegate object that provides layout information for items in the collection. */
    layoutDelegate?: LayoutDelegate;
    /** A delegate object that provides drop targets for pointer coordinates within the collection. */
    dropTargetDelegate?: DropTargetDelegate;
    /** A component that renders the root collection items. */
    CollectionRoot: React.ComponentType<CollectionRootProps>;
    /** A component that renders the child collection items. */
    CollectionBranch: React.ComponentType<CollectionBranchProps>;
}
export declare const DefaultCollectionRenderer: CollectionRenderer;
export declare function renderAfterDropIndicators(collection: ICollection<Node<unknown>>, node: Node<unknown>, renderDropIndicator: (target: ItemDropTarget) => ReactNode): ReactNode;
export declare const CollectionRendererContext: React.Context<CollectionRenderer>;
type PersistedKeysReturnValue = Set<Key> | null;
export declare function usePersistedKeys(focusedKey: Key | null): PersistedKeysReturnValue;
export {};
