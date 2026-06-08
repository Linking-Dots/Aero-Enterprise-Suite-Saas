import { AriaTagGroupProps } from 'react-aria/useTagGroup';
import { ClassNameOrFunction, ContextValue, DOMProps, DOMRenderProps, RenderProps, SlotProps, StyleRenderProps } from './utils';
import { CollectionProps, ItemRenderProps } from './Collection';
import { FocusEvents, GlobalDOMAttributes, HoverEvents, Key, LinkDOMProps, PressEvents } from '@react-types/shared';
import { ListState } from 'react-stately/useListState';
import React, { ReactNode } from 'react';
export interface TagGroupProps extends Omit<AriaTagGroupProps<unknown>, 'children' | 'items' | 'label' | 'description' | 'errorMessage' | 'keyboardDelegate'>, DOMProps, SlotProps, DOMRenderProps<'div', undefined>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-TagGroup'
     */
    className?: string;
}
export interface TagListRenderProps {
    /**
     * Whether the tag list has no items and should display its empty state.
     *
     * @selector [data-empty]
     */
    isEmpty: boolean;
    /**
     * Whether the tag list is currently focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the tag list is currently keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * State of the TagGroup.
     */
    state: ListState<unknown>;
}
export interface TagListProps<T> extends Omit<CollectionProps<T>, 'disabledKeys'>, StyleRenderProps<TagListRenderProps, 'div'>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-TagList'
     */
    className?: ClassNameOrFunction<TagListRenderProps>;
    /** Provides content to display when there are no items in the tag list. */
    renderEmptyState?: (props: TagListRenderProps) => ReactNode;
}
export declare const TagGroupContext: React.Context<ContextValue<TagGroupProps, HTMLDivElement>>;
export declare const TagListContext: React.Context<ContextValue<TagListProps<any>, HTMLDivElement>>;
/**
 * A tag group is a focusable list of labels, categories, keywords, filters, or other items, with
 * support for keyboard navigation, selection, and removal.
 */
export declare const TagGroup: (props: TagGroupProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A tag list is a container for tags within a TagGroup.
 */
export declare const TagList: <T>(props: TagListProps<T> & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface TagRenderProps extends Omit<ItemRenderProps, 'allowsDragging' | 'isDragging' | 'isDropTarget'> {
    /**
     * Whether the tag group allows items to be removed.
     *
     * @selector [data-allows-removing]
     */
    allowsRemoving: boolean;
}
export interface TagProps extends RenderProps<TagRenderProps, 'div'>, LinkDOMProps, HoverEvents, FocusEvents, PressEvents, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Tag'
     */
    className?: ClassNameOrFunction<TagRenderProps>;
    /** A unique id for the tag. */
    id?: Key;
    /**
     * A string representation of the tags's contents, used for accessibility.
     * Required if children is not a plain text string.
     */
    textValue?: string;
    /** Whether the tag is disabled. */
    isDisabled?: boolean;
    /**
     * Handler that is called when a user performs an action on the item. The exact user event depends
     * on the collection's `selectionBehavior` prop and the interaction modality.
     */
    onAction?: () => void;
}
/**
 * A Tag is an individual item within a TagList.
 */
export declare const Tag: (props: TagProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
