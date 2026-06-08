import { DOMProps, DOMRef, ItemElement, ItemRenderer, Key, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
interface ActionBarProps<T> {
    /**
     * An list of `Item` elements or a function. If the latter, a list of items must be provided using
     * the `items` prop.
     */
    children: ItemElement<T> | ItemElement<T>[] | ItemRenderer<T>;
    /** A list of items to display as children. Must be used with a function as the sole child. */
    items?: Iterable<T>;
    /** A list of keys to disable. */
    disabledKeys?: Iterable<Key>;
    /**
     * The number of selected items that the ActionBar is currently linked to. If 0, the ActionBar is
     * hidden.
     */
    selectedItemCount: number | 'all';
    /** Handler that is called when the ActionBar clear button is pressed. */
    onClearSelection: () => void;
    /** Whether the ActionBar should be displayed with a emphasized style. */
    isEmphasized?: boolean;
    /** Handler that is called when an ActionBar button is pressed. */
    onAction?: (key: Key) => void;
    /**
     * Defines when the text within the buttons should be hidden and only the icon should be shown.
     * When set to 'hide', the text is always shown in a tooltip. When set to 'collapse', the text is
     * visible if space is available, and hidden when space is limited. The text is always visible
     * when the item is collapsed into a menu.
     *
     * @default 'collapse'
     */
    buttonLabelBehavior?: 'show' | 'collapse' | 'hide';
}
export interface SpectrumActionBarProps<T> extends ActionBarProps<T>, DOMProps, StyleProps {
}
/**
 * Action bars are used for single and bulk selection patterns when a user needs to perform actions
 * on one or more items at the same time.
 */
export declare const ActionBar: <T>(props: SpectrumActionBarProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export {};
