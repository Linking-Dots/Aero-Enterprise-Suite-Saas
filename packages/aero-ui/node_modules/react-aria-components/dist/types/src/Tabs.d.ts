import { AriaLabelingProps, FocusEvents, GlobalDOMAttributes, HoverEvents, Key, LinkDOMProps, PressEvents } from '@react-types/shared';
import { AriaTabListProps, AriaTabPanelProps } from 'react-aria/useTabList';
import { ClassNameOrFunction, ContextValue, DOMRenderProps, PossibleLinkDOMRenderProps, RenderProps, SlotProps, StyleProps, StyleRenderProps } from './utils';
import { CollectionProps } from './Collection';
import { Orientation } from '@react-types/shared';
import React from 'react';
import { TabListState } from 'react-stately/useTabListState';
export interface TabsProps extends Omit<AriaTabListProps<any>, 'items' | 'children'>, RenderProps<TabsRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Tabs'
     */
    className?: ClassNameOrFunction<TabsRenderProps>;
}
export interface TabsRenderProps {
    /**
     * The orientation of the tabs.
     *
     * @selector [data-orientation="horizontal | vertical"]
     */
    orientation: Orientation;
}
export interface TabListProps<T> extends StyleRenderProps<TabListRenderProps>, AriaLabelingProps, Omit<CollectionProps<T>, 'disabledKeys'>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-TabList'
     */
    className?: ClassNameOrFunction<TabListRenderProps>;
}
export interface TabListRenderProps {
    /**
     * The orientation of the tab list.
     *
     * @selector [data-orientation="horizontal | vertical"]
     */
    orientation: Orientation;
    /**
     * State of the tab list.
     */
    state: TabListState<unknown>;
}
export interface TabProps extends Omit<RenderProps<TabRenderProps>, 'render'>, PossibleLinkDOMRenderProps<'div', TabRenderProps>, AriaLabelingProps, LinkDOMProps, HoverEvents, FocusEvents, PressEvents, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Tab'
     */
    className?: ClassNameOrFunction<TabRenderProps>;
    /** The unique id of the tab. */
    id?: Key;
    /** Whether the tab is disabled. */
    isDisabled?: boolean;
}
export interface TabRenderProps {
    /**
     * Whether the tab is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the tab is currently in a pressed state.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the tab is currently selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the tab is currently focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the tab is currently keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the tab is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
}
export interface TabPanelProps extends AriaTabPanelProps, RenderProps<TabPanelRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-TabPanel'
     */
    className?: ClassNameOrFunction<TabPanelRenderProps>;
    /**
     * Whether to mount the tab panel in the DOM even when it is not currently selected. Inactive tab
     * panels are inert and cannot be interacted with. They must be styled appropriately so this is
     * clear to the user visually.
     *
     * @default false
     */
    shouldForceMount?: boolean;
}
export interface TabPanelRenderProps {
    /**
     * Whether the tab panel is currently focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the tab panel is currently keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the tab panel is currently non-interactive. This occurs when the
     * `shouldForceMount` prop is true, and the corresponding tab is not selected.
     *
     * @selector [data-inert]
     */
    isInert: boolean;
    /**
     * Whether the tab panel is currently entering. Use this to apply animations.
     *
     * @selector [data-entering]
     */
    isEntering: boolean;
    /**
     * Whether the tab panel is currently exiting. Use this to apply animations.
     *
     * @selector [data-exiting]
     */
    isExiting: boolean;
    /**
     * State of the tab list.
     */
    state: TabListState<unknown>;
}
export declare const TabsContext: React.Context<ContextValue<TabsProps, HTMLDivElement>>;
export declare const TabListStateContext: React.Context<TabListState<any> | null>;
/**
 * Tabs organize content into multiple sections and allow users to navigate between them.
 */
export declare const Tabs: (props: TabsProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A TabList is used within Tabs to group tabs that a user can switch between. The ids of the items
 * within the <TabList> must match up with a corresponding item inside the <TabPanels>.
 */
export declare const TabList: <T>(props: TabListProps<T> & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A Tab provides a title for an individual item within a TabList.
 */
export declare const Tab: (props: TabProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface TabPanelsProps<T> extends Omit<CollectionProps<T>, 'disabledKeys'>, StyleProps, DOMRenderProps<'div', undefined>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-TabPanels'
     */
    className?: string;
}
/**
 * Groups multiple `<TabPanel>` elements, and provides CSS variables for animated transitions.
 */
export declare const TabPanels: <T>(props: TabPanelsProps<T> & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A TabPanel provides the content for a tab.
 */
export declare const TabPanel: (props: TabPanelProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
