import { AriaLabelingProps, CollectionChildren, DOMProps, DOMRef, Key, Orientation, SingleSelection, StyleProps } from '@react-types/shared';
import React, { ReactElement, ReactNode } from 'react';
export interface SpectrumTabsProps<T> extends Omit<SingleSelection, 'onSelectionChange' | 'disallowEmptySelection' | 'selectedKey' | 'defaultSelectedKey' | 'onSelectionChange'>, AriaLabelingProps, DOMProps, StyleProps {
    /** The children of the `<Tabs>` element. Should include `<TabList>` and `<TabPanels>` elements. */
    children: ReactNode;
    /** The item objects for each tab, for dynamic collections. */
    items?: Iterable<T>;
    /**
     * The keys of the tabs that are disabled. These tabs cannot be selected, focused, or otherwise
     * interacted with.
     */
    disabledKeys?: Iterable<Key>;
    /** Whether the Tabs are disabled. */
    isDisabled?: boolean;
    /** Whether the tabs are displayed in a quiet style. */
    isQuiet?: boolean;
    /** Whether the tabs are displayed in an emphasized style. */
    isEmphasized?: boolean;
    /** The amount of space between the tabs. */
    density?: 'compact' | 'regular';
    /** The currently selected key in the collection (controlled). */
    selectedKey?: Key;
    /** The initial selected keys in the collection (uncontrolled). */
    defaultSelectedKey?: Key;
    /** Handler that is called when the selection changes. */
    onSelectionChange?: (key: Key) => void;
    /**
     * Whether tabs are activated automatically on focus or manually.
     *
     * @default 'automatic'
     */
    keyboardActivation?: 'automatic' | 'manual';
    /**
     * The orientation of the tabs.
     *
     * @default 'horizontal'
     */
    orientation?: Orientation;
}
/**
 * Tabs organize content into multiple sections and allow users to navigate between them. The
 * content under the set of tabs should be related and form a coherent unit.
 */
export declare const Tabs: <T>(props: SpectrumTabsProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export interface SpectrumTabListProps<T> extends DOMProps, StyleProps {
    /**
     * The tab items to display. Item keys should match the key of the corresponding `<Item>` within
     * the `<TabPanels>` element.
     */
    children: CollectionChildren<T>;
}
/**
 * A TabList is used within Tabs to group tabs that a user can switch between. The keys of the items
 * within the <TabList> must match up with a corresponding item inside the <TabPanels>.
 */
export declare function TabList<T>(props: SpectrumTabListProps<T>): ReactElement;
export interface SpectrumTabPanelsProps<T> extends DOMProps, StyleProps {
    /**
     * The contents of each tab. Item keys should match the key of the corresponding `<Item>` within
     * the `<TabList>` element.
     */
    children: CollectionChildren<T>;
}
/**
 * TabPanels is used within Tabs as a container for the content of each tab. The keys of the items
 * within the <TabPanels> must match up with a corresponding item inside the <TabList>.
 */
export declare function TabPanels<T extends object>(props: SpectrumTabPanelsProps<T>): ReactElement;
