import { AriaMenuProps } from 'react-aria/useMenu';
import { MenuTriggerProps as BaseMenuTriggerProps, RootMenuTriggerState } from 'react-stately/useMenuTriggerState';
import { ClassNameOrFunction, ContextValue, DOMRenderProps, PossibleLinkDOMRenderProps, RenderProps, SlotProps, StyleRenderProps } from './utils';
import { CollectionProps, ItemRenderProps, SectionProps } from './Collection';
import { FocusEvents, GlobalDOMAttributes, HoverEvents, Key, LinkDOMProps, MultipleSelection, PressEvents } from '@react-types/shared';
import React, { JSX, ReactElement, ReactNode } from 'react';
import { TreeState } from 'react-stately/useTreeState';
export declare const MenuContext: React.Context<ContextValue<MenuProps<any>, HTMLDivElement>>;
export declare const MenuStateContext: React.Context<TreeState<any> | null>;
export declare const RootMenuTriggerStateContext: React.Context<RootMenuTriggerState | null>;
export interface MenuTriggerProps extends BaseMenuTriggerProps {
    children: ReactNode;
}
export declare function MenuTrigger(props: MenuTriggerProps): JSX.Element | null;
export interface SubmenuTriggerProps {
    /**
     * The contents of the SubmenuTrigger. The first child should be an Item (the trigger) and the
     * second child should be the Popover (for the submenu).
     */
    children: ReactElement[];
    /**
     * The delay time in milliseconds for the submenu to appear after hovering over the trigger.
     *
     * @default 200
     */
    delay?: number;
}
/**
 * A submenu trigger is used to wrap a submenu's trigger item and the submenu itself.
 *
 * @version alpha
 */
export declare const SubmenuTrigger: (props: SubmenuTriggerProps & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface MenuRenderProps {
    /**
     * Whether the menu has no items and should display its empty state.
     *
     * @selector [data-empty]
     */
    isEmpty: boolean;
}
export interface MenuProps<T> extends Omit<AriaMenuProps<T>, 'children'>, CollectionProps<T>, StyleRenderProps<MenuRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Menu'
     */
    className?: ClassNameOrFunction<MenuRenderProps>;
    /** Provides content to display when there are no items in the list. */
    renderEmptyState?: () => ReactNode;
    /** Whether the menu should close when the menu item is selected. */
    shouldCloseOnSelect?: boolean;
}
/**
 * A menu displays a list of actions or options that a user can choose.
 */
export declare const Menu: <T>(props: MenuProps<T> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface MenuSectionProps<T> extends SectionProps<T>, Omit<MultipleSelection, 'disabledKeys'>, DOMRenderProps<'section', undefined> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-MenuSection'
     */
    className?: string;
    /** Whether the menu should close when the menu item is selected. */
    shouldCloseOnSelect?: boolean;
}
/**
 * A MenuSection represents a section within a Menu.
 */
export declare const MenuSection: <T>(props: MenuSectionProps<T> & React.RefAttributes<HTMLElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface MenuItemRenderProps extends ItemRenderProps {
    /**
     * Whether the item has a submenu.
     *
     * @selector [data-has-submenu]
     */
    hasSubmenu: boolean;
    /**
     * Whether the item's submenu is open.
     *
     * @selector [data-open]
     */
    isOpen: boolean;
}
export interface MenuItemProps<T = object> extends Omit<RenderProps<MenuItemRenderProps>, 'render'>, PossibleLinkDOMRenderProps<'div', MenuItemRenderProps>, LinkDOMProps, HoverEvents, FocusEvents, PressEvents, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-MenuItem'
     */
    className?: ClassNameOrFunction<MenuItemRenderProps>;
    /** The unique id of the item. */
    id?: Key;
    /**
     * The object value that this item represents. When using dynamic collections, this is set
     * automatically.
     */
    value?: T;
    /** A string representation of the item's contents, used for features like typeahead. */
    textValue?: string;
    /** An accessibility label for this item. */
    'aria-label'?: string;
    /** Whether the item is disabled. */
    isDisabled?: boolean;
    /** Handler that is called when the item is selected. */
    onAction?: () => void;
    /** Whether the menu should close when the menu item is selected. */
    shouldCloseOnSelect?: boolean;
}
/**
 * A MenuItem represents an individual action in a Menu.
 */
export declare const MenuItem: <T>(props: MenuItemProps<T> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
