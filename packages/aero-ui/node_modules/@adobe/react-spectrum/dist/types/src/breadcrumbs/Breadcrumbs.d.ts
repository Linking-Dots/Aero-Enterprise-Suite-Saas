import { AriaBreadcrumbsProps } from 'react-aria/useBreadcrumbs';
import { ItemProps, Key, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumBreadcrumbsProps<T> extends AriaBreadcrumbsProps, StyleProps {
    /** The breadcrumb items. */
    children: ReactElement<ItemProps<T>> | ReactElement<ItemProps<T>>[];
    /** Whether the Breadcrumbs are disabled. */
    isDisabled?: boolean;
    /** Called when an item is acted upon (usually selection via press). */
    onAction?: (key: Key) => void;
    /**
     * Size of the Breadcrumbs including spacing and layout.
     *
     * @default 'L'
     */
    size?: 'S' | 'M' | 'L';
    /** Whether to always show the root item if the items are collapsed. */
    showRoot?: boolean;
    /**
     * Whether to place the last Breadcrumb item onto a new line.
     */
    isMultiline?: boolean;
    /**
     * Whether to autoFocus the last Breadcrumb item when the Breadcrumbs render.
     */
    autoFocusCurrent?: boolean;
}
/**
 * Breadcrumbs show hierarchy and navigational context for a user’s location within an application.
 */
export declare const Breadcrumbs: React.ForwardRefExoticComponent<SpectrumBreadcrumbsProps<unknown> & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
